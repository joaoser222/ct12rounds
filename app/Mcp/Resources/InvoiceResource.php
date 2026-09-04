<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('invoice')]
#[Description('Detalhes de uma nota fiscal por ID')]
#[MimeType('application/json')]
class InvoiceResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected InvoiceRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://invoices/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('gateway_invoices.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Nota fiscal não encontrada.');
        }

        return Response::json($model->toArray());
    }
}
