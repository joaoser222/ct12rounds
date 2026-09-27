<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\ContactNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactNormalizerTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('nationalPhones')]
    public function test_it_prefixes_the_country_code_on_national_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer()->phone($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nationalPhones(): array
    {
        return [
            'mobile with mask' => ['(11) 98888-8888', '5511988888888'],
            'mobile without mask' => ['11988888888', '5511988888888'],
            'landline with mask' => ['(62) 3333-4444', '556233334444'],
            'landline without mask' => ['6233334444', '556233334444'],
        ];
    }

    #[DataProvider('alreadyInternationalPhones')]
    public function test_it_is_idempotent_on_international_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer()->phone($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function alreadyInternationalPhones(): array
    {
        return [
            'international mobile' => ['5511988888888', '5511988888888'],
            'international mobile with mask' => ['+55 (11) 98888-8888', '5511988888888'],
            'international landline' => ['556233334444', '556233334444'],
        ];
    }

    #[DataProvider('rejectedPhones')]
    public function test_it_rejects_numbers_without_an_area_code(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer()->phone($input);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejectedPhones(): array
    {
        return [
            'local number with eight digits' => ['33334444'],
            'too short' => ['9888888'],
            'too long' => ['55119888888881'],
        ];
    }

    public function test_it_returns_null_for_absent_values(): void
    {
        $this->assertNull($this->normalizer()->phone(null));
        $this->assertNull($this->normalizer()->phone(''));
        $this->assertNull($this->normalizer()->phone('---'));
        $this->assertNull($this->normalizer()->document(null));
        $this->assertNull($this->normalizer()->document(''));
    }

    public function test_it_strips_formatting_from_documents(): void
    {
        $this->assertSame('11144477735', $this->normalizer()->document('111.444.777-35'));
        $this->assertSame('11222333000181', $this->normalizer()->document('11.222.333/0001-81'));
    }

    public function test_it_reads_the_country_code_from_the_setting(): void
    {
        Setting::query()->create([
            'name' => 'default_country_code',
            'label' => 'Código de País dos Telefones',
            'content' => '1',
            'object_type' => 'text',
            'group' => 'general',
        ]);

        $this->assertSame('1', $this->normalizer()->countryCode());
        $this->assertSame('111988888888', $this->normalizer()->phone('(11) 98888-8888'));
    }

    public function test_it_falls_back_to_brazil_when_the_setting_is_blank_or_missing(): void
    {
        $this->assertSame('55', $this->normalizer()->countryCode());

        Setting::query()->create([
            'name' => 'default_country_code',
            'label' => 'Código de País dos Telefones',
            'content' => '',
            'object_type' => 'text',
            'group' => 'general',
        ]);

        $this->assertSame('55', $this->normalizer()->countryCode());
    }

    private function normalizer(): ContactNormalizer
    {
        return $this->app->make(ContactNormalizer::class);
    }
}
