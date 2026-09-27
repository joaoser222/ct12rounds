<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfRuleTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function validDocuments(): array
    {
        return [
            'valid cpf' => ['11144477735'],
            'valid cpf masked' => ['111.444.777-35'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidDocuments(): array
    {
        return [
            'first check digit wrong' => ['11144477725'],
            'second check digit wrong' => ['11144477736'],
            'repeated digits' => ['99999999999'],
            'too short' => ['1114447773'],
            'too long' => ['111444777355'],
            'letters only' => ['abcdefghijk'],
            'empty' => [''],
            'fixture used across the suite' => ['11144477730'],
        ];
    }

    #[DataProvider('validDocuments')]
    public function test_it_accepts_a_cpf_with_both_check_digits(string $document): void
    {
        $this->assertTrue(Cpf::isValid($document));
    }

    #[DataProvider('invalidDocuments')]
    public function test_it_rejects_anything_that_is_not_a_cpf(string $document): void
    {
        $this->assertFalse(Cpf::isValid($document));
    }

    public function test_it_rejects_non_string_values(): void
    {
        $this->assertFalse(Cpf::isValid(null));
        $this->assertFalse(Cpf::isValid(11144477735));
        $this->assertFalse(Cpf::isValid(['11144477735']));
    }

    public function test_the_rule_fails_with_a_readable_message(): void
    {
        $messages = [];

        (new Cpf)->validate('document', '11144477730', function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertCount(1, $messages);
        // The :attribute placeholder is resolved by the validation pipeline,
        // not by the rule itself.
        $this->assertStringContainsString('CPF válido', $messages[0]);
        $this->assertStringContainsString(':attribute', $messages[0]);
    }

    public function test_the_rule_stays_silent_for_a_valid_document(): void
    {
        $messages = [];

        (new Cpf)->validate('document', '111.444.777-35', function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([], $messages);
    }
}
