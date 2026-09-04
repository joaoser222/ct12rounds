<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('purchase')]
#[Description('Detalhes de uma compra por ID')]
#[MimeType('application/json')]
class PurchaseResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected PurchaseRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://purchases/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('purchases.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Compra não encontrada.');
        }

        return Response::json($model->toArray());
    }
}
