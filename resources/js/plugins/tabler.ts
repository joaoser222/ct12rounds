import { h } from 'vue'
import type { IconSet, IconProps } from 'vuetify'

/**
 * Tabler iconset adapter for the format expected by Vuetify.
 *
 * Allows using short icon names in the application while maintaining rendering
 * via CSS classes from the webfont package.
 */

const tabler: IconSet = {
  component: (props: IconProps) =>
    h('i', { class: `ti ti-${props.icon}` }),
}

export { tabler }
