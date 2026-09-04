<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\ReceivableRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('receivable')]
#[Description('Detalhes de uma conta a receber por ID')]
#[MimeType('application/json')]
class ReceivableResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected ReceivableRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://receivables/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('receivables.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Conta a receber não encontrada.');
        }

        return Response::json($model->toArray());
    }
}
