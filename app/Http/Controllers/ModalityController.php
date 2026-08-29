<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Modalities\CreateModalityAction;
use App\Actions\Modalities\UpdateModalityAction;
use App\DTOs\Modalities\CreateModalityDTO;
use App\DTOs\Modalities\UpdateModalityDTO;
use App\Models\Modality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ModalityController extends CrudModuleController
{
    public function __construct(
        private readonly CreateModalityAction $createModality,
        private readonly UpdateModalityAction $updateModality,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'color', 'icon', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::MODALITY;
    }

    protected function modelClass(): string
    {
        return Modality::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $iconPath = $request->hasFile('icon')
            ? $this->storeIcon($request->file('icon'))
            : null;

        $result = $this->createModality->execute(
            CreateModalityDTO::from([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'icon' => $iconPath,
            ])
        );

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' criado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var Modality $modality */
        $modality = $this->modelFromRoute($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_icon' => ['nullable', 'boolean'],
        ]);

        $removeIcon = (bool) ($validated['remove_icon'] ?? false);

        $iconPath = $modality->icon;
        if ($request->hasFile('icon')) {
            $this->deleteIcon($modality->icon);
            $iconPath = $this->storeIcon($request->file('icon'));
        } elseif ($removeIcon) {
            $this->deleteIcon($modality->icon);
            $iconPath = null;
        }

        $result = $this->updateModality->execute(
            UpdateModalityDTO::from([
                'id' => $modality->getKey(),
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'icon' => $iconPath,
            ])
        );

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /**
     * Stores the uploaded icon under a deterministic md5 filename keeping the original extension.
     */
    private function storeIcon(UploadedFile $file): string
    {
        $name = md5_file($file->getRealPath()).'.'.$file->getClientOriginalExtension();

        return $file->storeAs('modalities', $name, 'public');
    }

    private function deleteIcon(?string $icon): void
    {
        if ($icon !== null && Storage::disk('public')->exists($icon)) {
            Storage::disk('public')->delete($icon);
        }
    }
}
