<div class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow">
    @php
        $url = route('qr.scan', ['token' => $getRecord()->token]);
        $qrCode = \Endroid\QrCode\Builder\Builder::create()
            ->data($url)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
            ->size(400)
            ->margin(10)
            ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
            ->build();
    @endphp
    <button 
        type="button" 
        onclick="const printContent = document.getElementById('qr-to-print-{{ $getRecord()->id }}').innerHTML; const originalContents = document.body.innerHTML; document.body.innerHTML = printContent; window.print(); document.body.innerHTML = originalContents; window.location.reload();"
        class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition"
    >
        Print QR Code
    </button>

    <div id="qr-to-print-{{ $getRecord()->id }}" class="hidden">
        <style>
            @media print {
                @page {
                    size: 800px 1280px; /* Approximate 8-inch tablet ratio */
                    margin: 0;
                }
                body {
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    background-color: #fffbeb !important; /* Amber-50 */
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .print-container {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    padding: 2rem;
                    background-color: #fffbeb !important; /* Amber-50 */
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .logo {
                    width: 300px; /* Adjust based on logo aspect ratio */
                    margin-bottom: 2rem;
                }
                .qr-image {
                    width: 500px;
                    height: 500px;
                    margin-bottom: 1rem;
                }
                .code-text {
                    font-size: 40px;
                    font-weight: bold;
                    margin-top: 1rem;
                    color: #333;
                }
                .url-text {
                    font-size: 18px;
                    color: #666;
                    margin-top: 0.5rem;
                }
            }
        </style>
        <div class="print-container">
            <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="logo" />
            <img src="{{ $qrCode->getDataUri() }}" alt="QR Code" class="qr-image" />
            <div class="code-text">{{ $getRecord()->unique_code }}</div>
            <div class="url-text">{{ $url }}</div>
        </div>
    </div>
</div>
