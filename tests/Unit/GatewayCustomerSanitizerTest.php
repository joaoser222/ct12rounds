<?php

namespace Tests\Unit;

use App\Services\Gateway\GatewayCustomerSanitizer;
use PHPUnit\Framework\TestCase;

class GatewayCustomerSanitizerTest extends TestCase
{
    public function test_it_sanitizes_customer_identity_contact_and_address(): void
    {
        $sanitizer = new GatewayCustomerSanitizer;

        $attributes = $sanitizer->sanitize([
            'name' => '  JOÃO   DA  SILVA  ',
            'email' => ' JOAO@EXAMPLE.COM ',
            'mobilePhone' => '(11) 99999-9999',
            'cpfCnpj' => '123.456.789-01',
            'address' => ' RUA   DAS FLORES ',
            'addressNumber' => ' s/n ',
            'complement' => ' APTO   101 ',
            'province' => ' CENTRO ',
            'postalCode' => '01310-100',
            'cityName' => ' SÃO   PAULO ',
            'state' => ' sp ',
        ]);

        $this->assertSame('João da Silva', $attributes['name']);
        $this->assertSame('joao@example.com', $attributes['email']);
        $this->assertSame('11999999999', $attributes['phone']);
        $this->assertSame('12345678901', $attributes['document']);
        $this->assertSame('Rua das Flores', $attributes['address']);
        $this->assertSame('S/N', $attributes['address_number']);
        $this->assertSame('Apto 101', $attributes['address_complement']);
        $this->assertSame('Centro', $attributes['address_district']);
        $this->assertSame('01310100', $attributes['address_postal_code']);
        $this->assertSame('São Paulo', $attributes['address_city']);
        $this->assertSame('SP', $attributes['address_state']);
    }

    public function test_it_preserves_known_acronyms_while_capitalizing_names(): void
    {
        $sanitizer = new GatewayCustomerSanitizer;

        $this->assertSame(
            'Maria da Silva CPF',
            $sanitizer->sanitizeName('MARIA DA SILVA CPF'),
        );
        $this->assertSame(
            'Empresa EPP',
            $sanitizer->sanitizeName('EMPRESA EPP'),
        );
    }

    public function test_it_detects_when_provider_data_requires_normalization(): void
    {
        $sanitizer = new GatewayCustomerSanitizer;
        $customer = [
            'name' => 'JOÃO SILVA',
            'postalCode' => '01310-100',
            'state' => 'sp',
        ];

        $attributes = $sanitizer->sanitize($customer);

        $this->assertTrue($sanitizer->hasRemoteChanges($customer, $attributes));
        $this->assertFalse($sanitizer->hasRemoteChanges([
            'name' => 'João Silva',
            'postalCode' => '01310100',
            'state' => 'SP',
        ], $attributes));
    }
}
