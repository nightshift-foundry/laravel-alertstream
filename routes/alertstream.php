<?php

use Illuminate\Support\Facades\Route;
use NightshiftFoundry\AlertStream\Http\Controllers\HealthController;
use NightshiftFoundry\AlertStream\Http\Controllers\SnapshotController;

$prefix = config('alertstream.snapshots.route_prefix', 'alertstream');
$middleware = config('alertstream.snapshots.route_middleware', ['web']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function (): void {
        Route::get('/health', HealthController::class)
            ->name('alertstream.health');

        Route::get('/snapshots', [SnapshotController::class, 'index'])
            ->name('alertstream.snapshots.index');

        Route::get('/snapshots/{hash}', [SnapshotController::class, 'show'])
            ->name('alertstream.snapshots.show')
            ->where('hash', '[a-f0-9]{64}');

        Route::delete('/snapshots/{hash}', [SnapshotController::class, 'destroy'])
            ->name('alertstream.snapshots.destroy')
            ->where('hash', '[a-f0-9]{64}');
    });
