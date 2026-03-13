<x-filament-panels::page>
    @php
        $url = route('qr.scan', ['token' => $record->token]);
        $dataUri = \App\Services\QrCodeBuilder::buildForUrl($url, 400);
        $bgUrl = \App\Services\QrBackgroundService::getBackgroundUrl();
        $widthIn = \App\Services\SettingService::get('qr_print_width_in', 4);
        $heightIn = \App\Services\SettingService::get('qr_print_height_in', 6);
    @endphp

    <div class="flex flex-col items-center justify-center p-4 bg-gray-100 rounded-lg shadow-sm">
        <div class="mb-4">
            <x-filament::button
                size="lg"
                icon="heroicon-m-printer"
                onclick="printQrCode()"
            >
                Print QR Code
            </x-filament::button>
        </div>

        {{-- Visible Preview (matches print layout) --}}
        <div class="qr-sticker-preview" style="width: 200px; aspect-ratio: {{ $widthIn }}/{{ $heightIn }}; background-image: url('{{ $bgUrl }}'); background-size: 100% 100%; background-repeat: no-repeat; position: relative; border-radius: 8px; overflow: hidden;">
            <img src="{{ $dataUri }}" alt="QR Code" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[38%] h-auto rounded-lg" />
            <div class="absolute bottom-[16%] left-1/2 -translate-x-1/2 text-lg font-bold text-gray-800 whitespace-nowrap">{{ $record->unique_code }}</div>
        </div>

        {{-- Hidden Print Template --}}
        <div id="qr-print-template" class="hidden">
            <style>
                @media print {
                    @page {
                        size: {{ $widthIn }}in {{ $heightIn }}in;
                        margin: 0;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        font-family: 'Inter', sans-serif;
                    }
                    .qr-sticker-print {
                        width: {{ $widthIn }}in;
                        height: {{ $heightIn }}in;
                        position: relative;
                        background-image: url('{{ $bgUrl }}');
                        background-size: 100% 100%;
                        background-repeat: no-repeat;
                        box-sizing: border-box;
                    }
                    .qr-sticker-print .qr-image {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: 60%;
                        height: auto;
                        border-radius: 12px;
                    }
                    .qr-sticker-print .code-text {
                        position: absolute;
                        bottom: 17%;
                        left: 50%;
                        transform: translateX(-50%);
                        font-size: 0.70rem;
                        font-weight: 800;
                        color: #1f2937;
                        white-space: nowrap;
                    }
                }
            </style>
            <div class="qr-sticker-print">
                <img src="{{ $dataUri }}" alt="QR Code" class="qr-image" />
                <div class="code-text">{{ $record->unique_code }}</div>
            </div>
        </div>

        <script>
            function printQrCode() {
                const printContent = document.getElementById('qr-print-template').innerHTML;
                const originalContents = document.body.innerHTML;
                document.body.innerHTML = printContent;
                window.print();
                document.body.innerHTML = originalContents;
                window.location.reload();
            }
        </script>
    </div>
</x-filament-panels::page>
