<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\ClassSchedules\CreateClassScheduleAction;
use App\Actions\ClassSchedules\UpdateClassScheduleAction;
use App\DTOs\ClassSchedules\CreateClassScheduleDTO;
use App\DTOs\ClassSchedules\UpdateClassScheduleDTO;
use App\Models\ClassSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassScheduleController extends CrudModuleController
{
    public function __construct(
        private readonly CreateClassScheduleAction $createClassSchedule,
        private readonly UpdateClassScheduleAction $updateClassSchedule,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'modality_name', 'week_day', 'start_time', 'end_time', 'created_at'];

    protected array $joins = ['modality'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'class_schedules.id',
        'modality_id' => 'class_schedules.modality_id',
        'modality_name' => 'modalities.name',
        'week_day' => 'class_schedules.week_day',
        'start_time' => 'class_schedules.start_time',
        'end_time' => 'class_schedules.end_time',
        'created_at' => 'class_schedules.created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['modality_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'week_day', 'start_time', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CLASS_SCHEDULE;
    }

    protected function modelClass(): string
    {
        return ClassSchedule::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createClassSchedule->execute(
            CreateClassScheduleDTO::from($request->validate([
                'modality_id' => ['required', 'integer', 'exists:modalities,id'],
                'week_day' => ['required', 'integer', 'min:1', 'max:6'],
                'start_time' => ['required', 'string', 'date_format:H:i'],
                'end_time' => ['required', 'string', 'date_format:H:i', 'after:start_time'],
            ]))
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var ClassSchedule $classSchedule */
        $classSchedule = $this->modelFromRoute($request);

        $result = $this->updateClassSchedule->execute(
            UpdateClassScheduleDTO::from([
                ...$request->validate([
                    'modality_id' => ['nullable', 'integer', 'exists:modalities,id'],
                    'week_day' => ['nullable', 'integer', 'min:1', 'max:6'],
                    'start_time' => ['nullable', 'string', 'date_format:H:i'],
                    'end_time' => ['nullable', 'string', 'date_format:H:i'],
                ]),
                'id' => $classSchedule->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }
}
