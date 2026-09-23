@props([
    'code',
    'title',
    'message',
    'icon' => 'tabler-alert-triangle',
    'tone' => 'info', // info | warning | danger
    'reload' => false,
    'showHome' => true,
    'homeLabel' => 'Kembali ke Beranda',
    'homeHref' => null,
])

@php($homeHref ??= url('/'))

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ config('app.name', 'Presensi QR') }}</title>

    {{--
        Error pages are self-contained on purpose: no @vite, no external fonts,
        no Alpine/Livewire. A build failure, a broken manifest, or a down CDN
        is exactly the kind of thing that can cause a 500 — this page must
        still render when all of that is broken.
    --}}
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e5e7eb;
            --text: #0f172a;
            --text-muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-contrast: #ffffff;
            --tone-bg: #eff6ff;
            --tone-fg: #2563eb;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --surface: #1e293b;
                --border: #334155;
                --text: #f1f5f9;
                --text-muted: #94a3b8;
                --tone-bg: rgba(37, 99, 235, 0.15);
            }
        }
        [data-tone="warning"] { --tone-bg: #fffbeb; --tone-fg: #d97706; }
        [data-tone="danger"] { --tone-bg: #fef2f2; --tone-fg: #dc2626; }
        @media (prefers-color-scheme: dark) {
            [data-tone="warning"] { --tone-bg: rgba(217, 119, 6, 0.15); }
            [data-tone="danger"] { --tone-bg: rgba(220, 38, 38, 0.18); }
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Figtree, Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            text-align: center;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
        }
        .brand svg { width: 20px; height: 20px; color: var(--primary); }
        .icon-badge {
            width: 56px;
            height: 56px;
            margin: 0 auto 20px;
            border-radius: 9999px;
            background: var(--tone-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-badge svg { width: 28px; height: 28px; color: var(--tone-fg); }
        .code {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin: 0 0 8px;
        }
        h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 8px;
            color: var(--text);
        }
        p.message {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0 0 24px;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 150ms ease, border-color 150ms ease;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--primary-contrast);
        }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary {
            background: transparent;
            color: var(--text);
            border-color: var(--border);
        }
        .btn-secondary:hover { border-color: var(--text-muted); }
        .btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        @media (prefers-reduced-motion: reduce) {
            .btn { transition: none; }
        }
    </style>
</head>
<body>
    <div class="card" data-tone="{{ $tone }}">
        <div class="brand">
            <x-tabler-qrcode />
            <span>{{ config('app.name', 'Presensi QR') }}</span>
        </div>

        <div class="icon-badge">
            <x-dynamic-component :component="$icon" />
        </div>

        <p class="code">Error {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>

        <div class="actions">
            @if($reload)
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                    Muat Ulang Halaman
                </button>
            @endif

            @if($showHome)
                <a href="{{ $homeHref }}" class="btn {{ $reload ? 'btn-secondary' : 'btn-primary' }}">
                    {{ $homeLabel }}
                </a>
            @endif
        </div>
    </div>
</body>
</html>
