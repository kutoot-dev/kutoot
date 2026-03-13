<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Print QR Codes</title>
    @php
        $bgUrl = \App\Services\QrBackgroundService::getBackgroundUrl();
        $widthIn = \App\Services\SettingService::get('qr_print_width_in', 4);
        $heightIn = \App\Services\SettingService::get('qr_print_height_in', 6);
        /* Slightly reduce height to prevent overflow; small reduction = larger sticker */
        $safeHeightIn = max(3, $heightIn - 0.01);
    @endphp
    <style>
        @media print {
            @page {
                size: {{ $widthIn }}in {{ $heightIn }}in;
                margin: 0;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            /* 1 sticker = 1 page */
            .sticker-page {
                page-break-after: always;
                break-after: page;
            }
            .sticker-page:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }

        /* Vertical stack of stickers */
        .page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            margin: 0 auto;
            max-width: {{ $widthIn }}in;
        }

        .sticker-page {
            width: {{ $widthIn }}in;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .sticker-card {
            width: {{ $widthIn }}in;
            height: {{ $safeHeightIn }}in;
            position: relative;
            padding-top: 0.18in;
            box-sizing: border-box;
            background-image: url('{{ $bgUrl }}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            break-inside: avoid;
        }

        .sticker-card .qr-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            height: auto;
            border-radius: 12px;
        }

        .sticker-card .code-text {
            position: absolute;
            bottom: 26%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.70rem;
            font-weight: 800;
            color: #1f2937;
            white-space: nowrap;
        }

        .toolbar {
            background: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        button.print-btn {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
        }

        button.print-btn:hover {
            background-color: #4338ca;
        }

        .back-btn {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
        }
        .back-btn:hover {
            color: #111827;
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <a href="javascript:history.back()" class="back-btn">&larr; Back to QR Codes</a>
        <div>
            <span style="margin-right: 15px; font-size: 14px; color: #4b5563;">Stickers: {{ count($qrCodes) }} | {{ $widthIn }}" &times; {{ $heightIn }}" | Pages: {{ count($qrCodes) }}</span>
            <button class="print-btn" onclick="window.print()">Print Now</button>
        </div>
        <div class="no-print" style="font-size: 11px; color: #6b7280; margin-top: 4px;">Tip: In print dialog, disable "Headers and footers" for best results.</div>
    </div>

    <div class="page-container">
        @foreach($qrCodes as $record)
            @php
                $url = route('qr.scan', ['token' => $record->token]);
                $dataUri = \App\Services\QrCodeBuilder::buildForUrl($url, 360);
            @endphp
            <div class="sticker-page">
                <div class="sticker-card">
                    <img src="{{ $dataUri }}" alt="QR Code" class="qr-image" />
                    <div class="code-text">{{ $record->unique_code }}</div>
                </div>
            </div>
        @endforeach
    </div>

</body>
</html>
