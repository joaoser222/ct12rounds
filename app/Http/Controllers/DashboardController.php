<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Services\DashboardService;
use App\Traits\AuthorizesAccessControl;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesAccessControl;

    public function __construct(private readonly DashboardService $dashboardService) {}

    public function __invoke(): Response
    {
        $this->authorizeAccess(AccessAction::VIEW);

        return Inertia::render('Dashboard', [
            'charts' => [
                'contractsByMonth' => $this->dashboardService->contractsByMonth(),
                'receivedByMonth' => $this->dashboardService->receivedByMonth(),
                'contractsByPlan' => $this->dashboardService->contractsByPlan(),
                'outstanding' => $this->dashboardService->receivableOutstanding(),
            ],
        ]);
    }

    protected function accessModule(): AccessModule
    {
        return AccessModule::DASHBOARD;
    }
}
