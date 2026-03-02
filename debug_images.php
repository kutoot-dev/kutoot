<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StoreBanner;
use Illuminate\Support\Facades\Storage;

$model = StoreBanner::first();
if (!$model) {
    echo "No StoreBanner found.\n";
    exit;
}

echo "ID: " . $model->id . "\n";
echo "Title: " . $model->title . "\n";

if ($model instanceof \Spatie\MediaLibrary\HasMedia) {
    echo "Has Media (trait/interface): Yes\n";
    $media = $model->getFirstMedia('image');
    if ($media) {
        echo "Media Found: Yes\n";
        echo "Media ID: " . $media->id . "\n";
        echo "Collection: " . $media->collection_name . "\n";
        echo "File Name: " . $media->file_name . "\n";
        echo "Disk: " . $media->disk . "\n";
        echo "Path Relative to Root: " . $media->getPathRelativeToRoot() . "\n";
        echo "URL: " . $media->getUrl() . "\n";
        echo "Thumb Path Relative to Root: " . $media->getPathRelativeToRoot('thumb') . "\n";
        echo "Thumb URL: " . $media->getUrl('thumb') . "\n";
    } else {
        echo "Media Found: No\n";
    }
} else {
    echo "Has Media: No\n";
}

echo "Model Attribute 'image': " . var_export($model->image, true) . "\n";
echo "Storage::disk('public')->url(\$model->image): " . Storage::disk('public')->url($model->image) . "\n";
echo "File Exists on Public Disk: " . (Storage::disk('public')->exists($model->image) ? 'Yes' : 'No') . "\n";

$public_path = public_path('storage/' . $model->image);
echo "Full Public Path: " . $public_path . "\n";
echo "File Exists at Full Public Path: " . (file_exists($public_path) ? 'Yes' : 'No') . "\n";
