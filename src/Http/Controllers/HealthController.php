<?php

namespace NightshiftFoundry\AlertStream\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $config = config('alertstream');

        return response()->json([
            'status' => $config['enabled'] ? 'active' : 'disabled',
            'channels' => $config['channels']['active'] ?? [],
            'queue' => [
                'enabled' => (bool) ($config['queue'] ?? true),
                'connection' => $config['queue_connection'] ?? config('queue.default'),
                'name' => $config['queue_name'] ?? 'default',
            ],
            'snapshots' => [
                'enabled' => (bool) ($config['snapshots']['enabled'] ?? false),
                'table' => $config['snapshots']['table'] ?? 'alertstream_snapshots',
            ],
            'throttle' => [
                'enabled' => (bool) ($config['throttle']['enabled'] ?? false),
                'max' => $config['throttle']['max'] ?? 5,
                'cooldown_minutes' => $config['throttle']['cooldown_minutes'] ?? 60,
            ],
            'report_exceptions' => (bool) ($config['report_exceptions'] ?? true),
            'muted_count' => count($config['mute'] ?? []),
        ]);
    }
}
