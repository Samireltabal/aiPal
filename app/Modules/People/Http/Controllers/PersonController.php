<?php

declare(strict_types=1);

namespace App\Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\PersonPhone;
use App\Modules\People\Http\Requests\PersonRequest;
use App\Modules\People\Http\Resources\PersonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PersonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Person::query()
            ->where('user_id', $user->id)
            ->with(['emails', 'phones']);

        if ($q = trim((string) $request->query('q', ''))) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], strtolower($q)).'%';
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($w) use ($like, $likeOp) {
                $w->whereRaw('LOWER(display_name) '.$likeOp.' ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(company, \'\')) '.$likeOp.' ?', [$like])
                    ->orWhereExists(function ($sub) use ($like, $likeOp) {
                        $sub->select(DB::raw(1))
                            ->from('person_emails')
                            ->whereColumn('person_emails.person_id', 'people.id')
                            ->whereRaw('LOWER(email) '.$likeOp.' ?', [$like]);
                    });
            });
        }

        if ($tag = $request->query('tag')) {
            $query->whereJsonContains('tags', $tag);
        }

        if ($request->boolean('stale')) {
            $threshold = now()->subDays((int) config('people.staleness_days', 90));
            $query->where(function ($w) use ($threshold) {
                $w->whereNull('last_contact_at')->orWhere('last_contact_at', '<', $threshold);
            });
        }

        $sort = match ($request->query('sort')) {
            'name' => ['display_name', 'asc'],
            'recent' => ['last_contact_at', 'desc'],
            default => ['updated_at', 'desc'],
        };

        $people = $query->orderByRaw("$sort[0] IS NULL")->orderBy($sort[0], $sort[1])->paginate(50);

        return PersonResource::collection($people);
    }

    public function show(Request $request, int $id): PersonResource
    {
        $person = Person::query()
            ->where('user_id', $request->user()->id)
            ->with(['emails', 'phones'])
            ->findOrFail($id);

        return new PersonResource($person);
    }

    public function store(PersonRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $person = DB::transaction(function () use ($user, $validated) {
            $person = Person::create([
                'user_id' => $user->id,
                'context_id' => $validated['context_id'] ?? $user->currentContext()?->id ?? $user->defaultContext()?->id,
                'display_name' => $validated['display_name'],
                'given_name' => $validated['given_name'] ?? null,
                'family_name' => $validated['family_name'] ?? null,
                'nickname' => $validated['nickname'] ?? null,
                'company' => $validated['company'] ?? null,
                'title' => $validated['title'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'tags' => $validated['tags'] ?? [],
                'custom' => $validated['custom'] ?? [],
                'birthday' => $validated['birthday'] ?? null,
                'photo_url' => $validated['photo_url'] ?? null,
            ]);

            foreach ($validated['emails'] ?? [] as $idx => $email) {
                PersonEmail::firstOrCreate(
                    ['user_id' => $user->id, 'email' => strtolower($email)],
                    ['person_id' => $person->id, 'is_primary' => $idx === 0],
                );
            }
            foreach ($validated['phones'] ?? [] as $idx => $phone) {
                PersonPhone::firstOrCreate(
                    ['user_id' => $user->id, 'phone' => $phone],
                    ['person_id' => $person->id, 'is_primary' => $idx === 0],
                );
            }

            return $person->load(['emails', 'phones']);
        });

        return (new PersonResource($person))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(PersonRequest $request, int $id): PersonResource
    {
        $user = $request->user();
        $person = Person::query()->where('user_id', $user->id)->findOrFail($id);

        $person->update($request->safe()->except(['emails', 'phones']));

        return new PersonResource($person->fresh(['emails', 'phones']));
    }

    public function destroy(Request $request, int $id): Response
    {
        $person = Person::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $person->delete();

        return response()->noContent();
    }
}
