<?php

namespace NightshiftFoundry\AlertStream\Commands;

use Illuminate\Console\Command;
use NightshiftFoundry\AlertStream\Models\Snapshot;

class PruneSnapshotsCommand extends Command
{
    protected $signature = 'alertstream:prune-snapshots
                            {--days= : Delete snapshots older than this many days (default: config value)}';

    protected $description = 'Delete AlertStream snapshots older than the retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('alertstream.snapshots.retention_days', 30));

        $count = Snapshot::expired($days)->delete();

        $this->info("✓ Pruned {$count} snapshot(s) older than {$days} day(s).");

        return 0;
    }
}
