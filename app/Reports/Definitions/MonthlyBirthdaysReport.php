<?php

namespace App\Reports\Definitions;

use App\Reports\ReportColumn;
use App\Reports\ReportDefinition;
use App\Reports\ReportFilter;

class MonthlyBirthdaysReport
{
    public const KEY = 'monthly_birthdays';

    public static function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::KEY,
            label: 'Aniversariantes do mês',
            description: 'Clientes que fazem aniversário no mês selecionado.',
            filters: [
                new ReportFilter(
                    key: 'month',
                    label: 'Mês',
                    type: 'select',
                    options: self::monthOptions(),
                ),
            ],
            columns: [
                new ReportColumn('id', 'ID', 'number', true),
                new ReportColumn('name', 'Nome', 'string', true),
                new ReportColumn('birth_date', 'Data de nascimento', 'date', true),
                new ReportColumn('birth_day', 'Dia', 'number', true),
                new ReportColumn('age', 'Idade', 'number', true),
                new ReportColumn('phone', 'Telefone', 'string'),
                new ReportColumn('email', 'E-mail', 'string'),
            ],
        );
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function monthOptions(): array
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return array_map(
            fn (int $month, string $label): array => ['value' => $month, 'label' => $label],
            array_keys($months),
            array_values($months),
        );
    }
}
