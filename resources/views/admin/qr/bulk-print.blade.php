<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Print QR Codes - Kutoot</title>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            body {
                margin: 0;
                padding: 0;
                background-color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                box-shadow: none !important;
                background: transparent !important;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .page-container {
            width: 297mm;
            height: 210mm;
            background: white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 15mm;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 12mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            page-break-after: always;
            margin-bottom: 20px;
        }

        .page-container:last-child {
            page-break-after: auto;
            margin-bottom: 0;
        }

        /* Premium QR Sticker Card */
        .qr-card {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 12mm;
            background: linear-gradient(135deg, #fff8f5 0%, #fffbea 100%);
            border: 2px solid #f26a1b;
            border-radius: 16px;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(142, 0, 56, 0.08);
        }

        /* Decorative background elements */
        .qr-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 20% 50%, rgba(242, 106, 27, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(142, 0, 56, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }

        .qr-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 30%;
            height: 30%;
            background: linear-gradient(135deg, rgba(242, 106, 27, 0.08) 0%, transparent 70%);
            border-radius: 50% 0 0 0;
            pointer-events: none;
        }

        .card-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            gap: 6mm;
        }

        .logo-badge {
            width: 53mm;
            height: 20mm;
            padding: 3mm;
            border-radius: 12px;
            background: linear-gradient(135deg, #4e1f05 0%, #351703 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(242, 106, 27, 0.3);
        }

        .logo-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: brightness(1.1);
        }

        .qr-wrapper {
            position: relative;
            padding: 10mm;
            border-radius: 60px;
            background: white;
            border: 2px solid #f26a1b;
            box-shadow:
                0 4px 12px rgba(142, 0, 56, 0.15),
                inset 0 0 0 1px rgba(242, 106, 27, 0.1);
        }

        .qr-image {
            width: 50mm;
            height: 50mm;
            display: block;
            border-radius: 10px;
            object-fit: contain;
        }

        .code-text {
            font-size: 10px;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-family: 'Courier New', monospace;
            line-height: 1.2;
        }

        .brand-text {
            font-size: 9px;
            color: #5d290b;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .url-text {
            font-size: 6px;
            color: #6b7280;
            margin-top: 2mm;
            break-word: break-word;
            max-width: 100%;
            line-height: 1;
            display: none;
        }

        .toolbar {
            background: white;
            padding: 16px 24px;
            margin-bottom: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 297mm;
            margin-left: auto;
            margin-right: auto;
        }

        .back-btn {
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-btn:hover {
            color: #111827;
        }

        button.print-btn {
            background: linear-gradient(135deg, #f26a1b 0%, #8e0038 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(242, 106, 27, 0.3);
        }

        button.print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(242, 106, 27, 0.4);
        }

        .info-text {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

    </style>
</head>
<body>

    <div class="toolbar no-print">
        <a href="javascript:history.back()" class="back-btn">← Back to QR Codes</a>
        <div>
            <span class="info-text">{{ count($qrCodes) }} QR Codes | Layout: 3 per A4 Landscape | Premium Design</span>
        </div>
        <button class="print-btn" onclick="window.print()">🖨️ Print Stickers</button>
    </div>

    <!-- The actual print block -->
    @php
        $stickersPerPage = 3;
        $chunks = $qrCodes->chunk($stickersPerPage);
    @endphp
    @foreach($chunks as $chunk)
        <div class="page-container">
            @foreach($chunk as $record)
                @php
                    $url = route('qr.scan', ['token' => $record->token]);
                    $logoPath = public_path('images/kutoot-full-logo.png');
                    $builder = \Endroid\QrCode\Builder\Builder::create()
                        ->data($url)
                        ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                        ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
                        ->size(300)
                        ->margin(8)
                        ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
                        ->foregroundColor(new \Endroid\QrCode\Color\Color(31, 26, 46))
                        ->backgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
                    if (file_exists($logoPath)) {
                        $builder = $builder
                            ->logoPath($logoPath)
                            ->logoResizeToWidth(60)
                            ->logoResizeToHeight(60)
                            ->logoPunchoutBackground(true);
                    }
                    $qrCode = $builder->build();
                @endphp
                <div class="qr-card">
                    <div class="card-content">
                        <div class="logo-badge">
                            <img src="{{ asset('images/kutoot-full-logo.png') }}" alt="Kutoot" />
                        </div>
                        <div class="qr-wrapper">
                            <img src="{{ $qrCode->getDataUri() }}" alt="QR Code" class="qr-image" />
                        </div>
                        <div class="code-text">{{ $record->unique_code }}</div>
                        <div class="url-text">{{ $url }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

</body>
</html>
