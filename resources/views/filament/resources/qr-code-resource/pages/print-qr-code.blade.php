<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center p-4 bg-gray-100 rounded-lg shadow-sm">
        @php
            $url = route('qr.scan', ['token' => $record->token]);
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

        {{-- Visible Preview (Single card, full width) --}}
        <div style="display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; width: 100%;">
            <div class="p-6 bg-amber-50 border border-amber-200 rounded-xl shadow-lg flex flex-col items-center justify-center text-center" style="width: 100%; max-width: 500px;">
                <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="mb-4" style="width: 100%; max-width: 300px;" />
                <img src="{{ $dataUri }}" alt="QR Code" class="mb-3 w-48 h-48 border-4 border-white rounded-lg shadow-sm" />
                <div class="text-xl font-bold text-gray-800 mt-2">{{ $record->unique_code }}</div>
                <div class="text-xs text-gray-500 mt-1 break-all">{{ $url }}</div>
            </div>
        </div>

        {{-- Hidden Print Template --}}
        <div id="qr-print-template" class="hidden">
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
                        font-family: 'Inter', sans-serif;
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
                        font-weight: 800;
                        margin-top: 3mm;
                        color: #1f2937;
                    }
                    .url-text {
                        font-size: 9px;
                        color: #6b7280;
                        margin-top: 2mm;
                        word-break: break-all;
                    }
                }
            </style>
            <div class="print-page">
                <div class="print-card">
                    <img src="{{ asset('images/kutoot-name-logo.svg') }}" alt="Kutoot" class="logo" />
                    <img src="{{ $dataUri }}" alt="QR Code" class="qr-image" />
                    <div class="code-text">{{ $record->unique_code }}</div>
                    <div class="url-text">{{ $url }}</div>
                </div>
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
