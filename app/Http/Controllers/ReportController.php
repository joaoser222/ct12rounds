<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Http\Requests\RunReportRequest;
use App\Models\Report;
use App\Reports\ReportResult;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends ReadOnlyModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'label', 'description', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'label', 'description'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'label', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::REPORT;
    }

    protected function modelClass(): string
    {
        return Report::class;
    }

    /**
     * @return array<string, string>
     */
    protected function getModuleRoutes(): array
    {
        $routes = parent::getModuleRoutes();
        $runRoute = route('reports.run', ['report' => '__id__']);

        return [
            ...$routes,
            'run' => str_replace('__id__', ':id', $runRoute),
        ];
    }

    public function run(Request $request, Report $report, ReportService $reportService): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $definition = $reportService->find($report->name);

        abort_if($definition === null, 404);

        $validated = $this->validatedRequestData($request, RunReportRequest::class);

        if ($validated === []) {
            $result = new ReportResult(
                definition: $definition,
                columns: $definition->columns,
                rows: [],
                meta: [
                    'filters' => [],
                    'user_id' => $request->user()?->id,
                ],
            );
        } else {
            $result = $reportService->run($report->name, $validated, $request->user());
        }

        if ($request->expectsJson()) {
            return response()->json($result->toArray());
        }

        $this->shareModuleRoutes();

        return Inertia::render('reports/Run', [
            'report' => $report,
            'definition' => $definition->toArray(),
            'columns' => array_map(
                fn ($column): array => $column->toArray(),
                $result->columns,
            ),
            'rows' => $result->rows,
            'filters' => $result->meta['filters'],
            'routes' => $this->getModuleRoutes(),
        ]);
    }
}
