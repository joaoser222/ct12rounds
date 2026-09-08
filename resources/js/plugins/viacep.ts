import { inject, type App, type InjectionKey } from 'vue';
import { onlyDigits } from '@/plugins/formatters';
import { capitalize } from '@/directives/textCase';

/**
 * Shared integration with ViaCEP.
 *
 * The plugin exposes helpers to:
 * - look up postal codes;
 * - fill forms with the project's address fields;
 * - reuse the integration via `useViaCep()` or global injection.
 */

export type ViaCepResponse = {
    cep: string;
    logradouro: string;
    complemento: string;
    unidade: string;
    bairro: string;
    localidade: string;
    uf: string;
    estado: string;
    regiao: string;
    ibge: string;
    gia: string;
    ddd: string;
    siafi: string;
    erro?: boolean;
};

export type ViaCepAddress = {
    postalCode: string;
    street: string;
    complement: string;
    district: string;
    city: string;
    state: string;
};

export type AddressFieldMap = {
    postalCode: string;
    street: string;
    complement: string;
    district: string;
    city: string;
    state: string;
};

export type FillAddressOptions = {
    fields?: Partial<AddressFieldMap>;
    onlyEmpty?: boolean;
    signal?: AbortSignal;
};

export type AddressForm = Record<string, unknown>;

export const defaultAddressFields: AddressFieldMap = {
    postalCode: 'address_postal_code',
    street: 'address',
    complement: 'address_complement',
    district: 'address_district',
    city: 'address_city',
    state: 'address_state',
};

export class ViaCepError extends Error {
    public constructor(message: string) {
        super(message);
        this.name = 'ViaCepError';
    }
}

export async function lookupCep(
    value: string | number | null | undefined,
    signal?: AbortSignal,
): Promise<ViaCepAddress | null> {
    const cep = onlyDigits(value);

    if (cep.length !== 8) {
        return null;
    }

    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
        signal,
    });

    if (!response.ok) {
        throw new ViaCepError('Não foi possível consultar o CEP.');
    }

    const data = (await response.json()) as ViaCepResponse;

    if (data.erro) {
        return null;
    }

    return {
        postalCode: onlyDigits(data.cep),
        street: capitalize(`${data.logradouro}`),
        complement: capitalize(`${data.complemento}`),
        district: capitalize(`${data.bairro}`),
        city: capitalize(`${data.localidade}`),
        state: `${data.uf}`.toLocaleUpperCase(),
    };
}

export async function fillAddressFromCep(
    form: AddressForm,
    cep: string | number | null | undefined,
    options: FillAddressOptions = {},
): Promise<ViaCepAddress | null> {
    const address = await lookupCep(cep, options.signal);

    if (!address) {
        return null;
    }

    fillAddressFields(form, address, options);

    return address;
}

export function fillAddressFields(
    form: AddressForm,
    address: ViaCepAddress,
    options: FillAddressOptions = {},
): void {
    // Allows mapping the ViaCEP response to different field names per screen.
    const fields = {
        ...defaultAddressFields,
        ...options.fields,
    };

    setFieldValue(form, fields.postalCode, address.postalCode, options);
    setFieldValue(form, fields.street, address.street, options);
    setFieldValue(form, fields.complement, address.complement, options);
    setFieldValue(form, fields.district, address.district, options);
    setFieldValue(form, fields.city, address.city, options);
    setFieldValue(form, fields.state, address.state, options);
}

function setFieldValue(
    form: AddressForm,
    field: string,
    value: string,
    options: FillAddressOptions,
): void {
    if (!value) {
        return;
    }

    if (options.onlyEmpty && String(form[field] ?? '') !== '') {
        return;
    }

    form[field] = value;
}

export const viaCep = {
    defaultAddressFields,
    fillAddressFields,
    fillAddressFromCep,
    lookupCep,
};

export type ViaCepPlugin = typeof viaCep;

export const viaCepKey: InjectionKey<ViaCepPlugin> = Symbol('viaCep');

export function useViaCep(): ViaCepPlugin {
    return inject(viaCepKey, viaCep);
}

export default {
    install(app: App): void {
        // Makes the integration available both via inject and as a global property.
        app.provide(viaCepKey, viaCep);
        app.config.globalProperties.$viaCep = viaCep;
    },
};

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $viaCep: ViaCepPlugin;
    }
}
