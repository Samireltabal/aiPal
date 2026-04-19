<?php

namespace App\Services;

use Google\Client;

class GoogleClientFactory
{
    public function make(): Client
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        return $client;
    }
}
