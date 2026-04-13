<?php

namespace App\Support\FailedJobs;

final class FailedJobInspector
{
    public static function teamIdFromPayload(?array $payload): ?int
    {
        $command = $payload['data']['command'] ?? null;
        if (! is_string($command) || $command === '') {
            return null;
        }

        if (preg_match('/"team_id";i:(\d+)/', $command, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/App\\\\Models\\\\Team";s:2:"id";i:(\d+)/', $command, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function jobLabelFromPayload(?array $payload): string
    {
        $label = $payload['displayName'] ?? null;
        if (is_string($label) && $label !== '') {
            return $label;
        }

        $job = $payload['job'] ?? null;
        if (is_string($job) && $job !== '') {
            return $job;
        }

        $commandName = $payload['data']['commandName'] ?? null;
        if (is_string($commandName) && $commandName !== '') {
            return $commandName;
        }

        return 'Unknown';
    }
}

