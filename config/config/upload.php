<?php

return [

    /*
     * The maximum file size for uploads in kilobytes.
     * This value is used across all Filament forms (maxSize)
     * and API validation rules (max:).
     *
     * Default: 2048 KB (2 MB)
     */
    'max_file_size_kb' => (int) env('MAX_UPLOAD_SIZE_MB', 100) * 1024,

];
