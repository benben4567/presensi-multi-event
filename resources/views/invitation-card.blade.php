<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Undangan — {{ $event->name }}</title>
    <style>
        @page {
            size: 80mm 105mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 80mm;
            height: 105mm;
            overflow: hidden;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #fff;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        /* ── Card shell ──────────────────────────────────── */
        .card {
            width: 80mm;
            height: 105mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Header strip ────────────────────────────────── */
        .header {
            flex: 0 0 13mm;
            background: linear-gradient(135deg, #2563EB 0%, #1E3A8A 100%);
            display: flex;
            align-items: center;
            padding: 0 5mm;
        }

        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 1mm 3.5mm;
            border-radius: 20px;
            border: 0.35mm solid rgba(255, 255, 255, 0.45);
        }

        /* ── Body ────────────────────────────────────────── */
        .body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2.5mm;
            padding: 3.5mm 5mm;
        }

        /* Event title */
        .event-title {
            font-size: 11pt;
            font-weight: 700;
            color: #111827;
            text-align: center;
            line-height: 1.25;
            width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        /* Thin divider */
        .divider {
            width: 85%;
            height: 0.25mm;
            background: #E5E7EB;
            flex-shrink: 0;
        }

        /* ── QR Box ──────────────────────────────────────── */
        .qr-box {
            flex-shrink: 0;
            background: #fff;
            border: 0.4mm solid #D1D5DB;
            border-radius: 3mm;
            padding: 2.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0.5mm 3mm rgba(0, 0, 0, 0.10);
        }

        .qr-box svg {
            display: block;
            width: 38mm !important;
            height: 38mm !important;
        }

        /* ── Participant info ─────────────────────────────── */
        .participant-name {
            font-size: 12pt;
            font-weight: 700;
            color: #1F2937;
            text-align: center;
            line-height: 1.2;
            width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .participant-phone {
            font-size: 9pt;
            color: #4B5563;
            text-align: center;
            font-family: 'Courier New', 'Lucida Console', monospace;
            letter-spacing: 0.5px;
        }

        /* Caption */
        .caption {
            font-size: 7.5pt;
            color: #9CA3AF;
            text-align: center;
            font-style: italic;
        }

        /* ── Footer strip ────────────────────────────────── */
        .footer {
            flex: 0 0 10mm;
            background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.72);
            font-size: 6.5pt;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        @media print {
            html, body {
                background: transparent;
            }
        }
    </style>
</head>
<body>
    <div class="card">

        {{-- Header --}}
        <div class="header">
            <span class="badge">Peserta</span>
        </div>

        {{-- Body --}}
        <div class="body">

            <div class="event-title">{{ $event->name }}</div>

            <div class="divider"></div>

            <div class="qr-box">
                {!! $qrSvg !!}
            </div>

            <div class="divider"></div>

            <div class="participant-name">{{ $participant->name }}</div>

            @if($participant->phone_e164)
                <div class="participant-phone">{{ $participant->phone_e164 }}</div>
            @endif

            <div class="caption">Tunjukkan kartu ini saat check-in</div>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <span class="footer-text">Presensi Event System</span>
        </div>

    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
