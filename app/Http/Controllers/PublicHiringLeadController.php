<?php

namespace App\Http\Controllers;

use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Http\Requests\PublicHiringLeadRequest;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicHiringLeadController extends Controller
{
    public function create(Request $request): Response
    {
        $plan = null;
        $coupon = null;
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
        }

        return Inertia::render('public/Cadastro', [
            'plan' => $plan?->only(['id', 'name', 'public_slug']),
            'coupon' => $coupon?->code,
            'contract' => $contract !== null ? [
                'id' => $contract->id,
                'token' => $contract->registration_token,
                'plan' => $contract->plan?->name,
            ] : null,
            'terms' => $this->hiringTerms(),
            'success' => $request->session()->pull('hiring_lead_success'),
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

        $plan = null;
        $coupon = null;
        $contractId = null;

        if (! empty($data['contract'])) {
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
            $contractId = $contract->id;
        } else {
            if (! empty($data['plan'])) {
                $plan = Plan::query()
                    ->where('public_slug', $data['plan'])
                    ->where('visibility', 'visible')
                    ->first();

                if ($plan === null) {
                    return back()->withErrors(['plan' => 'O plano informado não está disponível.'])->withInput();
                }
            }

            if (! empty($data['coupon'])) {
                $coupon = $this->activeCouponByCode($data['coupon']);

                if ($coupon === null) {
                    return back()->withErrors(['coupon' => 'O cupom informado não está disponível ou expirou.'])->withInput();
                }
            }
        }

        HiringLead::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'document' => preg_replace('/\D/', '', (string) $data['document']),
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
            'source' => ($plan !== null || $contractId !== null) ? HiringLeadSource::CONTRACT->value : HiringLeadSource::SITE->value,
            'visibility' => 'visible',
            'accepted_at' => CarbonImmutable::now(),
            'plan_id' => $plan?->getKey(),
            'coupon_id' => $coupon?->getKey(),
            'contract_id' => $contractId,
        ]);

        $request->session()->put('hiring_lead_success', true);

        return redirect()->route('public.cadastro');
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