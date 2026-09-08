import { onlyAlphanumeric, onlyDigits } from './formatters.ts';

/**
 * Simple mask engine for the application.
 *
 * Supports fixed tokens (`#`, `A`, `X`, `Z`) and repetition quantifiers,
 * allowing reusable masks to be built without depending on native input.
 */

export type MaskToken = {
    pattern: RegExp;
    transform?: (value: string) => string;
};

type ParsedMaskPart =
    | {
          type: 'literal';
          value: string;
      }
    | {
          type: 'token';
          symbol: string;
          token: MaskToken;
          min: number;
          max: number;
      };

export type UnmaskMode = 'mask' | 'digits' | 'alphanumeric' | 'none';

export const maskTokens: Record<string, MaskToken> = {
    '#': { pattern: /\d/ },
    A: {
        pattern: /[a-zA-Z]/,
        transform: (value: string) => value.toUpperCase(),
    },
    X: { pattern: /[a-zA-Z0-9]/ },
    Z: { pattern: /[a-zA-Z0-9-]/ },
};

export const masks = {
    cep: '#####-###',
    cnpj: '##.###.###/####-##',
    cpf: '###.###.###-##',
    landlinePhone: '(##) ####-####',
    phone: '(##) #####-####',
} as const;

export function phoneMask(value?: string | number | null): string {
    return onlyDigits(value).length > 10 ? masks.phone : masks.landlinePhone;
}

export function documentMask(value?: string | number | null): string {
    return onlyDigits(value).length > 11 ? masks.cnpj : masks.cpf;
}

export function unmaskValue(
    value?: string | number | null,
    mask = '',
    mode: UnmaskMode = 'mask',
    limitToMask = true,
): string {
    const stringValue = String(value ?? '');
    const maskLength = countMaskTokens(mask);

    if (mode === 'none') {
        return stringValue;
    }

    if (mode === 'digits') {
        return limitValue(onlyDigits(stringValue), maskLength, limitToMask);
    }

    if (mode === 'alphanumeric') {
        return limitValue(onlyAlphanumeric(stringValue), maskLength, limitToMask);
    }

    return limitValue(unmaskByMask(stringValue, mask), maskLength, limitToMask);
}

export function applyMask(
    value?: string | number | null,
    mask = '',
    mode: UnmaskMode = 'mask',
): string {
    if (!mask) {
        return String(value ?? '');
    }

    // First normalizes the raw value; then reconstructs the output according to the mask.
    const rawValue = unmaskValue(value, mask, mode);
    const parsedMask = parseMask(mask);
    let rawIndex = 0;
    let formattedValue = '';

    for (const part of parsedMask) {
        if (part.type === 'literal') {
            if (rawIndex < rawValue.length) {
                formattedValue += part.value;
            }

            continue;
        }

        if (!rawValue[rawIndex]) {
            break;
        }

        let matched = 0;

        while (rawIndex < rawValue.length && matched < part.max) {
            const rawChar = rawValue[rawIndex];

            if (!part.token.pattern.test(rawChar)) {
                break;
            }

            formattedValue += part.token.transform?.(rawChar) ?? rawChar;
            rawIndex++;
            matched++;
        }

        if (matched < part.min) {
            break;
        }
    }

    return formattedValue;
}

export const formatMasks = {
    cep: (value?: string | number | null): string =>
        applyMask(value, masks.cep),
    cnpj: (value?: string | number | null): string =>
        applyMask(value, masks.cnpj),
    cpf: (value?: string | number | null): string =>
        applyMask(value, masks.cpf),
    document: (value?: string | number | null): string =>
        applyMask(value, documentMask(value)),
    phone: (value?: string | number | null): string =>
        applyMask(value, phoneMask(value)),
};

function unmaskByMask(value: string, mask: string): string {
    if (!mask) {
        return value;
    }

    // Filters only characters compatible with the tokens declared in the mask.
    const availableTokens = new Set(
        parseMask(mask)
            .filter((part): part is Extract<ParsedMaskPart, { type: 'token' }> => part.type === 'token')
            .map((part) => part.symbol),
    );

    return [...value]
        .filter((char) =>
            [...availableTokens].some((token) =>
                maskTokens[token].pattern.test(char),
            ),
        )
        .join('');
}

function countMaskTokens(mask: string): number {
    const parsedMask = parseMask(mask);
    const hasUnlimitedToken = parsedMask.some(
        (part) => part.type === 'token' && !Number.isFinite(part.max),
    );

    if (hasUnlimitedToken) {
        return 0;
    }

    return parsedMask.reduce((count, part) => {
        if (part.type !== 'token') {
            return count;
        }

        return count + part.max;
    }, 0);
}

function limitValue(
    value: string,
    maskLength: number,
    limitToMask: boolean,
): string {
    if (!limitToMask || maskLength === 0) {
        return value;
    }

    return value.slice(0, maskLength);
}

function parseMask(mask: string): ParsedMaskPart[] {
    // Converts the textual mask into tokenized parts to facilitate application and unmasking.
    const parts: ParsedMaskPart[] = [];

    for (let index = 0; index < mask.length; index++) {
        const symbol = mask[index];
        const token = maskTokens[symbol];

        if (!token) {
            parts.push({ type: 'literal', value: symbol });

            continue;
        }

        const quantifier = parseQuantifier(mask, index + 1);

        parts.push({
            type: 'token',
            symbol,
            token,
            min: quantifier?.min ?? 1,
            max: quantifier?.max ?? 1,
        });

        if (quantifier) {
            index = quantifier.endIndex;
        }
    }

    return parts;
}

function parseQuantifier(
    mask: string,
    index: number,
): { min: number; max: number; endIndex: number } | null {
    // Supported quantifiers: *, +, ? and {min,max}.
    const char = mask[index];

    if (char === '*') {
        return { min: 0, max: Number.POSITIVE_INFINITY, endIndex: index };
    }

    if (char === '+') {
        return { min: 1, max: Number.POSITIVE_INFINITY, endIndex: index };
    }

    if (char === '?') {
        return { min: 0, max: 1, endIndex: index };
    }

    if (char !== '{') {
        return null;
    }

    const endIndex = mask.indexOf('}', index);

    if (endIndex === -1) {
        return null;
    }

    const range = mask.slice(index + 1, endIndex).trim();

    if (!/^\d+(,\d*)?$/.test(range)) {
        return null;
    }

    const [minText, maxText] = range.split(',');
    const min = Number(minText);
    const max = maxText === undefined || maxText === '' ? min : Number(maxText);

    if (!Number.isInteger(min) || !Number.isInteger(max) || min < 0 || max < min) {
        return null;
    }

    return { min, max, endIndex };
}
