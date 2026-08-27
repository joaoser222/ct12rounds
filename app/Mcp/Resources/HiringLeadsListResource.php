<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\HiringLead;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('hiring-leads')]
#[Description('Lista paginada de pré-cadastros de clientes (hiring_leads)')]
#[MimeType('application/json')]
#[Uri('gymnamite://hiring-leads')]
class HiringLeadsListResource extends Resource
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('hiring_leads.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $leads = HiringLead::query()
            ->select(['id', 'name', 'document', 'phone', 'email', 'source', 'status', 'accepted_at', 'created_at'])
            ->orderByDesc('id')
            ->paginate(15);

        return Response::json($leads->toArray());
    }
}
