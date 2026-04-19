<?php

declare(strict_types=1);

namespace App\Contracts;

interface MessagingChannel
{
    public function send(string $recipient, string $message): void;

    public function channelName(): string;
}
