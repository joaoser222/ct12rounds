<?php

namespace App\Repositories\Eloquent;

use App\Models\ClassSchedule;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;

class EloquentClassScheduleRepository extends BaseEloquentRepository implements ClassScheduleRepositoryInterface
{
    protected function modelClass(): string
    {
        return ClassSchedule::class;
    }

    protected array $with = ['modality'];

    protected array $searchableFields = [];

    protected array $filterableFields = ['modality_id', 'week_day', 'visibility'];

    protected string $defaultSort = 'week_day';

    protected string $defaultSortDirection = 'asc';
}
