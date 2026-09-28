import { h } from 'vue'
import type { IconAliases, IconSet, IconProps } from 'vuetify'

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

/**
 * Resolves the icon aliases Vuetify uses as defaults in its own components.
 *
 * These values must stay unprefixed because the icon set component above adds
 * the `ti ti-` prefix. Using the MDI aliases that ship with Vuetify makes every
 * default fall through to a class such as `ti ti-$mdiChevronLeft`, which has no
 * glyph in the Tabler webfont and leaves the icon invisible.
 *
 * Limited to the aliases Vuetify components actually reference as defaults.
 */
const aliases: Partial<IconAliases> = {
  calendar: 'calendar',
  checkboxIndeterminate: 'square-minus',
  clear: 'x',
  close: 'x',
  collapse: 'chevron-up',
  complete: 'check',
  delete: 'trash',
  delimiter: 'circle',
  dropdown: 'chevron-down',
  edit: 'pencil',
  error: 'alert-circle',
  expand: 'chevron-down',
  eyeDropper: 'color-picker',
  first: 'chevrons-left',
  last: 'chevrons-right',
  loading: 'loader-2',
  next: 'chevron-right',
  prev: 'chevron-left',
  radioOff: 'circle',
  radioOn: 'circle-dot',
  ratingEmpty: 'star',
  ratingFull: 'star-filled',
  sort: 'arrows-sort',
  sortAsc: 'sort-ascending',
  sortDesc: 'sort-descending',
  subgroup: 'chevron-right',
  tableGroupCollapse: 'chevron-up',
  tableGroupExpand: 'chevron-down',
}

export { aliases, tabler }
