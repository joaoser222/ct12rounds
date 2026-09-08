import { onlyDigits } from './formatters.ts';

/**
 * Reusable validators for Vuetify forms.
 *
 * Keeps rules simple and synchronous, returning `true` or the error message
 * expected by input components.
 */

export const required = (value: unknown) => !!value || 'É obrigatório';
export const email = (value: string) => /.+@.+\..+/.test(value) || 'E-mail inválido';

export const cpf = (value: string) => {
    const d = onlyDigits(value);
    const msg = 'CPF inválido';

    if (d.length !== 11 || /^(\d)\1{10}$/.test(d)) {
        return msg;
    }

    const n = d.split('').map(Number);
    // Reuses the official check digit rule.
    const calc = (len: number) => {
        const sum = n
            .slice(0, len)
            .reduce((acc, v, i) => acc + v * (len + 1 - i), 0);
        const rest = sum % 11;

        return rest < 2 ? 0 : 11 - rest;
    };

    return calc(9) === n[9] && calc(10) === n[10] ? true : msg;
};

export const cnpj = (value: string) => {
    const d = onlyDigits(value);
    const msg = 'CNPJ inválido';

    if (d.length !== 14 || /^(\d)\1{13}$/.test(d)) {
        return msg;
    }

    const n = d.split('').map(Number);
    // Reuses the official check digit rule.
    const calc = (len: number) => {
        let weight = len - 7;
        const sum = n.slice(0, len).reduce((acc, v) => {
            const w = weight--;

            if (weight < 1) {
                weight = 8;
            }

            return acc + v * w;
        }, 0);
        const rest = sum % 11;

        return rest < 2 ? 0 : 11 - rest;
    };

    return calc(12) === n[12] && calc(13) === n[13] ? true : msg;
};

export function cpfCnpj(value: string): true | string {
    const d = onlyDigits(value);

    return d.length > 11 ? cnpj(d) : cpf(d);
}

export const phone = (value: string) => {
    const d = onlyDigits(value);
    const invalid = 'Telefone inválido';

    if (d.length < 10 || d.length > 11) {
        return invalid;
    }

    if (d.length === 11 && d[2] !== '9') {
        return invalid;
    }

    return true;
};

export const cep = (value: string) => {
    const d = onlyDigits(value);

    return d.length === 8 ? true : 'CEP inválido';
};

export const same = (confirmationValue: string) => (value: string) =>
    value === confirmationValue ? true : 'Os valores não conferem';
export const minLength = (length: number) => (value: string) =>
    !value || value.length >= length || `Deve ter no mínimo ${length} caracteres`;
export const maxLength = (length: number) => (value: string) =>
    !value || value.length <= length || `Deve ter no máximo ${length} caracteres`;
export const exactLength = (length: number) => (value: string) =>
    !value || value.length === length || `Deve ter ${length} caracteres`;
