---
name: frontend-ui-engineering
description: Builds production-quality, accessible, responsive Vue 3 and Vuetify 3 UI. Use when building or modifying interfaces and Inertia pages, creating components, implementing layouts, meeting WCAG accessibility requirements, handling loading/empty/error states, or when the output needs to look and feel production-quality rather than AI-generated.
---

# Frontend UI Engineering

## Overview

Build production-quality Vue 3 and Vuetify 3 interfaces that are accessible,
responsive, and visually deliberate. Vuetify, the icon set, the theme, and the
component locations are project facts covered by `vuetify-development`; this
skill covers the quality bar: design consistency, states, and accessibility.

## When to Use

- Building new UI components or Inertia pages
- Modifying existing user-facing interfaces
- Implementing responsive layouts
- Fixing visual or UX issues

## Component Architecture

Reuse before you create. `TablePage.vue`, `DetailsPage.vue`, and
`ReadOnlyDetailsPage.vue` already cover most list and form screens; feature pages
should pass props, options, and slots rather than re-implement them.

```
resources/js/
  components/
    clients/ClientFormFields.vue   # Feature component, owned by its feature
  pages/
    contracts/Details.vue          # Inertia page, one root element
```

- **Prefer composition over configuration.** Compose the shared components and
  pass slot content instead of adding new configuration props to
  `TablePage.vue` or `DetailsPage.vue` for one screen.
- **Keep components focused.** If a component needs two unrelated
  responsibilities, split it. A single responsibility may still be large —
  `TablePage.vue` and the module `Details.vue` pages are intentionally
  data-driven and long. Length alone is not a defect.
- **Separate data from presentation.** The Inertia page owns the props from the
  controller; the component owns rendering and local UI state.
- **Keep labels in Portuguese**, class and component names in English, matching
  the existing pages.

## State Management

```
Local state (ref/reactive)     → Component-specific UI state
Props                          → Data coming from the Inertia page
provide/inject                 → Shared read-mostly context (formatters, options)
URL state (router.get/set)     → Filters, pagination, shareable UI state
```

Do not introduce a client-side store for state the server already owns. Inertia
props plus `router` visit parameters cover the module pages; when you need a new
shared layer, reuse what the project already uses rather than adding one.

## Design System Adherence

### Reference-led UI quality

When a screen needs a distinct visual direction, collect evidence before
choosing a layout:

1. Study two or three screens of the same product area. Record decisions about
   hierarchy, density, navigation, controls, responsive behavior, and interaction
   states.
2. Turn those decisions into a short contract before implementing: the screen's
   job, primary action, required states, responsive rules, and patterns to
   reject.
3. Rebuild the useful structure in the project's own components and visual
   language. Never copy another product's branding, text, imagery, or layout.

If references are unavailable, state the assumptions and follow the existing
pages.

### Avoid the AI Aesthetic

| AI Default | Why It Is a Problem | Production Quality |
|---|---|---|
| Purple/indigo everything | Every app ends up looking identical | Use the theme colors already in the project |
| Excessive gradients | Noise that clashes with the design system | Flat or subtle, matching the theme |
| Rounded everything | Ignores the hierarchy of corner radii | Consistent radius from the theme |
| Generic hero sections | Template-driven layout with no connection to the content | Content-first layouts |
| Lorem ipsum copy | Hides layout problems real content reveals | Realistic content |
| Oversized padding everywhere | Destroys hierarchy and wastes space | The project's spacing scale |
| Stock card grids | Layout shortcut that ignores information priority | Purpose-driven layouts |
| Shadow-heavy design | Competes with content, slows rendering on low-end devices | Subtle or no shadows unless the theme specifies |

### Spacing and Layout

Use the theme's spacing scale (`pa-*`, `mb-*`, `ga-*`) and Vuetify layout helpers
(`d-flex`, `align-center`, `justify-space-between`). Do not invent pixel values
in inline styles.

### Typography and Color

- Respect the theme type hierarchy: one `h1` per page, `h2` for sections, `h3`
  for subsections, body and helper text through the theme classes.
- Use theme tokens (`text-medium-emphasis`, `surface`, `primary`) instead of raw
  hex values.
- Never rely on color alone to convey state; pair it with an icon or text.

## Accessibility (WCAG 2.1 AA)

- **Keyboard**: every interactive element must be reachable and operable with
  the keyboard. Prefer `<v-btn>`, `<v-switch>`, and `<v-menu>` over clickable
  `div`s, which are not focusable.
- **Labels**: every input needs a visible label or an `aria-label`. Vuetify's
  `label` prop satisfies this; `label="&ast;"` keeps it accessible while hiding
  it visually.
- **Focus management**: after a dialog closes, focus returns to the trigger.
  Vuetify handles this through `v-dialog`; verify it in the page.
- **Meaningful states**: no blank screens. Every list has an empty state, every
  async surface has a loading state, and every form has a validation and error
  state.

```vue
<!-- Empty state, visible to a screen reader -->
<v-empty-state
    v-if="!loading && rows.length === 0"
    :title="t('emptyTitle')"
    :text="t('emptyText')"
/>

<!-- Loading state announced as busy -->
<v-skeleton-loader v-if="loading" type="table-row@5" aria-busy="true" />
```

## Responsive Design

Design mobile first, then expand. Vuetify's grid and display utilities replace
breakpoint-specific class strings:

```vue
<v-row>
  <v-col cols="12" md="6">
    <!-- full width on mobile, half on md and up -->
  </v-col>
</v-row>

<div class="d-flex flex-column flex-md-row ga-3">...</div>
```

Check the page at 320px, 768px, 1024px, and 1440px.

## Loading, Transitions, and Feedback

- Skeletons for content, not spinners, especially for Inertia deferred props.
- Optimistic updates only where the Inertia v3 feature already supports rollback;
  keep the user-facing failure path visible.
- Permission-gated actions must be hidden or disabled, never just visually
  de-emphasized.

## Red Flags

- Duplicating what `TablePage.vue` or `DetailsPage.vue` already do
- Inline styles with arbitrary pixel values
- Missing loading, empty, or error states
- Clickable `div`s instead of buttons
- Color as the sole indicator of state
- Generic "AI look": purple gradients, oversized cards, stock layouts
- Text labels that contradict the project's Portuguese convention

## Verification

- [ ] Page renders without console errors
- [ ] All interactive elements are keyboard reachable and operable
- [ ] Every input has a label
- [ ] Loading, empty, error, success, and permission states are handled
- [ ] Responsive at 320px, 768px, 1024px, and 1440px
- [ ] Follows the existing theme tokens, spacing, and component patterns
- [ ] Rebuilt assets are reflected in the UI (`npm run dev` or `npm run build`)

## Common Rationalizations

| Rationalization | Reality |
|---|---|
| "Accessibility is a nice-to-have" | It is a legal requirement in many jurisdictions and an engineering quality standard. |
| "We'll make it responsive later" | Retrofitting responsive design is far harder than building it from the start. |
| "The design isn't final, so I'll skip styling" | Use the theme defaults. Unstyled UI looks broken in review. |
| "This is just a prototype" | Prototypes become production code. |
| "The AI aesthetic is fine for now" | It signals low quality. Follow the project's existing design system from the start. |
