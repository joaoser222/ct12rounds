<?php

namespace App\Enums\Gateway;

enum GatewaySyncMode: string
{
    /** Charge the gateway during the request, so failures are known before responding. */
    case SYNC = 'sync';

    /** Hand the charge over to the queue worker. */
    case QUEUE = 'queue';
}
