<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\GatewayAccountRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('gateway-account')]
#[Description('Detalhes de uma conta do gateway por ID')]
#[MimeType('application/json')]
class GatewayAccountResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected GatewayAccountRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://gateway-accounts/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('gateway_accounts.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Conta do gateway não encontrada.');
        }

        return Response::json($model->toArray());
    }
}
