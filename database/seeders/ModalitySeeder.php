<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ModalitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Modalidades pré-definidas com cor e ícone.
     *
     * @var array<int, array{name: string, color: string, icon: string}>
     */
    private const MODALITIES = [
        ['name' => 'Boxe', 'color' => '#DC2626', 'icon' => 'boxe'],
        ['name' => 'Jiu-jitsu', 'color' => '#0D9488', 'icon' => 'jiu-jitsu'],
        ['name' => 'Kickboxing', 'color' => '#EA580C', 'icon' => 'kickboxing'],
        ['name' => 'MMA', 'color' => '#2563EB', 'icon' => 'mma'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::MODALITIES as $modalityData) {
            $iconPath = $this->copyIcon($modalityData['icon']);

            Modality::updateOrCreate(
                ['name' => $modalityData['name']],
                ['color' => $modalityData['color'], 'icon' => $iconPath]
            );
        }
    }

    /**
     * Copia o PNG bundled para o disco público e retorna o caminho relativo.
     */
    private function copyIcon(string $icon): string
    {
        $source = database_path('seeders/assets/modalities/'.$icon.'.png');
        $target = 'modalities/'.$icon.'.png';

        Storage::disk('public')->put($target, file_get_contents($source));

        return $target;
    }
}
