<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Modality;
use App\Models\ModalityGraduation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GraduationService
{
    /**
     * Syncs the graduation catalog of a modality.
     *
     * Rows that keep their id are updated in place, so links held by
     * client_graduations survive an edit. Ids that no longer belong to the
     * modality are treated as new rows instead of being stolen from another one.
     *
     * ponytail: position mirrors the array index, so reordering means moving the
     * row in the form. Add drag handles when clients ask for it.
     *
     * ponytail: renaming two rows in the same save (a swap) trips the
     * (modality_id, name) unique index. Renames are one-at-a-time in practice.
     *
     * @param  array<int, array<string, mixed>>  $graduations
     */
    public function syncModalityGraduations(Modality $modality, array $graduations): void
    {
        $existingIds = $modality->graduations()
            ->get(['id'])
            ->map(fn (ModalityGraduation $graduation): int => $graduation->getKey())
            ->flip();

        $keptIds = [];

        foreach ($this->normalizeGraduations($graduations) as $position => $graduation) {
            $id = $graduation['id'];

            if ($id !== null && $existingIds->has($id)) {
                $modality->graduations()
                    ->whereKey($id)
                    ->update([
                        'name' => $graduation['name'],
                        'color' => $graduation['color'],
                        'position' => $position,
                    ]);

                $keptIds[] = $id;

                continue;
            }

            $keptIds[] = $modality->graduations()->create([
                'name' => $graduation['name'],
                'color' => $graduation['color'],
                'position' => $position,
            ])->getKey();
        }

        $modality->graduations()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Replaces the graduations linked to a client.
     *
     * Nothing else references client_graduations, so rebuilding the rows is safe.
     * promoted_at is owned by the form and round-trips through the request.
     *
     * @param  array<int, array<string, mixed>>  $graduations
     */
    public function syncClientGraduations(Client $client, array $graduations): void
    {
        $graduationIds = collect($graduations)
            ->map(fn (array $graduation): array => [
                'modality_graduation_id' => (int) ($graduation['modality_graduation_id'] ?? 0),
                'promoted_at' => $graduation['promoted_at'] ?? null,
            ])
            ->filter(fn (array $graduation): bool => $graduation['modality_graduation_id'] > 0)
            ->unique('modality_graduation_id')
            ->values();

        $client->graduations()->delete();

        foreach ($graduationIds as $graduation) {
            $client->graduations()->create($graduation);
        }
    }

    /**
     * Trims names, title-cases them, drops empty ones and keeps only the first
     * of each duplicate.
     *
     * Duplicates are keyed on the normalized name, so "faixa branca" and
     * "Faixa Branca" collapse into one row instead of tripping the
     * (modality_id, name) unique index.
     *
     * ponytail: Str::title lowercases acronyms ("AFA" -> "Afa"). The form's
     * v-text-case keeps them, so the stored value can differ from the typed
     * one. Mirror the directive's preposition rules here only if labels with
     * prepositions or acronyms actually show up.
     *
     * @param  array<int, array<string, mixed>>  $graduations
     * @return Collection<int, array{id: int|null, name: string, color: string|null}>
     */
    private function normalizeGraduations(array $graduations): Collection
    {
        $rows = [];
        $seen = [];

        foreach ($graduations as $graduation) {
            $name = Str::title(trim((string) ($graduation['name'] ?? '')));

            if ($name === '' || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;

            // The controller already enforces the hex format; only the empty
            // case needs a value, since <input type="color"> can submit one.
            $color = trim((string) ($graduation['color'] ?? ''));

            $rows[] = [
                'id' => isset($graduation['id']) ? (int) $graduation['id'] : null,
                'name' => $name,
                'color' => $color === '' ? null : strtolower($color),
            ];
        }

        return collect($rows);
    }
}