import type { Directive } from 'vue';

type TextCaseMode = 'upper' | 'lower' | 'capitalize';

const prepositions = new Set([
    'a', 'à', 'as',
    'ao', 'aos',
    'com', 'como',
    'da', 'das', 'de', 'do', 'dos',
    'em', 'entre',
    'na', 'nas', 'no', 'nos',
    'para', 'perante', 'por',
    'sem', 'sob', 'sobre',
    'até', 'desde', 'contra',
]);

export function capitalize(value: string): string {
    return value
        .split(/\s+/)
        .map((word, i) => {
            if (i > 0 && prepositions.has(word.toLowerCase())) {
                return word.toLowerCase();
            }

            if (word.length > 1 && word === word.toUpperCase()) {
                return word;
            }

            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        })
        .join(' ');
}

const transforms: Record<TextCaseMode, (v: string) => string> = {
    upper: (v) => v.toUpperCase(),
    lower: (v) => v.toLowerCase(),
    capitalize,
};

const processing = new WeakSet<EventTarget>();

export const vTextCase: Directive<HTMLInputElement, TextCaseMode> = {
    mounted(el, binding) {
        const input = el.tagName === 'INPUT' ? el : el.querySelector('input');

        if (!input) return;

        const getTransform = () => transforms[binding.value] ?? transforms.upper;

        input.addEventListener('input', () => {
            if (processing.has(input)) return;

            processing.add(input);

            const start = input.selectionStart;
            const end = input.selectionEnd;

            input.value = getTransform()(input.value);
            input.dispatchEvent(new Event('input', { bubbles: true }));

            input.setSelectionRange(start, end);

            processing.delete(input);
        });
    },
    updated(el, binding) {
        if (binding.value !== binding.oldValue) {
            const input = el.tagName === 'INPUT' ? el : el.querySelector('input');

            if (input) {
                const transform = transforms[binding.value] ?? transforms.upper;
                input.value = transform(input.value);
            }
        }
    },
};
