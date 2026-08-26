<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('create-cost-center')]
#[Description('Guia o modelo a cadastrar um novo centro de custo na academia.')]
class CreateCostCenterPrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('cost_centers.create') ?? false;
    }

    /**
     * @return array<int, array{key: string, instruction: string}>
     */
    public function steps(): array
    {
        return [
            [
                'key' => 'cost_center_data',
                'instruction' => 'Colete os dados: nome do centro de custo e tipo de operação (entrada ou saída).',
            ],
            [
                'key' => 'confirm',
                'instruction' => 'Resuma os dados coletados e peça confirmação do usuário antes de criar o centro de custo.',
            ],
            [
                'key' => 'create',
                'instruction' => 'Use a ferramenta de criar centro de custo informando o nome e o tipo de operação.',
            ],
        ];
    }

    public function clientMessage(): string
    {
        $name = config('app.name', 'a academia');

        return <<<TEXT
Olá! Para cadastrar um novo centro de custo na {$name}, por favor preencha os dados abaixo:

*Cadastro de Centro de Custo*
Nome do centro de custo:
Tipo de operação (entrada/saída):

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

        return "Siga estritamente as etapas abaixo para cadastrar um centro de custo. "
            ."Execute cada etapa em ordem. Ao final de cada etapa, resuma o que foi coletado "
            ."e aguarde a confirmação do usuário antes de avançar. Se o usuário quiser corrigir "
            ."dados de uma etapa anterior, retorne a ela.\n\n"
            .implode("\n", $lines)."\n\n"
            ."Total de etapas: {$stepCount}.";
    }
}
