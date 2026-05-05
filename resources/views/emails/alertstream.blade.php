<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }} — AlertStream</title>
    <style>
        /*
         * Default = light design (matches the snapshot page light theme).
         * All inline styles use light-mode values.
         * Dark-mode overrides are applied only via the media query below,
         * for email clients that support @media (prefers-color-scheme: dark).
         *
         * Class map:
         *   .as-outer       — outer wrapper table + body background
         *   .as-hero        — white hero row (severity + title)
         *   .as-exception   — light-gray exception row
         *   .as-meta        — white meta/details row
         *   .as-cta         — light-blue CTA row
         *   .as-title       — h1 title colour
         *   .as-timestamp   — captured-at timestamp colour
         *   .as-sec-label   — section label (EXCEPTION / DETAILS)
         *   .as-class-mono  — exception class name
         *   .as-file-mono   — file:line
         *   .as-msg-box     — amber message box container
         *   .as-msg-text    — message text inside box
         *   .as-meta-label  — label column in details table
         *   .as-meta-value  — value column in details table
         *   .as-meta-mono   — monospace value column (request URL)
         *   .as-cta-desc    — description text above the button
         *   .as-snap-url    — plain-text snapshot URL below button
         *   .as-header      — header bar row
         *   .as-logo        — AlertStream wordmark
         *   .as-env-badge   — environment pill in header
         *   .as-footer      — footer bar row
         *   .as-footer-text — footer paragraph
         *   .as-footer-brand — "AlertStream" strong in footer
         */

        @media (prefers-color-scheme: dark) {
            /* ── Outer wrapper ────────────────────────────────────────── */
            body.as-outer,
            table.as-outer       { background-color: #0f172a !important; }

            /* ── Content rows ─────────────────────────────────────────── */
            td.as-hero           { background-color: #1e293b !important;
                                   border-color:      #334155 !important; }
            td.as-exception      { background-color: #0f172a !important;
                                   border-color:      #334155 !important; }
            td.as-meta           { background-color: #1e293b !important;
                                   border-color:      #334155 !important; }
            td.as-cta            { background-color: #0c1624 !important;
                                   border-color:      #1e3a5f !important; }

            /* ── Text ─────────────────────────────────────────────────── */
            h1.as-title          { color: #f1f5f9 !important; }
            span.as-timestamp    { color: #64748b !important; }
            p.as-sec-label       { color: #64748b !important; }
            p.as-class-mono      { color: #22d3ee !important; }
            p.as-file-mono       { color: #94a3b8 !important; }

            /* ── Message box ──────────────────────────────────────────── */
            div.as-msg-box       { background-color: #1c1917 !important;
                                   border-color:      #44403c !important; }
            span.as-msg-text     { color: #fbbf24 !important; }

            /* ── Details table ────────────────────────────────────────── */
            td.as-meta-label     { color: #64748b !important; }
            td.as-meta-value     { color: #f1f5f9 !important; }
            td.as-meta-mono      { color: #94a3b8 !important; }

            /* ── CTA ──────────────────────────────────────────────────── */
            p.as-cta-desc        { color: #94a3b8 !important; }
            p.as-snap-url        { color: #475569 !important; }

            /* ── Header bar ───────────────────────────────────────────── */
            td.as-header         { background-color: #0f172a !important;
                                   border-color:      #334155 !important; }
            span.as-logo         { color: #f1f5f9 !important; }
            span.as-env-badge    { background-color: #1e293b !important;
                                   color:             #94a3b8 !important;
                                   border-color:      #334155 !important; }

            /* ── Footer bar ───────────────────────────────────────────── */
            td.as-footer         { background-color: #0f172a !important;
                                   border-color:      #334155 !important; }
            p.as-footer-text     { color: #475569 !important; }
            strong.as-footer-brand { color: #64748b !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;" class="as-outer">

{{-- Outer wrapper --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color:#f1f5f9;" class="as-outer">
<tr><td align="center" style="padding:28px 16px;">

    {{-- 600 px email container --}}
    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        {{-- ── Header bar ──────────────────────────────────────────────── --}}
        <tr>
            <td class="as-header"
                style="background-color:#ffffff;border-top:1px solid #e2e8f0;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;border-radius:12px 12px 0 0;padding:18px 28px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td>
                            <span class="as-logo"
                                  style="font-size:17px;font-weight:700;color:#0f172a;letter-spacing:-0.01em;">⚡ AlertStream</span>
                        </td>
                        <td align="right">
                            <span class="as-env-badge"
                                  style="display:inline-block;background-color:#f1f5f9;color:#475569;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;">{{ $env }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- ── Hero: severity badge + title ──────────────────────────── --}}
        <tr>
            <td class="as-hero"
                style="background-color:#ffffff;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;padding:24px 28px 20px;">
                @php
                    $badgeStyle = match($severity) {
                        'critical' => 'background-color:#991b1b;color:#fecaca;',
                        'warning'  => 'background-color:#854d0e;color:#fef08a;',
                        default    => 'background-color:#9a3412;color:#fed7aa;',
                    };
                @endphp
                <div style="margin-bottom:12px;">
                    <span style="{{ $badgeStyle }}display:inline-block;padding:3px 12px;border-radius:9999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">{{ $severity }}</span>
                    <span class="as-timestamp"
                          style="font-size:12px;color:#94a3b8;margin-left:10px;">{{ $timestamp }}</span>
                </div>
                <h1 class="as-title"
                    style="margin:0;font-size:20px;font-weight:700;color:#0f172a;line-height:1.4;word-break:break-word;">{{ $title }}</h1>
            </td>
        </tr>

        {{-- ── Exception details ───────────────────────────────────────── --}}
        <tr>
            <td class="as-exception"
                style="background-color:#f8fafc;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;border-top:1px solid #e2e8f0;padding:20px 28px;">

                <p class="as-sec-label"
                   style="margin:0 0 10px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:#64748b;">Exception</p>

                <p class="as-class-mono"
                   style="margin:0 0 4px;font-family:ui-monospace,'SF Mono',Menlo,Consolas,monospace;font-size:13px;color:#0e7490;word-break:break-all;">{{ $exceptionClass }}</p>

                <p class="as-file-mono"
                   style="margin:0 0 14px;font-size:12px;font-family:ui-monospace,'SF Mono',Menlo,Consolas,monospace;color:#64748b;word-break:break-all;">{{ $file }}:{{ $line }}</p>

                <div class="as-msg-box"
                     style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;">
                    <span class="as-msg-text"
                          style="font-size:14px;color:#92400e;word-break:break-word;display:block;white-space:pre-wrap;">{{ $message }}</span>
                </div>
            </td>
        </tr>

        {{-- ── Meta details ────────────────────────────────────────────── --}}
        <tr>
            <td class="as-meta"
                style="background-color:#ffffff;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;border-top:1px solid #e2e8f0;padding:20px 28px;">

                <p class="as-sec-label"
                   style="margin:0 0 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:#64748b;">Details</p>

                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="as-meta-label"
                            style="width:32%;padding:5px 0;font-size:12px;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;vertical-align:top;">Environment</td>
                        <td class="as-meta-value"
                            style="padding:5px 0;font-size:13px;color:#1e293b;">{{ $env }}</td>
                    </tr>
                    <tr>
                        <td class="as-meta-label"
                            style="width:32%;padding:5px 0;font-size:12px;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;vertical-align:top;">Captured at</td>
                        <td class="as-meta-value"
                            style="padding:5px 0;font-size:13px;color:#1e293b;">{{ $timestamp }}</td>
                    </tr>
                    @if (!empty($context['url']))
                    <tr>
                        <td class="as-meta-label"
                            style="width:32%;padding:5px 0;font-size:12px;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;vertical-align:top;">Request</td>
                        <td class="as-meta-mono"
                            style="padding:5px 0;font-size:12px;font-family:ui-monospace,'SF Mono',Menlo,Consolas,monospace;color:#1e293b;word-break:break-all;">{{ ($context['method'] ?? 'GET') }} {{ $context['url'] }}</td>
                    </tr>
                    @endif
                    @if (!empty($context['user_id']))
                    <tr>
                        <td class="as-meta-label"
                            style="width:32%;padding:5px 0;font-size:12px;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;vertical-align:top;">User ID</td>
                        <td class="as-meta-value"
                            style="padding:5px 0;font-size:13px;color:#1e293b;">{{ $context['user_id'] }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>

        {{-- ── Snapshot CTA (only when a snapshot URL is available) ─────── --}}
        @if ($snapshotUrl)
        <tr>
            <td class="as-cta"
                style="background-color:#eff6ff;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;border-top:1px solid #bfdbfe;padding:22px 28px;text-align:center;">
                <p class="as-cta-desc"
                   style="margin:0 0 16px;font-size:13px;color:#475569;line-height:1.6;">View the full stack trace, code snippets, and request context in AlertStream:</p>
                <a href="{{ $snapshotUrl }}"
                   style="display:inline-block;background-color:#3b82f6;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;letter-spacing:0.01em;">
                    View Snapshot &rarr;
                </a>
                <p class="as-snap-url"
                   style="margin:16px 0 0;font-size:11px;color:#94a3b8;word-break:break-all;">{{ $snapshotUrl }}</p>
            </td>
        </tr>
        @endif

        {{-- ── Footer bar ──────────────────────────────────────────────── --}}
        <tr>
            <td class="as-footer"
                style="background-color:#f8fafc;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;border-radius:0 0 12px 12px;padding:16px 28px;text-align:center;">
                <p class="as-footer-text"
                   style="margin:0;font-size:11px;color:#94a3b8;line-height:1.5;">
                    Sent by <strong class="as-footer-brand" style="color:#64748b;">AlertStream</strong>
                    &mdash; You are receiving this because this address is configured as an alert recipient.
                </p>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>

