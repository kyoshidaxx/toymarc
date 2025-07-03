<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DMARC Reports Directory
    |--------------------------------------------------------------------------
    |
    | This is the default directory where DMARC report files are stored.
    | The path is relative to the storage/app directory.
    |
    */

    'reports_directory' => env('DMARC_REPORTS_DIRECTORY', 'dmarc_reports'),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | Maximum allowed file size for DMARC report files in bytes.
    | Default is 10MB.
    |
    */

    'max_file_size' => env('DMARC_MAX_FILE_SIZE', 10485760),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions that are allowed for DMARC report files.
    |
    */

    'allowed_extensions' => ['xml'],

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the import process.
    |
    */

    'import' => [
        'batch_size' => env('DMARC_IMPORT_BATCH_SIZE', 100),
        'timeout' => env('DMARC_IMPORT_TIMEOUT', 300),
        'memory_limit' => env('DMARC_IMPORT_MEMORY_LIMIT', '512M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the dashboard display.
    |
    */

    'dashboard' => [
        'default_date_range' => env('DMARC_DASHBOARD_DEFAULT_RANGE', 30), // days
        'refresh_interval' => env('DMARC_DASHBOARD_REFRESH_INTERVAL', 300), // seconds
        'max_records_per_page' => env('DMARC_DASHBOARD_MAX_RECORDS', 50),
    ],

]; 