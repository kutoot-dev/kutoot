<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Print QR Codes</title>
    <style>
        :root {
            --sticker-width: {{ $width ?? 38 }}mm;
            --sticker-height: {{ $height ?? 25 }}mm;
            --sticker-margin: {{ $margin ?? 2 }}mm;
        }

        @media print {
            @page {
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
                background-color: transparent !important;
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

        /* The container holding the printable grid */
        .page-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
            justify-content: flex-start;
            align-content: flex-start;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 10mm;
            max-width: {{ $layout === '3-across' ? 'calc((var(--sticker-width) * 3) + 25mm)' : 'calc(var(--sticker-width) + 20mm)' }};
        }

        /* A single sticker container */
        .sticker-card {
            width: var(--sticker-width);
            height: var(--sticker-height);
            margin: var(--sticker-margin);
            border: 1px dotted #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #fffbeb !important; /* Amber-50 */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            overflow: hidden;
            position: relative;
        }

        .logo {
            width: 40%;
            margin-bottom: 2mm;
        }

        .qr-image {
            width: 60%;
            max-height: 60%;
            object-fit: contain;
        }

        .code-text {
            font-size: 8px;
            font-weight: bold;
            margin-top: 1mm;
            color: #333;
            white-space: nowrap;
        }

        .url-text {
            font-size: 5px;
            color: #666;
            margin-top: 0.5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
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
            max-width: 800px;
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
            <span style="margin-right: 15px; font-size: 14px; color: #4b5563;">Stickers: {{ count($qrCodes) }} | Layout: {{ $layout }} | Size: {{ $width }}x{{ $height }}mm</span>
            <button class="print-btn" onclick="window.print()">Print Now</button>
        </div>
    </div>

    <!-- The actual print block -->
    <div class="page-container">
        @foreach($qrCodes as $record)
            @php
                $url = route('qr.scan', ['token' => $record->token]);
                $qrCode = \Endroid\QrCode\Builder\Builder::create()
                    ->data($url)
                    ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
                    ->size(200) /* Higher internal resolution */
                    ->margin(0)
                    ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
                    ->build();
            @endphp
            <div class="sticker-card">
                <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="logo" />
                <img src="{{ $qrCode->getDataUri() }}" alt="QR Code" class="qr-image" />
                <div class="code-text">{{ $record->unique_code }}</div>
            </div>
        @endforeach
    </div>

</body>
</html>
