<?php

use Laravel\Mcp\Facades\Mcp;

// Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);

Mcp::web('/mcp', \App\Mcp\Servers\SupportDeskServer::class);

