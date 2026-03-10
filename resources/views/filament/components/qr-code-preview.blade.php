<div class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow">
    @php
        $url = route('qr.scan', ['token' => $getRecord()->token]);
        $logoPath = public_path('images/kutoot-logo-initial.png');
        $builder = \Endroid\QrCode\Builder\Builder::create()
            ->data($url)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
            ->size(400)
            ->margin(10)
            ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
            // use orange modules instead of black
            ->foregroundColor(new \Endroid\QrCode\Color\Color(242, 106, 27))
            ->backgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
        if (file_exists($logoPath)) {
            $builder = $builder
                ->logoPath($logoPath)
                ->logoResizeToWidth(80)
                ->logoResizeToHeight(80)
                ->logoPunchoutBackground(true);
        }
        $qrCode = $builder->build();
        $qrDataUri = $qrCode->getDataUri();
    @endphp
    <button
        type="button"
        onclick="const printContent = document.getElementById('qr-to-print-{{ $getRecord()->id }}').innerHTML; const originalContents = document.body.innerHTML; document.body.innerHTML = printContent; window.print(); document.body.innerHTML = originalContents; window.location.reload();"
        class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition"
    >
        Print QR Code
    </button>
    <div style="position: relative; width: 100%; max-width: 500px; aspect-ratio: 2/3; background: url('{{ asset('images/qr-background.png') }}') no-repeat center/contain; background-size: contain; margin-top: 12px;">
        <!-- QR code centered over the placeholder in the background image -->
        <img src="{{ $qrDataUri }}" alt="QR Code" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width: 192px; height: 192px;" />
        <div style="position:absolute; bottom:8px; left:50%; transform:translateX(-50%); font-size: 14px; font-weight:bold; color:#1f2937;">{{ $getRecord()->unique_code }}</div>
    </div>

    <div id="qr-to-print-{{ $getRecord()->id }}" class="hidden">
        <style>
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }
                body {
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    background-color: #fff !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .print-page {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 12mm;
                }
                .print-card {
                    width: 80mm;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    padding: 8mm;
                    background-color: #fffbeb !important;
                    border: 1px solid #fde68a;
                    border-radius: 6mm;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .logo {
                    width: 100%;
                    max-width: 70mm;
                    margin-bottom: 5mm;
                }
                .qr-image {
                    width: 55mm;
                    height: 55mm;
                    margin-bottom: 3mm;
                    border: 2px solid white;
                    border-radius: 4mm;
                }
                .code-text {
                    font-size: 18px;
                    font-weight: bold;
                    margin-top: 3mm;
                    color: #333;
                }
                .url-text {
                    font-size: 9px;
                    color: #666;
                    margin-top: 2mm;
                    word-break: break-all;
                }
            }
        </style>
        <div class="print-page">
            <div class="print-card" style="position:relative; background: url('{{ asset('images/qr-background.png') }}') no-repeat center/contain; background-size: contain;">
                <img src="{{ $qrDataUri }}" alt="QR Code" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:50mm; height:50mm;" />
                <div class="code-text" style="position:absolute; bottom:5mm; left:50%; transform:translateX(-50%);">{{ $getRecord()->unique_code }}</div>
            </div>
        </div>
    </div>
</div>
