<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$qrCode = \App\Models\QrCode::where('token', 'Nm8kQeyr7bi4hqYS9lRMdf0LbDC74l6X')->first();

if ($qrCode) {
    $qrCode->update(['status' => 'linked']);
    echo "✓ QR Code ID " . $qrCode->id . " status updated to: linked\n";
} else {
    echo "✗ QR Code not found\n";
}
