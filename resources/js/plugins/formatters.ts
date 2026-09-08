/**
 * Lightweight formatting and sanitization helpers used by the UI.
 *
 * The idea here is to centralize common formats without coupling the application
 * to specific components.
 */

export function onlyDigits(value?: string | number | null): string {
    return String(value ?? '').replace(/\D/g, '');
}

export function onlyAlphanumeric(value?: string | number | null): string {
    return String(value ?? '').replace(/[^a-zA-Z0-9]/g, '');
}

const dateFormatter = new Intl.DateTimeFormat('pt-BR');
const dateTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
});
const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

export function formatDate(
    value?: string | number | Date | null,
    fallback = '-',
): string {
    const date = normalizeDate(value);

    return date ? dateFormatter.format(date) : fallback;
}

export function formatDateTime(
    value?: string | number | Date | null,
    fallback = '-',
): string {
    const date = normalizeDate(value);

    return date ? dateTimeFormatter.format(date) : fallback;
}

export function formatCurrency(
    value?: string | number | null,
    fallback = '-',
): string {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const amount = Number(value);

    return Number.isFinite(amount) ? currencyFormatter.format(amount) : fallback;
}

function normalizeDate(value?: string | number | Date | null): Date | null {
    // Normalizes heterogeneous inputs before date formatting.
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}
