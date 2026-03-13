<?php

return [

    /*
     * Maximum file size for uploads in MB. Single global config: MAX_UPLOAD_SIZE_MB (default 100).
     * For KB (Filament maxSize / Laravel max rule), multiply by 1024.
     */
    'max_upload_size_mb' => (int) env('MAX_UPLOAD_SIZE_MB', 100),

];
