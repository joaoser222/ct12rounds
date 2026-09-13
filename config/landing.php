<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Snapshot Path
    |--------------------------------------------------------------------------
    |
    | Where the versioned snapshot of the published landing is written. The
    | snapshot lives inside the repository so a publish can be committed and
    | rolled back from git. Guarded writes only happen when the directory is
    | writable, so production containers without a bind mount stay safe.
    |
    */

    'snapshot_path' => env('LANDING_SNAPSHOT_PATH', resource_path('views/landing/published.html')),

];