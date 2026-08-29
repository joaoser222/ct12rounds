import '@tabler/icons-webfont/dist/tabler-icons.scss';
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import type { ThemeDefinition } from 'vuetify';
import { VBtn } from 'vuetify/components';
import { aliases } from 'vuetify/iconsets/mdi';
import { VFileUpload } from 'vuetify/labs/VFileUpload';
import { pt } from 'vuetify/locale';
import themes from '../themes/index';
import { tabler } from './tabler';

/**
 * Configuração central do Vuetify da aplicação.
 *
 * Este arquivo concentra:
 * - tema, locale e iconset;
 * - aliases de componentes usados no projeto;
 * - defaults visuais para manter consistência entre páginas.
 */

// Garante tipagem compatível com o shape esperado pelo Vuetify.
const typedThemes = themes as Record<string, ThemeDefinition>;

export default createVuetify({
    components: {
        VFileUpload,
    },
    icons: {
        defaultSet: 'tabler',
        aliases,
        sets: {
            tabler,
        },
    },
    locale: {
        locale: 'pt',
        messages: { pt },
    },
    display: {
        thresholds: {
            xs: 360,
            sm: 640,
            md: 960,
            lg: 1264,
            xl: 1904,
        },
    },
    aliases:{
        VClippedButton: VBtn,
        VBtnIcon: VBtn
    },
    defaults: {
        // Defaults globais e por componente evitam repetir props visuais nas páginas.
        global: {
            elevation: 0,
        },
        VTextField: {
            variant: 'outlined',
            color: 'primary',
            density: 'compact',
            hideDetails: 'auto',
            VBtn: {
                variant: 'text',
                density: 'compact',
            }
        },
        VTextarea: {
            variant: 'outlined',
            color: 'primary',
            density: 'compact',
            hideDetails: 'auto',
            VBtn: {
                variant: 'text',
                density: 'compact',
            }
        },
        VNumberInput: {
            variant: 'outlined',
            density: 'comfortable',
            VBtnIcon: {
                density: 'compact',
            },
        },
        VBtn: {
            elevation: 0,
            variant: 'flat',
            class: ['text-none', 'text-body-2', 'font-weight-medium'],
        },
        VBtnIcon: {
            variant: 'text',
            rounded: 'circle'
        },
        VClippedButton: {
            elevation: 0,
            variant: 'flat',
            rounded: 'sm',
            class: ['clipped-object'],
        },
        VBtnGroup: {
            border: 'sm',
            class: ['bg-lighten', 'pa-1', 'ga-2', 'border-surface-variant'],
        },
        VCard: {
            border: 'sm',
            elevation: 0,
            color: 'surface',
            class: ['pa-3', 'border-surface-variant'],
            VCard: {
                color: 'lighten',
            }
        },
        VList: {
            nav: true,
            lines: false,
            elevation: 0,
            class: ['px-2', 'py-1'],
            VListItem: {
                class: ['py-2', 'px-3', 'my-0-5'],
                minHeight: '36px',
            },
            VListGroup: {
                expandIcon: 'ti ti-chevron-down',
                collapseIcon: 'ti ti-chevron-up',
            },
        },

        VSelect: {
            variant: 'outlined',
            density: 'compact',
            menuIcon: 'ti ti-chevron-down',
            hideDetails: 'auto',
            menuProps: {
                elevation: 2,
                contentClass: ['border-0','rounded-sm'],
            },
        },

        VAutocomplete: {
            variant: 'outlined',
            density: 'compact',
            menuIcon: 'ti ti-chevron-down',
            itemTitle: 'name',
            itemValue: 'code',
            hideDetails: 'auto',
            menuProps: {
                elevation: 2,
                contentClass: ['border-0','rounded-sm'],
            },
        },
        VChip: {
            color: 'success',
            variant: 'flat',
            size: 'small',
            density: 'comfortable',
            elevation: 0,
            class: ['text-caption', 'font-weight-medium'],
        },
        VTable: {
            density: 'compact',
            class: ['border-sm', 'border-surface-variant', 'rounded']
        },
        VAlert: {
            variant: 'tonal',
            prominent: false,
            border: 'start',
        },
        VTabs: {
            alignTabs: 'start',
            color: 'primary',
            class: ['my-2'],
            hideSlider: false,
            density: 'comfortable',
            VTab: {
                variant: 'text',
                class: ['mx-0', 'px-3', 'text-none', 'text-body-2'],
            },
        },
        VExpansionPanels: {
            variant: 'accordion' as const,
            elevation: 0,
            class: ['border-sm', 'border-surface-variant'],
            VExpansionPanel: {
                expandIcon: 'ti ti-chevron-down',
                collapseIcon: 'ti ti-chevron-up',
                elevation: 0,
                color: 'surface',
                VExpansionPanelTitle: {
                class: ['text-body-2', 'font-weight-medium'],
                },
            },
        },
        VRadio: {
            density: 'compact',
            color: 'primary',
            falseIcon: 'ti ti-circle',
            trueIcon: 'ti ti-circle-dot',
            class: ['pa-0'],
        },
        VCheckbox: {
            density: 'compact',
            color: 'accent',
            falseIcon: 'ti ti-square',
            trueIcon: 'ti ti-square-check',
            trueValue: true,
            falseValue: false,
            hideDetails: 'auto',
        },
        VCheckboxBtn: {
            falseIcon: 'ti ti-square',
            trueIcon: 'ti ti-square-check',
        },
        VPagination: {
            activeColor: 'primary',
            totalVisible: 7,
            prevIcon: 'ti ti-chevron-left',
            nextIcon: 'ti ti-chevron-right',
            density: 'comfortable',
        },
        VProgressCircular: {
            color: 'primary',
            indeterminate: true,
            size: 'default',
            width: 2,
        },
        VProgressLinear: {
            color: 'primary',
            rounded: 'pill',
            height: 4,
        },
        VDatePicker: {
            elevation: 0,
            nextIcon: 'ti ti-chevron-right',
            prevIcon: 'ti ti-chevron-left',
            modeIcon: 'ti ti-chevron-up',
            VBtn: {
                variant: 'flat',
                elevation: 0,
            },
        },
        VDialog: {
            elevation: 4,
        },
        VMenu: {
            VList: {
                class: ['border-sm', 'border-surface-variant'],
            }
        },
        VTooltip: {
            location: 'top',
        },
        VDivider: {
            class: ['border-surface-variant'],
        },
        VNavigationDrawer: {
            elevation: 0,
            border: 'e-sm',
        },
        VToolbar: {
            color: 'surface',
            class: ['pa-2'],
            density: 'compact',
            border: '0',
        }
    },
    theme: {
        defaultTheme: 'dark',
        themes: typedThemes,
    },
});
