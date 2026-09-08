<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Traits\AuthorizesAccessControl;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

abstract class AbstractModuleController extends Controller
{
    use AuthorizesAccessControl;

    abstract protected function accessModule(): AccessModule;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Return the available routes for the frontend.
     *
     * @return array<string, string>
     */
    abstract protected function getModuleRoutes(): array;

    /**
     * Share routes with Inertia.
     */
    protected function shareModuleRoutes(): void
    {
        Inertia::share('moduleRoutes', function (): array {
            return $this->getModuleRoutes();
        });
    }

    protected function routePrefix(): string
    {
        return str_replace('_', '-', $this->accessModule()->value);
    }

    protected function routeParameterName(): string
    {
        return Str::of($this->routePrefix())->replace('-', '_')->singular()->toString();
    }

    protected function indexComponent(): string
    {
        return Str::of($this->routePrefix())
            ->replace('-', '_')
            ->append('/Index')
            ->toString();
    }

    protected function detailsComponent(): string
    {
        return Str::of($this->routePrefix())
            ->replace('-', '_')
            ->append('/Details')
            ->toString();
    }

    protected function collectionPropName(): string
    {
        return $this->routePrefix();
    }

    protected function itemPropName(): string
    {
        return Str::singular($this->routePrefix());
    }

    /**
     * @return class-string<FormRequest>|null
     */
    protected function storeRequestClass(): ?string
    {
        return null;
    }

    /**
     * @return class-string<FormRequest>|null
     */
    protected function updateRequestClass(): ?string
    {
        return null;
    }

    protected function defaultSearchField(): ?string
    {
        return $this->searchableFields()[0] ?? null;
    }

    /**
     * Fields that will be returned to the frontend.
     * If empty, returns all fields.
     *
     * @return array<int, string>
     */
    protected function fields(): array
    {
        return property_exists($this, 'fields')
            ? $this->fields
            : [];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldsMapping(): array
    {
        return property_exists($this, 'fieldsMapping')
            ? $this->fieldsMapping
            : [];
    }

    /**
     * @return array<int, string|Expression>
     */
    protected function resolveSelectColumns(): array
    {
        $mapping = $this->fieldsMapping();

        return array_map(
            fn (string $field) => isset($mapping[$field])
                ? \DB::raw("{$mapping[$field]} AS ".\DB::getQueryGrammar()->wrap($field))
                : $field,
            $this->fields(),
        );
    }

    protected function resolveSearchColumn(string $field): string
    {
        $mapping = $this->fieldsMapping();

        return $mapping[$field] ?? $field;
    }

    /**
     * @return array<int, string>
     */
    protected function joins(): array
    {
        return property_exists($this, 'joins')
            ? $this->joins
            : [];
    }

    protected function applyJoins(Builder $query): void
    {
        $model = new ($this->modelClass());

        foreach ($this->joins() as $relation) {
            $relationObject = $model->$relation();

            $query->leftJoin(
                $relationObject->getRelated()->getTable(),
                $relationObject->getQualifiedForeignKeyName(),
                '=',
                $relationObject->getQualifiedOwnerKeyName(),
            );
        }
    }

    /**
     * @return array<int, string>
     */
    protected function searchableFields(): array
    {
        return property_exists($this, 'searchableFields')
            ? $this->searchableFields
            : [];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableFields(): array
    {
        return property_exists($this, 'sortableFields')
            ? $this->sortableFields
            : ['id', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleIndexProps(Request $request): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [];
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string, color: string}>
     */
    protected function enumOptions(string $enumClass): array
    {
        return array_map(
            fn (BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
                'color' => method_exists($case, 'color') ? $case->color() : 'secondary',
            ],
            $enumClass::cases(),
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, array{value: string, label: string}>
     */
    protected function modelOptions(string $modelClass): array
    {
        return $modelClass::query()
            ->select(['code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Model $model): array => [
                'value' => (string) $model->getAttribute('code'),
                'label' => (string) $model->getAttribute('name'),
            ])
            ->all();
    }

    protected function modelFromRoute(Request $request): Model
    {
        $routeValue = $request->route($this->routeParameterName());

        if ($routeValue instanceof Model) {
            return $routeValue;
        }

        $model = $this->newModelQuery()->findOrFail($routeValue);

        $request->route()?->setParameter($this->routeParameterName(), $model);

        return $model;
    }

    protected function pageItemId(Request $request): string|int|null
    {
        if ($request->routeIs($this->routePrefix().'.create')) {
            return 'new';
        }

        $routeValue = $request->route($this->routeParameterName());

        if ($routeValue instanceof Model) {
            return $routeValue->getKey();
        }

        return $routeValue;
    }

    protected function newModelQuery(): Builder
    {
        return $this->modelClass()::query();
    }

    /**
     * @param  class-string<FormRequest>|null  $formRequestClass
     * @return array<string, mixed>
     */
    protected function validatedRequestData(Request $request, ?string $formRequestClass): array
    {
        if ($formRequestClass === null) {
            return $request->all();
        }

        $formRequest = $formRequestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->setRouteResolver($request->getRouteResolver());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }
}
