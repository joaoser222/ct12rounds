<?php

namespace App\Repositories\Eloquent;

use App\Models\ModalityCategory;
use App\Repositories\Contracts\ModalityCategoryRepositoryInterface;

class EloquentModalityCategoryRepository extends BaseEloquentRepository implements ModalityCategoryRepositoryInterface
{
    protected function modelClass(): string
    {
        return ModalityCategory::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name'];

    protected array $filterableFields = ['audience', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';
}
