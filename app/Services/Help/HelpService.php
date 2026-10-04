<?php

declare(strict_types=1);

namespace App\Services\Help;

/**
 * Read-only help corpus. Picks the few guides that match the question and hands
 * them to the LLM as context. There is no database access here by design: the
 * chat answers "how do I do this", and a data question becomes a link.
 */
final class HelpService
{
    private const MAX_GUIDES = 3;

    /**
     * @return array<string, string> guide slug => markdown body
     */
    public function guides(): array
    {
        $guides = [];

        foreach (glob(base_path('docs/help/*.md')) ?: [] as $path) {
            $contents = file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            $guides[basename($path, '.md')] = $contents;
        }

        return $guides;
    }

    /**
     * Guides whose title or body mentions the question terms, best match first.
     * Keeps the prompt small: sending all 11 guides on every message would cost
     * more than the schema we just removed.
     *
     * @return array<string, string>
     */
    public function relevantGuides(string $question): array
    {
        $terms = $this->terms($question);

        if ($terms === []) {
            return [];
        }

        $guides = $this->guides();
        $scored = [];

        foreach ($guides as $slug => $body) {
            $haystack = mb_strtolower($body);
            $score = 0;

            foreach ($terms as $term) {
                $score += substr_count($haystack, $term);
            }

            if ($score > 0) {
                $scored[$slug] = $score;
            }
        }

        arsort($scored);

        $selected = [];

        foreach (array_slice($scored, 0, self::MAX_GUIDES, true) as $slug => $score) {
            $selected[$slug] = $guides[$slug];
        }

        return $selected;
    }

    /**
     * Words longer than three characters, lowercased. Portuguese accents are
     * folded so "cobrança" matches "cobranca".
     *
     * @return array<int, string>
     */
    private function terms(string $question): array
    {
        $folded = $this->fold($question);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $folded, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($words, fn (string $w): bool => mb_strlen($w) > 3)));
    }

    private function fold(string $value): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e',
            'í' => 'i', 'ì' => 'i',
            'ó' => 'o', 'õ' => 'o', 'ô' => 'o', 'ò' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ù' => 'u',
            'ç' => 'c',
        ];

        return mb_strtolower(strtr($value, $map));
    }

    /**
     * The system prompt. Reads like an instruction to a new support employee:
     * answer how-to from the guides, and hand over a link instead of data.
     */
    public function systemPrompt(): string
    {
        return implode("\n\n", [
            'Você é o assistente interno de ajuda do sistema '.config('app.name', 'a academia').'.',
            'Responda sempre em português do Brasil.',
            '',
            'Você explica COMO USAR o sistema. Você não executa ações e não consulta dados.',
            '',
            'Regras:',
            '1. Use os guias abaixo como sua única fonte sobre o sistema. Se a resposta não está nos guias, diga que não sabe e sugira procurar no time.',
            '2. Nunca responda valores, listas ou totais. Se a pergunta depende de um dado do banco, entregue o link da tela que mostra o dado já filtrado.',
            '3. Escreva o link em markdown na sua resposta, usando a URL exata da lista de telas. Nunca invente uma URL.',
            '4. Seja direto. Use listas e passos curtos. Sem preâmbulo, sem repetir a pergunta.',
            '',
            'Telas disponíveis:',
            HelpIndex::linkInstructions(),
        ]);
    }

    /**
     * Guides injected as context for one question.
     */
    public function contextFor(string $question): string
    {
        $guides = $this->relevantGuides($question);

        if ($guides === []) {
            return '';
        }

        $blocks = [];

        foreach ($guides as $slug => $body) {
            $blocks[] = "### Guia: {$slug}\n".$body;
        }

        return implode("\n\n", $blocks);
    }
}