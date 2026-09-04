<?php

use App\Mcp\Servers\Ct12roundsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/ct12rounds', Ct12roundsServer::class)
    ->middleware(['auth:sanctum']);
