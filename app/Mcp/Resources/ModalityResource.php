<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\ModalityRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('modality')]
#[Description('Detalhes de uma modalidade por ID')]
#[MimeType('application/json')]
class ModalityResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected ModalityRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://modalities/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('modalities.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Modalidade não encontrada.');
        }

        return Response::json($model->toArray());
    }
}
