<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\DirectLessonRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('direct-lesson')]
#[Description('Detalhes de uma aula direta por ID')]
#[MimeType('application/json')]
class DirectLessonResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected DirectLessonRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://direct-lessons/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('direct_lessons.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Aula direta não encontrada.');
        }

        return Response::json($model->toArray());
    }
}
