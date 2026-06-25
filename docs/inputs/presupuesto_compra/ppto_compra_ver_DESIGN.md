---
name: Agro-Industrial Executive
colors:
  surface: '#f8f9ff'
  surface-dim: '#ccdbf3'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d5e3fc'
  on-surface: '#0d1c2e'
  on-surface-variant: '#44474c'
  inverse-surface: '#233144'
  inverse-on-surface: '#eaf1ff'
  outline: '#74777d'
  outline-variant: '#c4c6cd'
  surface-tint: '#4f6073'
  primary: '#041627'
  on-primary: '#ffffff'
  primary-container: '#1a2b3c'
  on-primary-container: '#8192a7'
  inverse-primary: '#b7c8de'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#221200'
  on-tertiary: '#ffffff'
  tertiary-container: '#3e2400'
  on-tertiary-container: '#ca8100'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d2e4fb'
  primary-fixed-dim: '#b7c8de'
  on-primary-fixed: '#0b1d2d'
  on-primary-fixed-variant: '#38485a'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#ffddb8'
  tertiary-fixed-dim: '#ffb95f'
  on-tertiary-fixed: '#2a1700'
  on-tertiary-fixed-variant: '#653e00'
  background: '#f8f9ff'
  on-background: '#0d1c2e'
  surface-variant: '#d5e3fc'
typography:
  display:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  h1:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
    letterSpacing: -0.01em
  h2:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
    letterSpacing: '0'
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
    letterSpacing: '0'
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1'
    letterSpacing: 0.05em
  status-badge:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1'
    letterSpacing: '0'
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 16px
  md: 24px
  lg: 32px
  xl: 48px
  sidebar-width: 280px
  gutter: 24px
  container-padding: 40px
---

## Brand & Style

The design system is engineered for the agro-industrial sector, where precision, reliability, and large-scale resource management are paramount. The brand personality is authoritative yet unobtrusive, acting as a high-end executive tool that facilitates rapid decision-making. 

The aesthetic follows a **Modern Corporate** style. It prioritizes clarity over decoration, using ample whitespace and a disciplined grid to convey a sense of calm and control. The visual language avoids trendy gimmicks, opting instead for a "sober luxury" feel that would be at home in a high-stakes boardroom or a fleet management center. The emotional response should be one of total confidence in the data presented.

## Colors

The palette is anchored by **Deep Navy Blue**, providing a foundation of stability and professional depth. This color is used for primary actions, navigation, and core branding elements. 

- **Primary (Deep Navy):** Used for the persistent sidebar and primary buttons to signify authority.
- **Secondary (Emerald Green):** Reserved strictly for positive budget statuses, successful transactions, and "Approved" states.
- **Text (Slate Grey):** Employed for body copy and UI labels to reduce eye strain compared to pure black, maintaining a modern, sophisticated tone.
- **Accents:** A muted amber is used for "Pending" states, and a sharp red is utilized for "Over Budget" warnings, ensuring critical financial alerts are immediately visible against the clean white background.

## Typography

This design system utilizes **Inter** exclusively to ensure maximum legibility for dense tabular data and financial metrics. The typographic hierarchy is strictly enforced to guide the user's eye through complex information.

Headlines use a tighter letter-spacing and heavier weights to command attention, while body text is optimized for readability with generous line heights. Capitalized labels are used for table headers and secondary navigation elements to create a clear structural distinction from interactive data points.

## Layout & Spacing

The layout utilizes a **Fixed-Fluid Hybrid** model. A persistent sidebar is fixed to the left at 280px, providing a constant anchor for navigation. The main content area uses a 12-column fluid grid with 24px gutters, allowing data tables and dashboard widgets to scale across different screen resolutions while maintaining rigorous alignment.

Spacing follows an 8px rhythmic scale to ensure consistent proportions. Large "container-padding" of 40px is used for the primary dashboard view to create an expansive, high-end feel that prevents the interface from feeling cluttered, even when displaying significant amounts of data.

## Elevation & Depth

To maintain a "sober" and modern look, the design system avoids heavy gradients or dramatic shadows. Instead, it uses **Ambient Shadows** to define the z-axis. 

Cards and interactive containers sit on the base white background with a very soft, diffused shadow (0px 4px 20px rgba(0, 0, 0, 0.05)). This subtle lift differentiates content areas without breaking the flat aesthetic. The persistent sidebar uses a slight tonal shift or a 1px border (#E2E8F0) rather than a shadow to maintain its role as a structural anchor rather than an overlay.

## Shapes

The shape language is professional and architectural. A **Soft** roundedness (4px - 8px) is applied to buttons, input fields, and cards. This slight rounding takes the "edge" off the industrial data without making the app feel consumer-grade or playful. 

Status badges use a more pronounced rounding (full pill-shape) to distinguish them from clickable buttons and structural containers, making them instantly recognizable as status indicators.

## Components

### Buttons
Primary buttons are solid Deep Navy with white text. Secondary buttons use a Slate Grey outline. All buttons feature a 4px corner radius and height optimized for clickability (40px for standard, 48px for primary dashboard actions).

### Status Badges
Badges are pill-shaped with a light background tint and a darker text color for high contrast:
- **Approved:** Light Emerald background / Deep Emerald text.
- **Pending:** Light Amber background / Dark Amber text.
- **Over Budget:** Light Red background / Deep Red text.

### Data Tables
Tables are the heart of the system. They feature high-readability row heights (52px), subtle horizontal dividers, and no vertical borders. The header row uses the `label-caps` typography style with a very light grey background (#F8FAFC) to anchor the columns.

### Flat Cards
Cards are used to group related metrics (e.g., "Fuel Consumption Summary"). They feature a white fill, a 1px border (#F1F5F9), and the standard soft ambient shadow.

### Persistent Sidebar
The sidebar uses a Deep Navy background with an opacity-based hover state for navigation items. Icons should be minimalist line drawings with a 2px stroke width to match the Inter typeface weight.