<?php

namespace App\Actions\ClassSchedules;

use App\Actions\BaseAction;
use App\DTOs\ClassSchedules\ActionResultDTO;
use App\DTOs\ClassSchedules\UpdateClassScheduleDTO;
use App\Models\ClassSchedule;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;

class UpdateClassScheduleAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = ClassSchedule::class;

    public function __construct(
        private readonly ClassScheduleRepositoryInterface $classScheduleRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateClassScheduleDTO) {
            throw new \InvalidArgumentException('UpdateClassScheduleAction requires an UpdateClassScheduleDTO.');
        }

        $dto = $input;

        /** @var ClassSchedule $classSchedule */
        $classSchedule = $this->classScheduleRepository->findOrFail($dto->id);

        $classSchedule = $this->classScheduleRepository->update($classSchedule, array_filter([
            'modality_id' => $dto->modality_id,
            'week_day' => $dto->week_day,
            'start_time' => $dto->start_time,
            'end_time' => $dto->end_time,
        ], fn ($v) => $v !== null));

        return ActionResultDTO::success(
            $classSchedule,
            'Horário atualizado com sucesso.'
        );
    }
}
