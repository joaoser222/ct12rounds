<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('create-product')]
#[Description('Guia o modelo a cadastrar um novo produto na academia.')]
class CreateProductPrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('products.create') ?? false;
    }

    /**
     * @return array<int, array{key: string, instruction: string}>
     */
    public function steps(): array
    {
        return [
            [
                'key' => 'product_data',
                'instruction' => 'Colete os dados do produto: nome, preço de compra, preço de venda, tipo (insumo ou produto) e unidade (kg, un, lt, etc).',
            ],
            [
                'key' => 'confirm',
                'instruction' => 'Resuma os dados coletados e peça confirmação do usuário antes de criar o produto.',
            ],
            [
                'key' => 'create',
                'instruction' => 'Use a ferramenta de criar produto informando os dados coletados.',
            ],
        ];
    }

    public function clientMessage(): string
    {
        $name = config('app.name', 'a academia');

        return <<<TEXT
Olá! Para cadastrar um novo produto na {$name}, por favor preencha os dados abaixo:

*Cadastro de Produto*
Nome do produto:
Preço de compra:
Preço de venda:
Tipo (insumo/produto):
Unidade (kg, un, lt, etc):

Responda esta mensagem com os dados preenchidos.
TEXT;
    }

    public function handle(Request $request): Response
    {
        return Response::text($this->buildStepsText());
    }

    private function buildStepsText(): string
    {
        $steps = $this->steps();
        $lines = [];

        foreach ($steps as $index => $step) {
            $lines[] = ($index + 1).'. ['.$step['key'].'] '.$step['instruction'];
        }

        $stepCount = count($steps);

        return "Siga estritamente as etapas abaixo para cadastrar um produto. "
            ."Execute cada etapa em ordem. Ao final de cada etapa, resuma o que foi coletado "
            ."e aguarde a confirmação do usuário antes de avançar. Se o usuário quiser corrigir "
            ."dados de uma etapa anterior, retorne a ela.\n\n"
            .implode("\n", $lines)."\n\n"
            ."Total de etapas: {$stepCount}.";
    }
}
