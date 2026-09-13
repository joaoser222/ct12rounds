<?php

namespace App\Http\Controllers;

use App\Actions\HiringLeads\CreateSiteLeadAction;
use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Http\Requests\PublicHiringLeadRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\GatewayAccount;
use App\Models\GatewayCreditCard;
use App\Models\GatewayCustomer;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Models\Setting;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\Gateway\GatewayAdapterResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicHiringLeadController extends Controller
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly GatewayAdapterResolver $gatewayResolver,
        private readonly CreateSiteLeadAction $createLead,
    ) {}

    public function create(Request $request): Response
    {
        $plan = null;
        $coupon = null;
        $couponWarning = null;
        $contract = null;

        if ($contractToken = $request->query('contract')) {
            $contract = Contract::query()
                ->where('registration_token', $contractToken)
                ->first();

            if ($contract !== null) {
                $contract->load(['plan', 'coupon']);
                $plan = $contract->plan;
                $coupon = $contract->coupon;
            }
        }

        if ($plan === null && $planSlug = $request->query('plan')) {
            $plan = Plan::query()
                ->where('public_slug', $planSlug)
                ->where('visibility', 'visible')
                ->first();
        }

        if ($coupon === null && $couponCode = $request->query('coupon')) {
            $coupon = $this->activeCouponByCode($couponCode);

            if ($coupon === null && $couponCode !== null) {
                $couponWarning = 'O cupom informado não está disponível. O cadastro será realizado sem desconto.';
            }
        }

        if ($coupon !== null && ! $coupon->isAvailable()) {
            $couponWarning = 'Este cupom atingiu o limite de utilizações. O cadastro será realizado sem desconto.';
            $coupon = null;
        }

        $isContractFlow = $contract !== null;

        return Inertia::render('public/Registration', [
            'plan' => $plan?->only(['id', 'name', 'public_slug']),
            'requiresLegalRepresentative' => $plan?->requiresLegalRepresentative() ?? false,
            'coupon' => $coupon?->code,
            'couponWarning' => $couponWarning,
            'contract' => $isContractFlow ? [
                'id' => $contract->id,
                'token' => $contract->registration_token,
                'plan' => $contract->plan?->name,
            ] : null,
            'terms' => $this->hiringTerms(),
            'success' => $request->session()->pull('hiring_lead_success'),
            'retryClientId' => $request->session()->get('registration_client_id'),
        ]);
    }

    private function hiringTerms(): ?string
    {
        $setting = Setting::query()
            ->where('name', 'hiring_terms')
            ->first();

        $content = $setting?->content;

        return is_string($content) && trim($content) !== '' ? $content : null;
    }

    public function store(PublicHiringLeadRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['contract'])) {
            return $this->storeContractRegistration($data, $request);
        }

        return $this->storePreRegistration($data, $request);
    }

    private function storePreRegistration(array $data, PublicHiringLeadRequest $request): RedirectResponse
    {
        $this->createLead->execute($data);

        $request->session()->put('hiring_lead_success', true);

        return redirect()->route('public.register');
    }

    private function storeContractRegistration(array $data, PublicHiringLeadRequest $request): RedirectResponse
    {
        $contract = Contract::query()
            ->where('registration_token', $data['contract'])
            ->first();

        if ($contract === null) {
            return back()->withErrors(['contract' => 'O link de cadastro não é válido.'])->withInput();
        }

        if ($contract->client_id !== null) {
            return back()->withErrors(['contract' => 'Este contrato já possui um cadastro vinculado.'])->withInput();
        }

        $contract->load(['plan', 'coupon']);
        $plan = $contract->plan;
        $coupon = $contract->coupon;

        $document = preg_replace('/\D/', '', (string) $data['document']);

        try {
            $client = DB::transaction(function () use ($data, $document, $request) {
                $client = $this->clientRepository->findByDocument($document);

                if ($client === null) {
                    $client = $this->clientRepository->create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'document' => $document,
                        'gender' => $data['gender'] ?? null,
                        'birth_date' => $data['birth_date'] ?? null,
                        'address' => $data['address'] ?? null,
                        'address_number' => $data['address_number'] ?? null,
                        'address_complement' => $data['address_complement'] ?? null,
                        'address_district' => $data['address_district'] ?? null,
                        'address_state' => $data['address_state'] ?? null,
                        'address_city' => $data['address_city'] ?? null,
                        'address_postal_code' => $data['address_postal_code'] ?? null,
                        'legal_representative' => $data['legal_representative'] ?? false,
                        'legal_representative_name' => $data['legal_representative_name'] ?? null,
                        'legal_representative_document' => ! empty($data['legal_representative_document']) ? preg_replace('/\D/', '', (string) $data['legal_representative_document']) : null,
                        'legal_representative_birth_date' => $data['legal_representative_birth_date'] ?? null,
                        'status' => 'active',
                        'client_source' => 'site',
                    ]);
                }

                $request->session()->put('registration_client_id', $client->id);

                return $client;
            });

            $gatewayAccount = GatewayAccount::query()->first();

            if ($gatewayAccount === null) {
                return back()->withErrors(['card_number' => 'Sistema de pagamento não configurado.'])->withInput();
            }

            $gateway = $this->gatewayResolver->paymentAdapter($gatewayAccount);
            $gatewayCustomer = $this->resolveOrCreateGatewayCustomer($gateway, $client);

            $cardToken = $gateway->tokenizeCreditCard([
                'creditCardHolderInfo' => [
                    'name' => $data['card_holder_name'],
                    'cpfCnpj' => $document,
                    'email' => $data['email'],
                ],
                'creditCard' => [
                    'creditCardNumber' => $data['card_number'],
                    'creditCardExpirationMonth' => $data['card_expiry_month'],
                    'creditCardExpirationYear' => $data['card_expiry_year'],
                    'creditCardCcv' => $data['card_cvv'],
                ],
            ]);

            $this->storeCreditCard($gateway, $cardToken, $gatewayCustomer);

            HiringLead::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'document' => $document,
                'gender' => $data['gender'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'address' => $data['address'] ?? null,
                'address_number' => $data['address_number'] ?? null,
                'address_complement' => $data['address_complement'] ?? null,
                'address_district' => $data['address_district'] ?? null,
                'address_state' => $data['address_state'] ?? null,
                'address_city' => $data['address_city'] ?? null,
                'address_postal_code' => $data['address_postal_code'] ?? null,
                'status' => HiringLeadStatus::NEW->value,
                'source' => HiringLeadSource::CONTRACT->value,
                'payment_method' => 'credit_card',
                'legal_representative' => $data['legal_representative'] ?? false,
                'legal_representative_name' => $data['legal_representative_name'] ?? null,
                'legal_representative_document' => ! empty($data['legal_representative_document']) ? preg_replace('/\D/', '', (string) $data['legal_representative_document']) : null,
                'legal_representative_birth_date' => $data['legal_representative_birth_date'] ?? null,
                'visibility' => 'visible',
                'accepted_at' => CarbonImmutable::now(),
                'plan_id' => $plan?->getKey(),
                'coupon_id' => $coupon?->getKey(),
                'contract_id' => $contract->id,
                'client_id' => $client->id,
            ]);

            if ($coupon !== null) {
                $coupon->increment('used_count');
            }

            $request->session()->forget('registration_client_id');
            $request->session()->put('hiring_lead_success', true);

            return redirect()->route('public.register');
        } catch (\Exception $e) {
            return back()->withErrors([
                'card_number' => 'Erro ao processar pagamento: '.$e->getMessage().'. Os dados do cartão podem estar incorretos.',
            ])->withInput();
        }
    }

    private function resolveOrCreateGatewayCustomer(PaymentGatewayAdapter $gateway, Client $client): GatewayCustomer
    {
        $existing = GatewayCustomer::query()
            ->where('holder_type', Client::class)
            ->where('holder_id', $client->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $gateway->createCustomer($client);
    }

    private function storeCreditCard(PaymentGatewayAdapter $gateway, ?array $cardToken, GatewayCustomer $gatewayCustomer): void
    {
        if ($cardToken === null) {
            throw new \RuntimeException('Falha ao tokenizar o cartão de crédito.');
        }

        $lastDigits = $cardToken['creditCardNumber'] ?? $cardToken['lastFourDigits'] ?? null;

        if ($lastDigits === null) {
            throw new \RuntimeException('Dados do cartão inválidos retornados pelo gateway.');
        }

        GatewayCreditCard::create([
            'gateway_card_token' => $cardToken['creditCardToken'] ?? $cardToken['creditCardNumber'] ?? null,
            'gateway_reference_key' => $cardToken['creditCardNumber'] ?? null,
            'card_brand' => $cardToken['creditCardBrand'] ?? $cardToken['brand'] ?? null,
            'last_digits' => $lastDigits,
            'gateway_account_id' => $gatewayCustomer->gateway_account_id,
            'gateway_customer_id' => $gatewayCustomer->id,
        ]);
    }

    public function retryPayment(PublicHiringLeadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $clientId = $request->session()->get('registration_client_id');
        $contractToken = $data['contract'] ?? null;

        if ($clientId === null || $contractToken === null) {
            return back()->withErrors(['card_number' => 'Sessão expirada. Preencha os dados novamente.'])->withInput();
        }

        $contract = Contract::query()
            ->where('registration_token', $contractToken)
            ->first();

        if ($contract === null) {
            return back()->withErrors(['contract' => 'O link de cadastro não é válido.'])->withInput();
        }

        $client = Client::find($clientId);

        if ($client === null) {
            $request->session()->forget('registration_client_id');
            return back()->withErrors(['card_number' => 'Cliente não encontrado. Preencha os dados novamente.'])->withInput();
        }

        $plan = $contract->plan;
        $coupon = $contract->coupon;
        $document = $client->document;

        try {
            $gatewayAccount = GatewayAccount::query()->first();

            if ($gatewayAccount === null) {
                return back()->withErrors(['card_number' => 'Sistema de pagamento não configurado.'])->withInput();
            }

            $gateway = $this->gatewayResolver->paymentAdapter($gatewayAccount);
            $gatewayCustomer = $this->resolveOrCreateGatewayCustomer($gateway, $client);

            $cardToken = $gateway->tokenizeCreditCard([
                'creditCardHolderInfo' => [
                    'name' => $data['card_holder_name'],
                    'cpfCnpj' => $document,
                    'email' => $client->email,
                ],
                'creditCard' => [
                    'creditCardNumber' => $data['card_number'],
                    'creditCardExpirationMonth' => $data['card_expiry_month'],
                    'creditCardExpirationYear' => $data['card_expiry_year'],
                    'creditCardCcv' => $data['card_cvv'],
                ],
            ]);

            $this->storeCreditCard($gateway, $cardToken, $gatewayCustomer);

            HiringLead::query()->create([
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'document' => $document,
                'gender' => $client->gender,
                'birth_date' => $client->birth_date,
                'address' => $client->address,
                'address_number' => $client->address_number,
                'address_complement' => $client->address_complement,
                'address_district' => $client->address_district,
                'address_state' => $client->address_state,
                'address_city' => $client->address_city,
                'address_postal_code' => $client->address_postal_code,
                'status' => HiringLeadStatus::NEW->value,
                'source' => HiringLeadSource::CONTRACT->value,
                'payment_method' => 'credit_card',
                'legal_representative' => $client->legal_representative,
                'legal_representative_name' => $client->legal_representative_name,
                'legal_representative_document' => $client->legal_representative_document,
                'legal_representative_birth_date' => $client->legal_representative_birth_date,
                'visibility' => 'visible',
                'accepted_at' => CarbonImmutable::now(),
                'plan_id' => $plan?->getKey(),
                'coupon_id' => $coupon?->getKey(),
                'contract_id' => $contract->id,
                'client_id' => $client->id,
            ]);

            if ($coupon !== null) {
                $coupon->increment('used_count');
            }

            $request->session()->forget('registration_client_id');
            $request->session()->put('hiring_lead_success', true);

            return redirect()->route('public.register');
        } catch (\Exception $e) {
            return back()->withErrors([
                'card_number' => 'Erro ao processar pagamento: '.$e->getMessage().'. Os dados do cartão podem estar incorretos.',
            ])->withInput();
        }
    }

    private function activeCouponByCode(string $code): ?Coupon
    {
        $coupon = Coupon::query()
            ->where('code', mb_strtoupper((string) $code))
            ->where('visibility', 'visible')
            ->first();

        if ($coupon === null) {
            return null;
        }

        if ($coupon->expiration_date !== null && $coupon->expiration_date->isBefore(CarbonImmutable::today())) {
            return null;
        }

        return $coupon;
    }
}
