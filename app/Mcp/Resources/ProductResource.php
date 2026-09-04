<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('product')]
#[Description('Detalhes de um produto por ID')]
#[MimeType('application/json')]
class ProductResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected ProductRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://products/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('products.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Produto não encontrado.');
        }

        return Response::json($model->toArray());
    }
}
