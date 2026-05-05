<?php

namespace NightshiftFoundry\AlertStream\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NightshiftFoundry\AlertStream\Models\Snapshot;

class SnapshotController extends Controller
{
    public function index(Request $request)
    {
        $query = Snapshot::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('exception_message', 'like', "%{$search}%");
            });
        }

        if ($severity = $request->input('severity')) {
            $query->whereJsonContains('context->severity', $severity);
        }

        $snapshots = $query->paginate(25);

        return view('alertstream::snapshots.index', compact('snapshots', 'search', 'severity'));
    }

    public function show(string $hash)
    {
        $snapshot = Snapshot::where('hash', $hash)->firstOrFail();

        $frames = [];
        foreach (explode("\n", $snapshot->trace ?? '') as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }

            // Match:  #N /path/to/File.php(line): SomeClass->method(args)
            if (preg_match('/^(#\d+)\s+(.+?)(?:\((\d+)\))?\s*:\s*(.*)$/', $raw, $m)) {
                $file = $m[2];
                $lineNum = $m[3] !== '' ? (int) $m[3] : null;

                $snippet = null;
                if ($lineNum && file_exists($file) && is_readable($file)) {
                    $allLines = @file($file, FILE_IGNORE_NEW_LINES);
                    if ($allLines !== false) {
                        $zero = $lineNum - 1;                          // 0-based target
                        $start = max(0, $zero - 7);
                        $end = min(count($allLines) - 1, $zero + 7);
                        $snippet = [
                            'start' => $start + 1,                   // 1-based for display
                            'highlight' => $lineNum,
                            'lines' => array_slice($allLines, $start, $end - $start + 1),
                        ];
                    }
                }

                $frames[] = [
                    'num' => $m[1],
                    'file' => $file,
                    'line' => $lineNum,
                    'call' => $m[4],
                    'raw' => $raw,
                    'snippet' => $snippet,
                ];
            } else {
                // e.g. {main} or any unmatched line
                $frames[] = [
                    'num' => null,
                    'file' => null,
                    'line' => null,
                    'call' => $raw,
                    'raw' => $raw,
                    'snippet' => null,
                ];
            }
        }

        return view('alertstream::snapshots.show', compact('snapshot', 'frames'));
    }

    public function destroy(string $hash)
    {
        Snapshot::where('hash', $hash)->firstOrFail()->delete();

        return redirect()->route('alertstream.snapshots.index')
            ->with('alertstream_deleted', true);
    }
}
