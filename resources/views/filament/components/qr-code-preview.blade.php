<div class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow">
    @php
        $url = route('qr.scan', ['token' => $getRecord()->token]);
        $logoPath = public_path('images/kutoot-full-logo.png');
        $builder = \Endroid\QrCode\Builder\Builder::create()
            ->data($url)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
            ->size(400)
            ->margin(10)
            ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin);
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
    <div style="display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; width: 100%; margin-top: 12px;">
        <div style="width: 100%; max-width: 500px; padding: 16px; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
            <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" style="margin-bottom: 16px; width: 100%; max-width: 300px;" />
            <img src="{{ $qrDataUri }}" alt="QR Code" style="margin-bottom: 12px; width: 192px; height: 192px; border: 4px solid white; border-radius: 8px;" />
            <div style="font-size: 18px; font-weight: bold; color: #1f2937; margin-top: 8px;">{{ $getRecord()->unique_code }}</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px; word-break: break-all;">{{ $url }}</div>
        </div>
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
            <div class="print-card">
                <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="logo" />
                <img src="{{ $qrDataUri }}" alt="QR Code" class="qr-image" />
                <div class="code-text">{{ $getRecord()->unique_code }}</div>
                <div class="url-text">{{ $url }}</div>
            </div>
        </div>
    </div>
</div>
