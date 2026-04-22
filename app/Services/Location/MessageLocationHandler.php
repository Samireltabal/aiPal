<?php

declare(strict_types=1);

namespace App\Services\Location;

use App\Models\User;

/**
 * Detects and saves location from incoming channel messages.
 *
 * Two sources supported:
 *   1. Native lat/lon (from WhatsApp/Telegram "share location" message types)
 *   2. Maps URLs embedded in free-text messages (google.com/maps, goo.gl/maps, apple maps)
 *
 * Returns a confirmation message string when a location was saved, null otherwise.
 */
class MessageLocationHandler
{
    public function __construct(
        private readonly LocationUpdater $updater,
        private readonly MapsUrlParser $parser,
    ) {}

    public function handleNativeShare(User $user, float $latitude, float $longitude, string $source): ?string
    {
        $result = $this->updater->updateFromCoordinates($user, $latitude, $longitude, $source, force: true);

        if (! $result['updated']) {
            return null;
        }

        return $this->buildConfirmation($result['name']);
    }

    /**
     * Try to extract a maps URL from text and save the resulting location.
     * Returns confirmation message if handled, null if no recognizable URL.
     */
    public function handleTextMaybeContainingUrl(User $user, string $text, string $source): ?string
    {
        $coords = $this->parser->parseFromText($text);
        if ($coords === null) {
            return null;
        }

        $result = $this->updater->updateFromCoordinates(
            $user,
            $coords['latitude'],
            $coords['longitude'],
            $source,
            force: true,
        );

        if (! $result['updated']) {
            return null;
        }

        return $this->buildConfirmation($result['name']);
    }

    private function buildConfirmation(?string $name): string
    {
        $label = $name !== null && $name !== '' ? $name : 'that location';

        return "📍 Got it — I've saved your location as *{$label}*. I'll use this for weather, time, and other location-aware answers.";
    }
}
