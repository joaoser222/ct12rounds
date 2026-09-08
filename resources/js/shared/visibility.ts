import type { Option } from '@/shared/options';

export type VisibilityValue = 'visible' | 'hidden' | 'archived';

export type VisibilityOption = Option<VisibilityValue>;

// Shared options between frontend visibility filters and actions.
export const visibilityOptions: VisibilityOption[] = [
    { title: 'Visível', value: 'visible', icon: 'ti ti-eye' },
    { title: 'Oculto', value: 'hidden', icon: 'ti ti-eye-off' },
    { title: 'Arquivado', value: 'archived', icon: 'ti ti-archive' },
];
