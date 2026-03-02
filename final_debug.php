<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StoreBanner;
use App\Models\FeaturedBanner;

function checkModel($class) {
    echo "Checking $class...\n";
    $model = $class::first();
    if (!$model) {
        echo "  No records found.\n";
        return;
    }
    echo "  ID: " . $model->id . "\n";
    $media = $model->getFirstMedia('image');
    if ($media) {
        echo "  Media ID: " . $media->id . "\n";
        echo "  File Name: " . $media->file_name . "\n";
        echo "  Disk: " . $media->disk . "\n";
        echo "  Path: " . $media->getPath() . "\n";
        echo "  URL: " . $media->getUrl() . "\n";
        echo "  File Exists on Disk: " . (file_exists($media->getPath()) ? 'YES' : 'NO') . "\n";
    } else {
        echo "  No media found.\n";
    }
    echo "\n";
}

checkModel(StoreBanner::class);
checkModel(FeaturedBanner::class);
