<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center p-4 bg-gray-100 rounded-lg shadow-sm">
        @php
            $url = route('qr.scan', ['token' => $record->token]);
            $qrCode = \Endroid\QrCode\Builder\Builder::create()
                ->data($url)
                ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
                ->size(400)
                ->margin(10)
                ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
                ->build();
            $dataUri = $qrCode->getDataUri();
        @endphp

        <div class="mb-4">
            <x-filament::button
                size="lg"
                icon="heroicon-m-printer"
                onclick="printQrCode()"
            >
                Print QR Code
            </x-filament::button>
        </div>

        {{-- Visible Preview (replicating print layout) --}}
        <div class="p-8 bg-amber-50 border border-amber-200 rounded-xl shadow-lg flex flex-col items-center justify-center text-center" style="width: 400px; max-width: 100%;">
            <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="mb-8 w-48" />
            <img src="{{ $dataUri }}" alt="QR Code" class="mb-4 w-64 h-64 border-4 border-white rounded-lg shadow-sm" />
            <div class="text-3xl font-bold text-gray-800 mt-4">{{ $record->unique_code }}</div>
            <div class="text-sm text-gray-500 mt-2">{{ $url }}</div>
        </div>

        {{-- Hidden Print Template --}}
        <div id="qr-print-template" class="hidden">
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
                        font-family: 'Inter', sans-serif; /* Setup appropriate font */
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
                    }
                    .logo {
                        width: 400px;
                        margin-bottom: 3rem;
                    }
                    .qr-image {
                        width: 500px;
                        height: 500px;
                        margin-bottom: 2rem;
                        border: 10px solid white;
                        border-radius: 20px;
                    }
                    .code-text {
                        font-size: 60px;
                        font-weight: 800; /* Bold */
                        margin-top: 2rem;
                        color: #1f2937; /* Gray-800 */
                    }
                    .url-text {
                        font-size: 24px;
                        color: #6b7280; /* Gray-500 */
                        margin-top: 1rem;
                    }
                }
            </style>
            <div class="print-container">
                <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="logo" />
                <img src="{{ $dataUri }}" alt="QR Code" class="qr-image" />
                <div class="code-text">{{ $record->unique_code }}</div>
                <div class="url-text">{{ $url }}</div>
            </div>
        </div>

        <script>
            function printQrCode() {
                const printContent = document.getElementById('qr-print-template').innerHTML;
                const originalContents = document.body.innerHTML;
                
                document.body.innerHTML = printContent;
                
                window.print();
                
                // Restore content and reload to retain event listeners/state
                document.body.innerHTML = originalContents;
                window.location.reload();
            }
        </script>
    </div>
</x-filament-panels::page>
