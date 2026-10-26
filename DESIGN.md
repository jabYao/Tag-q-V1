---
name: Apex Horology
colors:
  # Canonical palette — single source of truth. Must match the "Colors" section.
  primary: '#008B8B'
  primary-hover: '#007373'
  on-primary: '#FFFFFF'
  secondary: '#F26B3A'
  secondary-hover: '#E05A29'
  on-secondary: '#FFFFFF'
  background: '#FFFFFF'
  surface: '#FFFFFF'
  surface-subtle: '#F8FAFC'
  surface-muted: '#F1F5F9'
  border-soft: '#E2E8F0'
  text-primary: '#000000'
  text-secondary: '#475569'
  text-tertiary: '#94A3B8'
  error: '#BA1A1A'
  on-error: '#FFFFFF'
typography:
  display-hero:
    fontFamily: Space Grotesk
    fontSize: 72px
    fontWeight: '700'
    lineHeight: 76px
    letterSpacing: -0.03em
  display-hero-mobile:
    fontFamily: Space Grotesk
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-xl:
    fontFamily: Space Grotesk
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 52px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Space Grotesk
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Space Grotesk
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 38px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Space Grotesk
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 30px
    letterSpacing: 0em
  headline-sm:
    fontFamily: Space Grotesk
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 26px
    letterSpacing: 0em
  body-xl:
    fontFamily: Work Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-md:
    fontFamily: Work Sans
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: 0em
  body-sm:
    fontFamily: Work Sans
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-caps:
    fontFamily: Space Grotesk
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.15em
  telemetry-num:
    fontFamily: Space Grotesk
    fontSize: 14px
    fontWeight: '700'
    lineHeight: 18px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-sm: 1rem
  gutter-lg: 2rem
  margin: 2rem
  margin-mobile: 1.25rem
  margin-desktop: 3.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style

This design system manifests the synthesis of precision haute horlogerie and high-performance technical engineering. Grounded in a crystalline, high-luminance light aesthetic, it pairs architectural restraint with kinetic track-grade accents. 

### Aesthetic Philosophy
- **Clean Mechanical Purity:** A pure white foundation (`#FFFFFF`) eliminates visual noise, presenting micro-mechanical craftsmanship with clinical, museum-grade fidelity.
- **Deep Cyan Structure:** Deep teal (`#008B8B`) anchors navigational anchors, chronographic bezels, and corporate architecture, lending authoritative weight and horological provenance.
- **Kinetic Blaze Accents:** High-energy sport orange (`#F26B3A`) serves as the active focal mechanism, reserved strictly for primary conversions, telemetry indicators, and critical live actions.
- **Micro-Precision Outlines:** Crisp 1px slate borders (`#E2E8F0`) with deliberate absolute black (`#000000`) accents mimic machined titanium tolerances and precision-etched instrument bezels.

The visual impression is luminous, exacting, and performance-oriented—evoking the surgical precision of an open-dial chronograph presented under daylight studio illumination.

## Colors

The color system delivers high-contrast clarity on light surfaces, balancing deep institutional cyan with high-octane orange call-to-actions.

### Palette Architecture
- **Primary (`#008B8B` - Deep Cyan / Teal):** Architectural spine. Deployed across navigation headers, footer containers, brand badges, and technical dividers.
- **Secondary (`#F26B3A` - High-Energy Orange):** Kinetic accelerator. Reserved strictly for primary action buttons, active status chips, cart conversions, and highlighted metrics.
- **Neutral Canvas (`#FFFFFF` - Pure White):** Default viewport background, baseline card faces, and modal viewports.
- **Secondary Surfaces (`#F8FAFC` & `#F1F5F9`):** Sub-surface tiers for spec tables, inset data wells, filter toolbars, and comparative column backdrops.
- **Structural Outlines:** 
  - `border-soft` (`#E2E8F0`): Hairline 1px dividers framing bento grids, inputs, and containment cells.
  - Absolute Black (`#000000`): Precision accents, typographic headings, high-contrast badges, and interactive outlines.

## Typography

The typographic balance bridges technical instrumentation with clear editorial legibility.

### Hierarchy & Pairing
- **Headlines & Telemetry (`Space Grotesk`):** Delivers a crisp, instrument-grade architectural signature. Used for main headings, collection codes, price tiers, and telemetry readouts.
- **Body & Longform (`Work Sans`):** Provides a dependable, rational structure for storytelling, horological specifications, editorial descriptions, and form controls.
- **Telemetry Readouts (`telemetry-num`):** Enforces tabular numbering for mechanical specs (vibrations per hour, case diameter, water resistance).
- **Technical Markers (`label-caps`):** Must be rendered in full uppercase with `0.15em` tracking for caliber categories, movement references, and status tags.

## Layout & Spacing

The structural layout relies on an architectonic 12-column grid constrained to a maximum width of `1440px` for optimal product presentation.

