<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$qrCode = \App\Models\QrCode::where('token', 'Nm8kQeyr7bi4hqYS9lRMdf0LbDC74l6X')->first();

if ($qrCode) {
    echo "✓ QR Code found\n";
    echo "  ID: " . $qrCode->id . "\n";
    echo "  Status: " . $qrCode->status->value . "\n";
    echo "  Merchant Location ID: " . $qrCode->merchant_location_id . "\n";
    echo "  Token: " . $qrCode->token . "\n";

    try {
        $loc = $qrCode->merchantLocation;
        echo "  Merchant Location: " . ($loc ? $loc->branch_name : "NULL") . "\n";
    } catch (\Exception $e) {
        echo "  Merchant Location Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ QR Code NOT found with token: Nm8kQeyr7bi4hqYS9lRMdf0LbDC74l6X\n";

    // Show all QR codes
    $all = \App\Models\QrCode::limit(3)->get();
    echo "\nSample QR codes in database:\n";
    foreach ($all as $qr) {
        echo "  - " . $qr->token . " (Status: " . $qr->status . ")\n";
    }
}
