<?php

return [

    /*
     * Maximum file size for uploads. Single global config: MAX_UPLOAD_SIZE_MB (default 100).
     * Used across Filament forms (maxSize), API validation, and Media Library.
     * Can be overridden from Admin Panel → System Settings → Object Storage.
     */
    'max_file_size_kb' => (int) env('MAX_UPLOAD_SIZE_MB', 100) * 1024,

];
