<?php

namespace Tests\Unit\Reports;

use App\Reports\ReportColumn;
use App\Reports\ReportDefinition;
use App\Reports\ReportFilter;
use App\Reports\ReportRegistry;
use App\Services\ReportService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    public function test_registry_exposes_registered_report_definitions(): void
    {
        $definitions = ReportRegistry::all();

        $this->assertCount(1, $definitions);
        $this->assertSame('monthly_birthdays', $definitions[0]->key);

        $service = new ReportService;

        $this->assertSame(['monthly_birthdays'], array_column($service->definitions(), 'key'));
        $this->assertSame('Aniversariantes do mês', $service->find('monthly_birthdays')?->label);
        $this->assertNull($service->find('missing'));
    }

    public function test_definition_serializes_filters_and_columns(): void
    {
        $definition = new ReportDefinition(
            key: 'sample',
            label: 'Sample',
            description: 'Sample report',
            filters: [
                new ReportFilter('status', 'Status', options: [
                    ['value' => 'active', 'label' => 'Active'],
                ]),
            ],
            columns: [
                new ReportColumn('name', 'Name', sortable: true),
            ],
        );

        $this->assertSame([
            'key' => 'sample',
            'label' => 'Sample',
            'description' => 'Sample report',
            'filters' => [[
                'key' => 'status',
                'label' => 'Status',
                'type' => 'string',
                'required' => false,
                'options' => [[
                    'value' => 'active',
                    'label' => 'Active',
                ]],
            ]],
            'columns' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => null,
                'sortable' => true,
            ]],
        ], $definition->toArray());
    }

    public function test_service_rejects_unregistered_reports(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Report [missing] is not registered.');

        (new ReportService)->run('missing');
    }
}
