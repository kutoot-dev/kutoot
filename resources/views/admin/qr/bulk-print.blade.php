<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Print QR Codes</title>
    @php
        $bgUrl = asset('images/qr-background.png');
    @endphp
    <style>
        @media print {
            @page {
                size: {{ \App\Services\SettingService::get('qr_print_width_in', 4) }}in {{ \App\Services\SettingService::get('qr_print_height_in', 6) }}in;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }

        /* Vertical stack of 4x6 stickers */
        .page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            margin: 0 auto;
            max-width: 4in;
        }

        .sticker-card {
            width: 4in;
            height: 6in;
            position: relative;
            background-image: url('{{ $bgUrl }}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            box-sizing: border-box;
            break-inside: avoid;
        }

        .sticker-card .qr-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50%;
            height: auto;
            border-radius: 12px;
        }

        .sticker-card .code-text {
            position: absolute;
            bottom: 16%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.25rem;
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
            <span style="margin-right: 15px; font-size: 14px; color: #4b5563;">Stickers: {{ count($qrCodes) }} | 4" &times; 6" vertical</span>
            <button class="print-btn" onclick="window.print()">Print Now</button>
        </div>
    </div>

    <div class="page-container">
        @foreach($qrCodes as $record)
            @php
                $url = route('qr.scan', ['token' => $record->token]);
                $dataUri = \App\Services\QrCodeBuilder::buildForUrl($url, 360);
            @endphp
            <div class="sticker-card">
                <img src="{{ $dataUri }}" alt="QR Code" class="qr-image" />
                <div class="code-text">{{ $record->unique_code }}</div>
            </div>
        @endforeach
    </div>

</body>
</html>
