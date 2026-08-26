<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('onboard-client')]
#[Description('Guia o modelo a cadastrar um novo cliente e, na sequência, criar um contrato vinculado a um plano.')]
class OnboardClientPrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('clients.create') ?? false;
    }

    /**
     * @return array<int, array{key: string, instruction: string}>
     */
    public function steps(): array
    {
        return [
            [
                'key' => 'client_data',
                'instruction' => 'Colete os dados do cliente: nome completo, documento (CPF/CNPJ), e-mail e telefone.',
            ],
            [
                'key' => 'confirm_client',
                'instruction' => 'Resuma os dados coletados e peça confirmação do usuário antes de criar o cliente.',
            ],
            [
                'key' => 'create_client',
                'instruction' => 'Use a ferramenta de criar cliente para registrá-lo no sistema.',
            ],
            [
                'key' => 'plan',
                'instruction' => 'Liste os planos disponíveis e pergunte qual o cliente deseja.',
            ],
            [
                'key' => 'payment',
                'instruction' => 'Pergunte a forma de pagamento para o contrato.',
            ],
            [
                'key' => 'confirm_contract',
                'instruction' => 'Resuma todos os dados do contrato (plano, pagamento) e peça confirmação antes de finalizar.',
            ],
            [
                'key' => 'create_contract',
                'instruction' => 'Use a ferramenta de criar contrato informando o ID do cliente, o ID do plano e a forma de pagamento.',
            ],
        ];
    }

    public function clientMessage(): string
    {
        $name = config('app.name', 'a academia');

        return <<<TEXT
Olá! Para realizar seu cadastro na {$name}, por favor preencha os dados abaixo:

*Cadastro de Cliente*
Nome completo:
CPF/CNPJ:
E-mail:
Telefone:

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

        return "Siga estritamente as etapas abaixo para o onboard de um cliente. "
            ."Execute cada etapa em ordem. Ao final de cada etapa, resuma o que foi coletado "
            ."e aguarde a confirmação do usuário antes de avançar. Se o usuário quiser corrigir "
            ."dados de uma etapa anterior, retorne a ela.\n\n"
            .implode("\n", $lines)."\n\n"
            ."Total de etapas: {$stepCount}.";
    }
}
