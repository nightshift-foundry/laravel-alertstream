<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snapshots — AlertStream</title>
    <style>
        /* ── Tokens: dark defaults ────────────────────────────────────── */
        :root {
            --bg:             #0f172a;
            --surface:        #1e293b;
            --border:         #334155;
            --text:           #e2e8f0;
            --text-muted:     #94a3b8;
            --text-dim:       #64748b;
            --input-bg:       #1e293b;
            --input-border:   #334155;
            --input-text:     #e2e8f0;
            --btn-bg:         #3b82f6;
            --btn-hover:      #2563eb;
            --th-text:        #94a3b8;
            --td-text:        #f1f5f9;
            --td-link:        #60a5fa;
            --td-mono:        #94a3b8;
            --row-border:     #1e293b;
            --head-border:    #334155;
            --page-bg:        #1e293b;
            --page-border:    #334155;
            --page-text:      #94a3b8;
            --page-active-bg: #3b82f6;
            --notice-bg:      #14532d;
            --notice-border:  #166534;
            --notice-text:    #bbf7d0;
            --occ-text:       #94a3b8;
            --empty-text:     #64748b;
            --toggle-bg:      #1e293b;
            --toggle-border:  #334155;
            --toggle-text:    #94a3b8;
        }

        /* ── Tokens: light via OS preference ─────────────────────────── */
        @media (prefers-color-scheme: light) {
            :root { --bg: #f1f5f9; --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #475569; --text-dim: #94a3b8; --input-bg: #ffffff; --input-border: #e2e8f0; --input-text: #0f172a; --btn-bg: #3b82f6; --btn-hover: #2563eb; --th-text: #64748b; --td-text: #1e293b; --td-link: #2563eb; --td-mono: #64748b; --row-border: #f1f5f9; --head-border: #e2e8f0; --page-bg: #ffffff; --page-border: #e2e8f0; --page-text: #64748b; --page-active-bg: #3b82f6; --notice-bg: #f0fdf4; --notice-border: #bbf7d0; --notice-text: #166534; --occ-text: #64748b; --empty-text: #94a3b8; --toggle-bg: #ffffff; --toggle-border: #e2e8f0; --toggle-text: #475569; }
        }

        /* ── Explicit overrides — MUST come after the media query ─────── */
        [data-theme="dark"]  { --bg: #0f172a; --surface: #1e293b; --border: #334155; --text: #e2e8f0; --text-muted: #94a3b8; --text-dim: #64748b; --input-bg: #1e293b; --input-border: #334155; --input-text: #e2e8f0; --btn-bg: #3b82f6; --btn-hover: #2563eb; --th-text: #94a3b8; --td-text: #f1f5f9; --td-link: #60a5fa; --td-mono: #94a3b8; --row-border: #1e293b; --head-border: #334155; --page-bg: #1e293b; --page-border: #334155; --page-text: #94a3b8; --page-active-bg: #3b82f6; --notice-bg: #14532d; --notice-border: #166534; --notice-text: #bbf7d0; --occ-text: #94a3b8; --empty-text: #64748b; --toggle-bg: #1e293b; --toggle-border: #334155; --toggle-text: #94a3b8; }
        [data-theme="light"] { --bg: #f1f5f9; --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #475569; --text-dim: #94a3b8; --input-bg: #ffffff; --input-border: #e2e8f0; --input-text: #0f172a; --btn-bg: #3b82f6; --btn-hover: #2563eb; --th-text: #64748b; --td-text: #1e293b; --td-link: #2563eb; --td-mono: #64748b; --row-border: #f1f5f9; --head-border: #e2e8f0; --page-bg: #ffffff; --page-border: #e2e8f0; --page-text: #64748b; --page-active-bg: #3b82f6; --notice-bg: #f0fdf4; --notice-border: #bbf7d0; --notice-text: #166534; --occ-text: #64748b; --empty-text: #94a3b8; --toggle-bg: #ffffff; --toggle-border: #e2e8f0; --toggle-text: #475569; }

        /* ── Base ─────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; padding: 2rem 1rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; }

        /* ── Theme toggle ─────────────────────────────────────────────── */
        .theme-toggle { position: fixed; top: 1rem; right: 1rem; background: var(--toggle-bg); border: 1px solid var(--toggle-border); color: var(--toggle-text); border-radius: 0.5rem; padding: 0.6rem 1.1rem; font-size: 0.875rem; font-family: ui-sans-serif, system-ui, sans-serif; font-weight: 500; cursor: pointer; line-height: 1.4; z-index: 100; display: inline-flex; align-items: center; gap: 0.4rem; transition: border-color 0.15s, box-shadow 0.15s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .theme-toggle:hover { border-color: var(--text-muted); box-shadow: 0 2px 6px rgba(0,0,0,0.4); }

        /* ── Card ─────────────────────────────────────────────────────── */
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; }

        /* ── Filters ──────────────────────────────────────────────────── */
        .filters { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .filters input, .filters select { background: var(--input-bg); border: 1px solid var(--input-border); color: var(--input-text); padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; }
        .filters input { flex: 1; min-width: 200px; }
        .filters button { background: var(--btn-bg); color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; }
        .filters button:hover { background: var(--btn-hover); }

        /* ── Table ────────────────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { text-align: left; padding: 0.75rem; color: var(--th-text); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--head-border); }
        td { padding: 0.75rem; border-bottom: 1px solid var(--row-border); color: var(--td-text); }
        td a { color: var(--td-link); text-decoration: none; }
        td a:hover { text-decoration: underline; }
        .td-mono { font-family: monospace; font-size: 0.8rem; color: var(--td-mono); }

        /* ── Badges ───────────────────────────────────────────────────── */
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        .badge-critical { background: #991b1b; color: #fecaca; }
        .badge-error    { background: #9a3412; color: #fed7aa; }
        .badge-warning  { background: #854d0e; color: #fef08a; }

        /* ── Pagination ───────────────────────────────────────────────── */
        .pagination { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem; }
        .pagination a, .pagination span { padding: 0.4rem 0.75rem; border-radius: 0.375rem; font-size: 0.8rem; color: var(--page-text); background: var(--page-bg); border: 1px solid var(--page-border); text-decoration: none; }
        .pagination span.current { background: var(--page-active-bg); color: white; border-color: var(--page-active-bg); }

        /* ── Misc ─────────────────────────────────────────────────────── */
        .empty { text-align: center; color: var(--empty-text); padding: 3rem; }
        .deleted-notice { background: var(--notice-bg); border: 1px solid var(--notice-border); color: var(--notice-text); border-radius: 0.75rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .occ { font-size: 0.75rem; color: var(--occ-text); }
    </style>
</head>
<body>
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">🌙</button>
    <script>
        (function () {
            var btn  = document.getElementById('theme-toggle');
            var html = document.documentElement;

            function getTheme() {
                var t = html.getAttribute('data-theme');
                if (t === 'dark' || t === 'light') return t;
                try { return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'; } catch (e) { return 'dark'; }
            }

            function updateBtn() {
                btn.textContent = getTheme() === 'dark' ? '☀️' : '🌙';
            }

            updateBtn();

            btn.addEventListener('click', function () {
                var next = getTheme() === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                updateBtn();
            });

            // Keep in sync if the OS preference changes while page is open
            try {
                window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function () {
                    if (!html.getAttribute('data-theme')) updateBtn();
                });
            } catch (e) {}
        })();
    </script>

    <div class="container">
        <h1>📸 AlertStream Snapshots</h1>

        @if(session('alertstream_deleted'))
        <div class="deleted-notice">✓ Snapshot deleted successfully.</div>
        @endif

        <form method="GET" class="filters" id="filter-form">
            <input type="text" name="search" id="search-input" placeholder="Search by title, class, or message…" value="{{ $search ?? '' }}">
            <select name="severity" id="severity-select">
                <option value="">All severities</option>
                <option value="critical" @if(($severity ?? '') === 'critical') selected @endif>Critical</option>
                <option value="error" @if(($severity ?? '') === 'error') selected @endif>Error</option>
                <option value="warning" @if(($severity ?? '') === 'warning') selected @endif>Warning</option>
            </select>
        </form>

        <script>
            (function () {
                var form   = document.getElementById('filter-form');
                var search = document.getElementById('search-input');
                var select = document.getElementById('severity-select');
                var timer;

                select.addEventListener('change', function () { form.submit(); });

                search.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () { form.submit(); }, 400);
                });
            })();
        </script>

        @if($snapshots->isEmpty())
            <div class="card empty">No snapshots found.</div>
        @else
            <div class="card" style="padding: 0; overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Title</th>
                            <th>File</th>
                            <th>When</th>
                            <th>Hits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($snapshots as $snap)
                        @php
                            $sev = $snap->context['severity'] ?? 'error';
                            $badge = match($sev) { 'critical' => 'badge-critical', 'warning' => 'badge-warning', default => 'badge-error' };
                        @endphp
                        <tr>
                            <td><span class="badge {{ $badge }}">{{ $sev }}</span></td>
                            <td><a href="{{ route('alertstream.snapshots.show', $snap->hash) }}">{{ Str::limit($snap->title, 50) }}</a></td>
                            <td class="td-mono">{{ basename($snap->file) }}:{{ $snap->line }}</td>
                            <td style="white-space: nowrap;">{{ $snap->created_at?->diffForHumans() }}</td>
                            <td>
                                @if($snap->occurrences > 1)
                                    <span class="occ">{{ $snap->occurrences }}x</span>
                                @else
                                    <span class="occ">1</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                @if($snapshots->onFirstPage())
                    <span>← Prev</span>
                @else
                    <a href="{{ $snapshots->previousPageUrl() }}">← Prev</a>
                @endif

                @foreach($snapshots->getUrlRange(1, $snapshots->lastPage()) as $page => $url)
                    @if($page == $snapshots->currentPage())
                        <span class="current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($snapshots->hasMorePages())
                    <a href="{{ $snapshots->nextPageUrl() }}">Next →</a>
                @else
                    <span>Next →</span>
                @endif
            </div>
        @endif
    </div>
</body>
</html>

