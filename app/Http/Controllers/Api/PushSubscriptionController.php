<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
        ]);

        $user = Auth::user();

        PushSubscription::updateOrCreate(
            ['user_id' => $user->id, 'endpoint' => $validated['endpoint']],
            ['public_key' => $validated['public_key'], 'auth_token' => $validated['auth_token']],
        );

        $user->update(['push_notifications_enabled' => true]);

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $user = Auth::user();

        PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        if ($user->pushSubscriptions()->doesntExist()) {
            $user->update(['push_notifications_enabled' => false]);
        }

        return response()->json(['status' => 'unsubscribed']);
    }
}
