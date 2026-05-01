<?php

declare(strict_types=1);

namespace App\Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Person;
use App\Modules\People\Http\Requests\InteractionRequest;
use App\Modules\People\Http\Resources\InteractionResource;
use App\Modules\People\Services\InteractionRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InteractionController extends Controller
{
    public function __construct(private readonly InteractionRecorder $recorder) {}

    public function index(Request $request, int $personId): AnonymousResourceCollection
    {
        $user = $request->user();
        Person::query()->where('user_id', $user->id)->findOrFail($personId);

        $items = Interaction::query()
            ->where('user_id', $user->id)
            ->where('person_id', $personId)
            ->orderByDesc('occurred_at')
            ->paginate(50);

        return InteractionResource::collection($items);
    }

    public function store(InteractionRequest $request, int $personId): JsonResponse
    {
        $user = $request->user();
        $person = Person::query()->where('user_id', $user->id)->findOrFail($personId);

        $interaction = $this->recorder->record($person, $request->validated());

        return (new InteractionResource($interaction))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
