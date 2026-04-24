<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\User;
use App\Services\ForwardedEmailProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class InboundEmailController extends Controller
{
    public function __invoke(Request $request, ForwardedEmailProcessor $processor): JsonResponse
    {
        if (! $this->verifyHmac($request)) {
            return response()->json(['error' => 'invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->json()->all();

        if (! $this->acceptedByProvider($data)) {
            return response()->json(['error' => 'spf/dkim failed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $toAddress = (string) ($data['to'] ?? '');
        [$user, $slug] = $this->resolveUserAndSlug($toAddress);

        if ($user === null) {
            return response()->json(['error' => 'unknown recipient'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->withinRateLimit($user->id)) {
            return response()->json(['error' => 'rate limited'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $subject = $this->truncate((string) ($data['subject'] ?? '(no subject)'), 500);
        $body = $this->truncate(
            (string) ($data['text'] ?? $data['body'] ?? ''),
            (int) config('inbound.max_body_kb') * 1024,
        );
        $from = is_string($data['from'] ?? null) ? $data['from'] : null;

        $context = $this->resolveContext($user, $slug, $from);

        if ($context === null) {
            return response()->json(['error' => 'unknown context slug'], Response::HTTP_NOT_FOUND);
        }

        $confirmation = $processor->process($user, $context, $subject, $body, $from);

        $this->replyConfirmation($user, $subject, $confirmation, $from);

        return response()->json([
            'ok' => true,
            'confirmation' => $confirmation,
            'context' => $context->slug,
        ], Response::HTTP_ACCEPTED);
    }

    private function verifyHmac(Request $request): bool
    {
        $secret = (string) config('inbound.hmac_secret');
        $header = (string) $request->header('X-Inbound-Signature', '');

        if ($secret === '' || $header === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }

    /** @param array<string, mixed> $data */
    private function acceptedByProvider(array $data): bool
    {
        $spf = strtolower((string) ($data['spf'] ?? 'pass'));
        $dkim = strtolower((string) ($data['dkim'] ?? 'pass'));

        return in_array($spf, ['pass', 'none'], true) && in_array($dkim, ['pass', 'none'], true);
    }

    /** @return array{0: ?User, 1: ?string} [user, slug] */
    private function resolveUserAndSlug(string $toAddress): array
    {
        $normalized = strtolower($toAddress);

        // forward-{token}-{slug}@... with explicit slug
        if (preg_match('/^forward-([a-z0-9]{32,64})-([a-z0-9-]{1,60})@/', $normalized, $m)) {
            $token = $m[1];
            $slug = $m[2];
            $user = User::query()->where('inbound_email_token', $token)->first();

            return [$user, $slug];
        }

        // forward-{token}@... with no slug — falls back to default context
        if (preg_match('/^forward-([a-z0-9]{32,64})@/', $normalized, $m)) {
            $user = User::query()->where('inbound_email_token', $m[1])->first();

            return [$user, null];
        }

        return [null, null];
    }

    /**
     * Precedence:
     * 1. Explicit slug in to-address — always wins if present and matches.
     * 2. Sender-domain inference rules — first match by user-defined priority.
     * 3. Mandatory catch-all: default context. Never drop silently.
     */
    private function resolveContext(User $user, ?string $slug, ?string $fromAddress): ?Context
    {
        if ($slug !== null) {
            $context = $user->contexts()->where('slug', $slug)->whereNull('archived_at')->first();
            if ($context === null) {
                return null;
            }

            $inferred = $this->inferFromSender($user, $fromAddress);
            if ($inferred !== null && $inferred->id !== $context->id) {
                Log::info('Inbound email slug overrides inference', [
                    'user_id' => $user->id,
                    'slug_context' => $context->slug,
                    'inferred_context' => $inferred->slug,
                    'from' => $fromAddress,
                ]);
            }

            return $context;
        }

        $inferred = $this->inferFromSender($user, $fromAddress);
        if ($inferred !== null) {
            return $inferred;
        }

        return $user->defaultContext();
    }

    private function inferFromSender(User $user, ?string $fromAddress): ?Context
    {
        if ($fromAddress === null) {
            return null;
        }

        $normalized = strtolower($fromAddress);
        // Extract the angle-addr portion if present: "Name <user@host>"
        if (preg_match('/<([^>]+)>/', $normalized, $m)) {
            $normalized = $m[1];
        }

        $contexts = $user->contexts()
            ->whereNull('archived_at')
            ->whereNotNull('inference_rules')
            ->get();

        $matches = [];

        foreach ($contexts as $context) {
            $rules = is_array($context->inference_rules) ? $context->inference_rules : [];
            foreach ($rules as $rule) {
                if (($rule['type'] ?? null) !== 'sender_domain') {
                    continue;
                }
                $domain = strtolower((string) ($rule['value'] ?? ''));
                if ($domain === '') {
                    continue;
                }
                $domain = ltrim($domain, '@');
                if (str_ends_with($normalized, '@'.$domain) || str_ends_with($normalized, '.'.$domain)) {
                    $matches[] = [
                        'context' => $context,
                        'priority' => (int) ($rule['priority'] ?? 99),
                    ];
                }
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        return $matches[0]['context'];
    }

    private function withinRateLimit(int $userId): bool
    {
        $max = (int) config('inbound.rate_limit_per_minute');

        return RateLimiter::attempt(
            key: "inbound-email:{$userId}",
            maxAttempts: $max,
            callback: fn () => true,
            decaySeconds: 60,
        ) !== false;
    }

    private function truncate(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private function replyConfirmation(User $user, string $subject, string $confirmation, ?string $fromAddress): void
    {
        $replyTo = $fromAddress ?: $user->email;

        if ($replyTo === null || $replyTo === '') {
            return;
        }

        try {
            Mail::raw($confirmation, function ($message) use ($replyTo, $subject): void {
                $message->to($replyTo)->subject('aiPal — '.($subject !== '' ? $subject : 'forwarded email'));
            });
        } catch (\Throwable $e) {
            Log::warning('Inbound email confirmation reply failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
