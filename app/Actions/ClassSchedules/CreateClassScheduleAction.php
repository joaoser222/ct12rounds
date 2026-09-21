<?php

namespace App\Actions\ClassSchedules;

use App\Actions\BaseAction;
use App\DTOs\ClassSchedules\ActionResultDTO;
use App\DTOs\ClassSchedules\CreateClassScheduleDTO;
use App\Models\ClassSchedule;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;

class CreateClassScheduleAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = ClassSchedule::class;

    public function __construct(
        private readonly ClassScheduleRepositoryInterface $classScheduleRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateClassScheduleDTO) {
            throw new \InvalidArgumentException('CreateClassScheduleAction requires a CreateClassScheduleDTO.');
        }

        $dto = $input;

        $classSchedule = $this->classScheduleRepository->create([
            'modality_id' => $dto->modality_id,
            'week_day' => $dto->week_day,
            'start_time' => $dto->start_time,
            'end_time' => $dto->end_time,
        ]);

        return ActionResultDTO::success(
            $classSchedule,
            'Horário criado com sucesso.'
        );
    }
}
