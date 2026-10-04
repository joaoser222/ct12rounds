<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\AccessControl\AccessModule;
use App\Http\Controllers\AbstractModuleController;
use App\Http\Controllers\CrudModuleController;
use Closure;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Writes a draft help guide for every module controller.
 *
 * Everything in the draft is read from the code: the route prefix, the
 * searchable fields, the enum options the screen already sends to the frontend
 * and the permissions it requires. Only the prose is left blank, marked TODO.
 *
 * Drafts go to `docs/help/_drafts/` because `HelpService` globs
 * `docs/help/*.md`: a skeleton full of TODO must never reach the chat. Move a
 * draft to `docs/help/` once someone who uses the screen has written it.
 */
#[Signature('help:skeleton {--force : Sobrescreve rascunhos que já existem}')]
#[Description('Gera rascunhos de docs/help/ a partir dos module controllers')]
class GenerateHelpGuideSkeleton extends Command
{
    private const DRAFTS = 'docs/help/_drafts';

    public function handle(): int
    {
        $directory = base_path(self::DRAFTS);
        File::ensureDirectoryExists($directory);

        $written = [];
        $skipped = [];

        foreach ($this->controllers() as $class) {
            $module = $this->readMetadata($class, 'accessModule');

            if (! $module instanceof AccessModule) {
                continue;
            }

            $prefix = (string) $this->readMetadata($class, 'routePrefix');
            $path = "{$directory}/{$module->value}.md";

            // RoleController and UserController both map to AccessModule::USER.
            if (isset($written[$module->value]) || isset($skipped[$module->value])) {
                continue;
            }

            if (File::exists($path) && ! $this->option('force')) {
                $skipped[$module->value] = true;

                continue;
            }

            File::put($path, $this->skeleton($class, $module, $prefix));
            $written[$module->value] = true;
        }

        foreach (array_keys($written) as $slug) {
            $this->components->info("Rascunho [{$slug}.md] gerado.");
        }

        if ($skipped !== []) {
            $this->components->warn(
                'Já existe rascunho: '.implode(', ', array_keys($skipped)).'. Use --force para regerar.'
            );
        }

        $this->newLine();
        $this->components->info(
            count($written).' rascunho(s) em '.self::DRAFTS.'. Preencha os blocos TODO e mova para docs/help/.'
        );

        return self::SUCCESS;
    }

    /**
     * @return array<int, class-string<AbstractModuleController>>
     */
    private function controllers(): array
    {
        $controllers = [];

        foreach (Finder::create()->files()->in(app_path('Http/Controllers'))->name('*.php') as $file) {
            /** @var SplFileInfo $file */
            $class = 'App\\Http\\Controllers\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(AbstractModuleController::class)) {
                continue;
            }

            $controllers[] = $class;
        }

        sort($controllers);

        return $controllers;
    }

    /**
     * @param  class-string<AbstractModuleController>  $class
     */
    private function skeleton(string $class, AccessModule $module, string $prefix): string
    {
        $readOnly = ! is_subclass_of($class, CrudModuleController::class);
        $fields = (array) $this->readMetadata($class, 'fields');
        $searchable = (array) $this->readMetadata($class, 'searchableFields');
        $sortable = (array) $this->readMetadata($class, 'sortableFields');
        $defaultSearch = $searchable[0] ?? null;
        $enums = $this->enumOptions($class);
        $actions = array_map(fn ($action): string => $action->value, $module->actions());

        $lines = [
            "# {$module->label()}",
            '',
            '> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;',
            '> os blocos TODO precisam ser escritos por alguém que usa a tela.',
            '',
            '## Onde fica',
            '',
            "- Tela: `/{$prefix}`",
            '- Permite: '.implode(', ', $actions),
            $readOnly
                ? '- Somente leitura: não há botão de cadastrar, editar ou excluir.'
                : '- Cadastro e edição disponíveis na própria tela.',
            '',
            '## Busca',
            '',
        ];

        $lines[] = $searchable === []
            ? 'A busca não está disponível nesta tela.'
            : 'A busca pesquisa: '.implode(', ', array_map(fn (string $f): string => "`{$f}`", $searchable))
                .($defaultSearch !== null ? ". Sem `searchField`, assume `{$defaultSearch}`." : '.');

        if ($sortable !== []) {
            $lines[] = 'Ordenação: '.implode(', ', array_map(fn (string $f): string => "`{$f}`", $sortable)).'.';
        }

        if ($enums !== []) {
            $lines[] = '';
            $lines[] = '## Filtros e valores válidos';
            $lines[] = '';

            foreach ($enums as $key => $cases) {
                $values = array_map(fn (array $option): string => "`{$option['value']}`", $cases);
                $lines[] = "- {$key}: ".implode(', ', $values);
            }

            $lines[] = '';
            $lines[] = 'A busca compara o texto literalmente, então um link precisa do valor bruto:';
            $lines[] = "`/{$prefix}?searchField=<campo>&search=<valor>`.";
        }

        $lines[] = '';
        $lines[] = '## Campos da lista';
        $lines[] = '';

        if ($fields === []) {
            $lines[] = 'TODO: a tela lista todos os campos do registro.';
        } else {
            foreach ($fields as $field) {
                $lines[] = "- `{$field}`";
            }
        }

        $lines[] = '';
        $lines[] = '## Como usar';
        $lines[] = '';
        $lines[] = 'TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.';
        $lines[] = '';
        $lines[] = '## Perguntas frequentes';
        $lines[] = '';
        $lines[] = 'TODO: as dúvidas que o pessoal realmente faz sobre esta tela.';

        return implode("\n", $lines)."\n";
    }

    /**
     * Reads the enum options the screen already ships to the frontend, so the
     * guide lists the values the code accepts instead of guessed ones.
     *
     * @param  class-string<AbstractModuleController>  $class
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function enumOptions(string $class): array
    {
        $options = [];

        foreach (['moduleIndexProps', 'moduleDetailsProps'] as $method) {
            try {
                $props = $this->readMetadata($class, $method, Request::create('/'));
            } catch (Throwable) {
                continue;
            }

            foreach ((array) ($props['options'] ?? []) as $key => $cases) {
                if (is_array($cases) && ! isset($options[$key])) {
                    $options[$key] = $cases;
                }
            }
        }

        ksort($options);

        return $options;
    }

    /**
     * Calls a protected controller method on an instance that skipped the
     * constructor, so metadata stays reachable without booting actions,
     * gateways and HTTP clients that have nothing to do with documentation.
     *
     * @param  class-string<AbstractModuleController>  $class
     */
    private function readMetadata(string $class, string $method, mixed ...$arguments): mixed
    {
        $instance = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        return Closure::bind(
            fn (mixed ...$arguments): mixed => $this->{$method}(...$arguments),
            $instance,
            $class,
        )(...$arguments);
    }
}