<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $snapshot->title }} — AlertStream</title>
    <style>
        /* ── Tokens: dark defaults ────────────────────────────────────── */
        :root {
            --bg:               #0f172a;
            --surface:          #1e293b;
            --border:           #334155;
            --text:             #e2e8f0;
            --text-muted:       #94a3b8;
            --text-dim:         #64748b;
            --text-dimmer:      #475569;
            --meta-dd:          #f1f5f9;
            --color-file:       #22d3ee;
            --msg-bg:           #1c1917;
            --msg-border:       #44403c;
            --msg-text:         #fbbf24;
            --trace-bg:         #080706;
            --trace-divider:    #1c1917;
            --trace-open-bg:    #0f0d0c;
            --trace-meta-bg:    #0c0a09;
            --trace-arrow:      #44403c;
            --trace-num:        #44403c;
            --trace-code:       #78716c;
            --trace-gutter:     #1c1917;
            --trace-call-dim:   #57534e;
            --trace-fb-label:   #44403c;
            --trace-fb-call:    #d6d3d1;
            --hl-bg:            #1c1400;
            --hl-num:           #fb923c;
            --hl-code:          #fef3c7;
            --tbl-th-border:    #334155;
            --tbl-td-border:    #1e293b;
            --tbl-th-text:      #94a3b8;
            --tbl-td-text:      #f1f5f9;
            --notice-bg:        #14532d;
            --notice-border:    #166534;
            --notice-text:      #bbf7d0;
            --btn-del-bg:       #7f1d1d;
            --btn-del-text:     #fecaca;
            --btn-del-border:   #991b1b;
            --btn-del-hover:    #991b1b;
            --badge-occ-bg:     #1e3a5f;
            --badge-occ-text:   #93c5fd;
            --card-summary-color: #94a3b8;
            --card-summary-arrow: #475569;
            --toggle-bg:        #1e293b;
            --toggle-border:    #334155;
            --toggle-text:      #94a3b8;
        }

        /* ── Tokens: light via OS preference ─────────────────────────── */
        @media (prefers-color-scheme: light) {
            :root { --bg: #f1f5f9; --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #475569; --text-dim: #94a3b8; --text-dimmer: #94a3b8; --meta-dd: #1e293b; --color-file: #0e7490; --msg-bg: #fffbeb; --msg-border: #fde68a; --msg-text: #92400e; --trace-bg: #f8fafc; --trace-divider: #e2e8f0; --trace-open-bg: #f1f5f9; --trace-meta-bg: #f8fafc; --trace-arrow: #cbd5e1; --trace-num: #cbd5e1; --trace-code: #64748b; --trace-gutter: #e2e8f0; --trace-call-dim: #94a3b8; --trace-fb-label: #94a3b8; --trace-fb-call: #334155; --hl-bg: #fef9c3; --hl-num: #d97706; --hl-code: #78350f; --tbl-th-border: #e2e8f0; --tbl-td-border: #f1f5f9; --tbl-th-text: #64748b; --tbl-td-text: #1e293b; --notice-bg: #f0fdf4; --notice-border: #bbf7d0; --notice-text: #166534; --btn-del-bg: #fee2e2; --btn-del-text: #991b1b; --btn-del-border: #fca5a5; --btn-del-hover: #fecaca; --badge-occ-bg: #dbeafe; --badge-occ-text: #1d4ed8; --card-summary-color: #475569; --card-summary-arrow: #94a3b8; --toggle-bg: #ffffff; --toggle-border: #e2e8f0; --toggle-text: #475569; }
        }

        /* ── Explicit overrides — MUST come after the media query ─────── */
        /* Same specificity as :root but later in the sheet → always wins  */
        [data-theme="dark"]  { --bg: #0f172a; --surface: #1e293b; --border: #334155; --text: #e2e8f0; --text-muted: #94a3b8; --text-dim: #64748b; --text-dimmer: #475569; --meta-dd: #f1f5f9; --color-file: #22d3ee; --msg-bg: #1c1917; --msg-border: #44403c; --msg-text: #fbbf24; --trace-bg: #080706; --trace-divider: #1c1917; --trace-open-bg: #0f0d0c; --trace-meta-bg: #0c0a09; --trace-arrow: #44403c; --trace-num: #44403c; --trace-code: #78716c; --trace-gutter: #1c1917; --trace-call-dim: #57534e; --trace-fb-label: #44403c; --trace-fb-call: #d6d3d1; --hl-bg: #1c1400; --hl-num: #fb923c; --hl-code: #fef3c7; --tbl-th-border: #334155; --tbl-td-border: #1e293b; --tbl-th-text: #94a3b8; --tbl-td-text: #f1f5f9; --notice-bg: #14532d; --notice-border: #166534; --notice-text: #bbf7d0; --btn-del-bg: #7f1d1d; --btn-del-text: #fecaca; --btn-del-border: #991b1b; --btn-del-hover: #991b1b; --badge-occ-bg: #1e3a5f; --badge-occ-text: #93c5fd; --card-summary-color: #94a3b8; --card-summary-arrow: #475569; --toggle-bg: #1e293b; --toggle-border: #334155; --toggle-text: #94a3b8; }
        [data-theme="light"] { --bg: #f1f5f9; --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #475569; --text-dim: #94a3b8; --text-dimmer: #94a3b8; --meta-dd: #1e293b; --color-file: #0e7490; --msg-bg: #fffbeb; --msg-border: #fde68a; --msg-text: #92400e; --trace-bg: #f8fafc; --trace-divider: #e2e8f0; --trace-open-bg: #f1f5f9; --trace-meta-bg: #f8fafc; --trace-arrow: #cbd5e1; --trace-num: #cbd5e1; --trace-code: #64748b; --trace-gutter: #e2e8f0; --trace-call-dim: #94a3b8; --trace-fb-label: #94a3b8; --trace-fb-call: #334155; --hl-bg: #fef9c3; --hl-num: #d97706; --hl-code: #78350f; --tbl-th-border: #e2e8f0; --tbl-td-border: #f1f5f9; --tbl-th-text: #64748b; --tbl-td-text: #1e293b; --notice-bg: #f0fdf4; --notice-border: #bbf7d0; --notice-text: #166534; --btn-del-bg: #fee2e2; --btn-del-text: #991b1b; --btn-del-border: #fca5a5; --btn-del-hover: #fecaca; --badge-occ-bg: #dbeafe; --badge-occ-text: #1d4ed8; --card-summary-color: #475569; --card-summary-arrow: #94a3b8; --toggle-bg: #ffffff; --toggle-border: #e2e8f0; --toggle-text: #475569; }

        /* ── Base ─────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; padding: 2rem 1rem; }
        .container { max-width: 960px; margin: 0 auto; }

        /* ── Theme toggle ─────────────────────────────────────────────── */
        .theme-toggle { position: fixed; top: 1rem; right: 1rem; background: var(--toggle-bg); border: 1px solid var(--toggle-border); color: var(--toggle-text); border-radius: 0.5rem; padding: 0.6rem 1.1rem; font-size: 0.875rem; font-family: ui-sans-serif, system-ui, sans-serif; font-weight: 500; cursor: pointer; line-height: 1.4; z-index: 100; display: inline-flex; align-items: center; gap: 0.4rem; transition: border-color 0.15s, box-shadow 0.15s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .theme-toggle:hover { border-color: var(--text-muted); box-shadow: 0 2px 6px rgba(0,0,0,0.4); }

        /* ── Badges ───────────────────────────────────────────────────── */
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-critical { background: #991b1b; color: #fecaca; }
        .badge-error    { background: #9a3412; color: #fed7aa; }
        .badge-warning  { background: #854d0e; color: #fef08a; }
        .badge-occ      { background: var(--badge-occ-bg); color: var(--badge-occ-text); }

        /* ── Card ─────────────────────────────────────────────────────── */
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; word-break: break-word; }

        /* ── Meta grid ────────────────────────────────────────────────── */
        .meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .meta dt { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .meta dd { font-size: 0.875rem; color: var(--meta-dd); word-break: break-all; }

        /* ── Helpers ──────────────────────────────────────────────────── */
        .text-dim   { font-size: 0.75rem; color: var(--text-dim); }
        .color-line { color: var(--hl-num); }

        /* ── Exception message ────────────────────────────────────────── */
        .message { font-size: 1rem; color: var(--msg-text); background: var(--msg-bg); border: 1px solid var(--msg-border); border-radius: 0.5rem; padding: 1rem; white-space: pre-wrap; word-break: break-word; }

        /* ── Trace list ───────────────────────────────────────────────── */
        .trace-list { list-style: none; background: var(--trace-bg); border-radius: 0.5rem; overflow: hidden; font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace; font-size: 0.8rem; }
        .trace-frame { border-bottom: 1px solid var(--trace-divider); }
        .trace-frame:last-child { border-bottom: none; }
        .trace-frame > summary { display: flex; align-items: baseline; gap: 0.6rem; padding: 0.6rem 1rem; cursor: pointer; list-style: none; user-select: none; white-space: nowrap; overflow: hidden; line-height: 1.5; }
        .trace-frame > summary::-webkit-details-marker { display: none; }
        .trace-frame > summary::before { content: '▶'; font-size: 0.55rem; color: var(--trace-arrow); transition: transform 0.15s; display: inline-block; flex-shrink: 0; }
        .trace-frame[open] > summary::before { transform: rotate(90deg); }
        .trace-frame[open] > summary { border-bottom: 1px solid var(--trace-divider); background: var(--trace-open-bg); }
        .trace-frame .frame-num        { color: #6366f1; font-weight: 700; flex-shrink: 0; }
        .trace-frame .frame-short      { color: var(--color-file); flex-shrink: 0; }
        .trace-frame .frame-call-short { color: var(--trace-call-dim); overflow: hidden; text-overflow: ellipsis; min-width: 0; }
        .trace-frame .frame-meta { padding: 0.6rem 1rem; display: grid; grid-template-columns: max-content 1fr; gap: 0.25rem 1rem; line-height: 1.6; border-bottom: 1px solid var(--trace-divider); background: var(--trace-meta-bg); }
        .trace-frame .fb-label { color: var(--trace-fb-label); white-space: nowrap; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .trace-frame .fb-file  { color: var(--color-file); word-break: break-all; }
        .trace-frame .fb-call  { color: var(--trace-fb-call); word-break: break-all; }

        /* ── Code snippet ─────────────────────────────────────────────── */
        .trace-snippet { overflow-x: auto; line-height: 1.6; }
        .trace-snippet table { border-collapse: collapse; width: 100%; }
        .trace-snippet td { padding: 0 0.5rem; white-space: pre; vertical-align: top; }
        .trace-snippet .sn-num  { color: var(--trace-num); text-align: right; user-select: none; padding-right: 1rem; min-width: 3.5rem; border-right: 2px solid var(--trace-gutter); }
        .trace-snippet .sn-code { color: var(--trace-code); padding-left: 1rem; }
        .trace-snippet tr.sn-highlight           { background: var(--hl-bg); }
        .trace-snippet tr.sn-highlight .sn-num   { color: var(--hl-num); border-right-color: var(--hl-num); }
        .trace-snippet tr.sn-highlight .sn-code  { color: var(--hl-code); }
        .trace-frame-plain { padding: 0.6rem 1rem; border-bottom: 1px solid var(--trace-divider); color: var(--trace-call-dim); }

        /* ── Context table ────────────────────────────────────────────── */
        .context-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .context-table th { text-align: left; padding: 0.5rem 0.75rem; color: var(--tbl-th-text); font-weight: 500; border-bottom: 1px solid var(--tbl-th-border); width: 30%; }
        .context-table td { padding: 0.5rem 0.75rem; color: var(--tbl-td-text); border-bottom: 1px solid var(--tbl-td-border); word-break: break-all; }

        /* ── Footer ───────────────────────────────────────────────────── */
        .footer { text-align: center; font-size: 0.75rem; color: var(--text-dimmer); padding-top: 2rem; }

        /* ── Delete button ────────────────────────────────────────────── */
        .btn-delete { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; background: var(--btn-del-bg); color: var(--btn-del-text); border: 1px solid var(--btn-del-border); border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: background 0.15s; }
        .btn-delete:hover { background: var(--btn-del-hover); }

        /* ── Deleted notice ───────────────────────────────────────────── */
        .deleted-notice { background: var(--notice-bg); border: 1px solid var(--notice-border); color: var(--notice-text); border-radius: 0.75rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }

        /* ── Collapsible card ─────────────────────────────────────────── */
        details.card { padding: 0; }
        details.card > summary { list-style: none; padding: 1rem 1.5rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--card-summary-color); user-select: none; border-radius: 0.75rem; }
        details.card > summary::-webkit-details-marker { display: none; }
        details.card > summary::before { content: '▶'; font-size: 0.6rem; color: var(--card-summary-arrow); transition: transform 0.15s; display: inline-block; }
        details.card[open] > summary::before { transform: rotate(90deg); }
        details.card[open] > summary { border-bottom: 1px solid var(--border); border-radius: 0.75rem 0.75rem 0 0; }
        details.card .card-body { padding: 1.5rem; }
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
        @if(session('alertstream_deleted'))
        <div class="deleted-notice">
            ✓ Snapshot deleted successfully.
        </div>
        @endif

        {{-- Header --}}
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    @php
                        $severity = $snapshot->context['severity'] ?? 'error';
                        $badgeClass = match($severity) {
                            'critical' => 'badge-critical',
                            'warning'  => 'badge-warning',
                            default    => 'badge-error',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $severity }}</span>
                    <span class="text-dim">{{ $snapshot->created_at?->format('M d, Y H:i:s T') }}</span>
                    @if($snapshot->occurrences > 1)
                    <span class="badge badge-occ">{{ $snapshot->occurrences }}x</span>
                    <span class="text-dim">Last seen: {{ $snapshot->last_seen_at?->format('M d, Y H:i:s T') }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('alertstream.snapshots.destroy', $snapshot->hash) }}" onsubmit="return confirm('Delete this snapshot? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-delete">🗑 Delete</button>
                </form>
            </div>
            <h1>{{ $snapshot->title }}</h1>
        </div>

        {{-- Exception Details --}}
        <div class="card">
            <div class="card-header">Exception</div>
            <div class="card-body" style="padding-top: 0;">
                <dl class="meta">
                    <div>
                        <dt>Class</dt>
                        <dd>{{ $snapshot->exception_class }}</dd>
                    </div>
                    <div>
                        <dt>File</dt>
                        <dd>{{ $snapshot->file }}:{{ $snapshot->line }}</dd>
                    </div>
                </dl>
                <div style="margin-top: 1rem;">
                    <div class="message">{{ $snapshot->exception_message }}</div>
                </div>
            </div>
        </div>

        {{-- Context --}}
        @if(!empty($snapshot->context))
        <div class="card">
            <div class="card-header">Context</div>
            <div style="padding: 0 1.5rem 1.5rem;">
                <table class="context-table">
                    <tbody>
                    @foreach($snapshot->context as $key => $value)
                        <tr>
                            <th>{{ $key }}</th>
                            <td>{{ is_array($value) || is_object($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Stack Trace --}}
        <details class="card" open>
            <summary>Stack Trace</summary>
            <div class="card-body" style="padding: 0;">
                <ul class="trace-list">
                @foreach($frames as $i => $frame)
                    @if($frame['file'])
                    <details class="trace-frame" {{ $i === 0 ? 'open' : '' }}>
                        <summary>
                            <span class="frame-num">{{ $frame['num'] }}</span>
                            <span class="frame-short">{{ basename($frame['file']) }}@if($frame['line'])<span class="color-line">:{{ $frame['line'] }}</span>@endif</span>
                            <span class="frame-call-short">{{ $frame['call'] }}</span>
                        </summary>
                        <div class="frame-body">
                            <div class="frame-meta">
                                <span class="fb-label">File</span>
                                <span class="fb-file">{{ $frame['file'] }}@if($frame['line']):<span class="color-line">{{ $frame['line'] }}</span>@endif</span>
                                @if($frame['call'])
                                <span class="fb-label">Call</span>
                                <span class="fb-call">{{ $frame['call'] }}</span>
                                @endif
                            </div>
                            @if($frame['snippet'])
                            <div class="trace-snippet">
                                <table>
                                @foreach($frame['snippet']['lines'] as $j => $codeLine)
                                @php $lineNo = $frame['snippet']['start'] + $j; @endphp
                                <tr class="{{ $lineNo === $frame['snippet']['highlight'] ? 'sn-highlight' : '' }}">
                                    <td class="sn-num">{{ $lineNo }}</td>
                                    <td class="sn-code">{{ $codeLine === '' ? ' ' : $codeLine }}</td>
                                </tr>
                                @endforeach
                                </table>
                            </div>
                            @endif
                        </div>
                    </details>
                    @else
                    <li class="trace-frame-plain">{{ $frame['call'] ?: $frame['raw'] }}</li>
                    @endif
                @endforeach
                </ul>
            </div>
        </details>

        <div class="footer">
            AlertStream Snapshot &middot; Hash: {{ $snapshot->hash }}
        </div>
    </div>
</body>
</html>

