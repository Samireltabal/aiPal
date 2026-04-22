<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorkflowWebhookController extends Controller
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $workflow = Workflow::query()
            ->where('trigger_type', 'webhook')
            ->where('webhook_token', $token)
            ->where('enabled', true)
            ->first();

        if (! $workflow) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $payload = [
            'body' => $request->all(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'method' => $request->method(),
        ];

        RunWorkflowJob::dispatch($workflow->id, 'webhook', $payload);

        return response()->json(['accepted' => true, 'workflow' => $workflow->name], Response::HTTP_ACCEPTED);
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, array<int, string>>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $drop = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

        return array_filter(
            $headers,
            fn (string $key) => ! in_array(strtolower($key), $drop, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