### Responsive Rules
- **Desktop (1024px+):** 12-column layout with `gutter-lg` (`2rem`) and `margin-desktop` (`3.5rem`). Bento configurations showcase watch macro photography across 7 columns alongside 5-column technical specification grids.
- **Tablet (768px - 1023px):** 8-column layout with `gutter` (`1.5rem`) and `margin` (`2rem`). Bento grids shift into 4-column paired blocks.
- **Mobile (< 768px):** 4-column layout with `gutter-sm` (`1rem`) and `margin-mobile` (`1.25rem`). Horological galleries stack vertically into a singular unified stream.

## Elevation & Depth

Visual hierarchy is maintained via razor-sharp physical containment rather than heavy, muddy drop shadows.

### Elevation Levels
- **Level 0 (Base Canvas):** Pure white `#FFFFFF` flat foundation.
- **Level 1 (Recessed Well):** Inset specification cards, parameter monitors, and filter trays using `#F8FAFC` or `#F1F5F9` with a flush `1px solid #E2E8F0` border.
- **Level 2 (Elevated Bento Card):** Pure white `#FFFFFF` surface framed by `1px solid #E2E8F0` and an ultra-diffused atmospheric shadow: `box-shadow: 0 4px 20px -2px rgba(0, 139, 139, 0.05)`.
- **Level 3 (Floating Overlays & Sticky Nav):** Header bar in `#008B8B` or floating modal containers in `#FFFFFF` framed by a `1px solid #000000` edge and `box-shadow: 0 12px 32px -4px rgba(0, 0, 0, 0.08)`.

## Shapes

The geometry adheres to level `1` (Soft), creating controlled `0.25rem` (4px) precision radii that mirror high-grade machined components, chamfered steel bezels, and watch link interfaces.

### Radius Assignments
- **Base / Elements (`0.25rem`):** Badges, input fields, checkboxes, action buttons, and telemetry chips.
- **Containers (`rounded-lg` / `0.5rem`):** Bento product cards, specification modules, and interactive drawers.
- **Modals & Flyouts (`rounded-xl` / `0.75rem`):** Floating dialogues, quick-view flyouts, and macro zoom viewports.

## Components

### Buttons
- **Primary CTA:** Background `#F26B3A`, text `#FFFFFF`, border none, font `Space Grotesk` (`label-caps`). Height: `48px`, padding: `0 24px`, radius `0.25rem`. Hover: darkens to `#E05A29` with a subtle elevation shift.
- **Institutional Primary:** Background `#008B8B`, text `#FFFFFF`, border none, font `Space Grotesk` (`label-caps`). Height: `48px`, padding: `0 24px`, radius `0.25rem`. Hover: shifts to `#007373`.
- **Secondary Technical Button:** Background `#FFFFFF`, text `#000000`, border `1px solid #E2E8F0`, font `Space Grotesk`. Hover: border shifts to `#000000` with background `#F8FAFC`.
- **Ghost Action:** Text `#008B8B`, background transparent, border none. Hover: text color shifts to `#F26B3A` with an inline directional arrow nudge.

### Product & Showcase Cards
- Encased in a pure white `#FFFFFF` surface with a crisp `1px solid #E2E8F0` border.
- Category header displays caliber or complication in `label-caps` (`#475569`).
- Product stage showcases the dial on a clean neutral `#F8FAFC` background inset.
- Metric footer features case diameter, water resistance, and price in `telemetry-num` (`#000000`), anchored by a compact `#F26B3A` purchase button.

### Chips & Badges
- **Status Badge (Active / In Stock):** Background `#F26B3A` at 10% opacity, border `1px solid #F26B3A`, text `#F26B3A`, typography `label-caps`.
- **Technical Chip (Caliber / Spec):** Background `#F1F5F9`, border `1px solid #E2E8F0`, text `#000000`, typography `label-caps`.

### Form Fields & Measurement Inputs
- Height `48px`, background `#FFFFFF`, border `1px solid #E2E8F0`, corner radius `0.25rem`.
- Text input in `Work Sans` (`body-md`), placeholder text `#94A3B8`.
- Focus state: border shifts to `1px solid #008B8B` with an ambient glow (`box-shadow: 0 0 0 3px rgba(0, 139, 139, 0.15)`).

### Checkbox & Radio Controls
- **Checkbox:** `18px x 18px`, `0.25rem` radius, border `1px solid #E2E8F0`. Checked: filled with `#008B8B`, displaying a crisp white icon.
- **Radio Selector:** `18px x 18px` circle, border `1px solid #E2E8F0`. Checked: outer ring `#008B8B` encasing a solid `#008B8B` inner pip.

### Data Tables & Spec Breakdowns
- Clean horizontal borders in `1px solid #E2E8F0`.
- Left-aligned attribute names in `Work Sans` (`body-sm`, `#475569`).
- Right-aligned engineering specifications in `Space Grotesk` (`telemetry-num`, `#000000`).
- Alternating row zebra fills in `#F8FAFC` for high-density movement schematics.
