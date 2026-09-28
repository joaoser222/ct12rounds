<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Backup
    |--------------------------------------------------------------------------
    |
    | Daily PostgreSQL dumps produced by the "db:backup" command. The path is
    | bind mounted to the host in the production Compose stack, so the dumps
    | survive container replacement and stay visible outside of Docker.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 7),

    'filename_prefix' => env('BACKUP_FILENAME_PREFIX', 'ct12rounds-'),

    /*
    | Seconds a single dump may run before it is considered failed. Large
    | databases on slow disks need more than the Symfony default.
    */
    'timeout' => (int) env('BACKUP_TIMEOUT', 3600),

];
