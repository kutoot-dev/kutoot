<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StoreBanner;

$model = StoreBanner::first();
if (!$model) {
    echo "No StoreBanner found.\n";
    exit;
}

echo "ID: " . $model->id . "\n";
echo "First Media URL: " . $model->getFirstMediaUrl('image') . "\n";
echo "Thumb URL: " . $model->getFirstMediaUrl('image', 'thumb') . "\n";
echo "HasMediaInterface? " . ($model instanceof \Spatie\MediaLibrary\HasMedia ? 'Yes' : 'No') . "\n";
if ($model instanceof \Spatie\MediaLibrary\HasMedia) {
    $media = $model->getFirstMedia('image');
    if ($media) {
        echo "Media Disk: " . $media->disk . "\n";
        echo "Media Path: " . $media->getPath() . "\n";
    }
}
