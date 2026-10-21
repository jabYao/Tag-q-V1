---
name: Tag q — Admin agenda and storefront escaparate
description: Two coexisting systems for a Colombian wristwatch shop — a navy dispatch day sheet for operations and a paper display window for the product detail.
colors:
  navy: "#00052c"
  navy-soft: "#090f3d"
  lume: "#c8f169"
  lume-deep: "#b8de5b"
  paper: "#f6f3ea"
  paper-deep: "#eee9dc"
  white: "#ffffff"
  field-shell: "#fffdf8"
  ink: "#141821"
  hair: "#d6d0c2"
  hair-soft: "#e8e2d5"
  muted: "#5f636b"
  muted-warm: "#6e6a5e"
  row-hover: "#fbf8ef"
  row-late: "#fbf2e4"
  form-bar: "#fbf9f2"
  amber: "#e0a33b"
  amber-text: "#785100"
  steel: "#5f667a"
  steel-text: "#4f5668"
  green: "#2f7a55"
  green-text: "#29684b"
  red: "#d64b3c"
  red-text: "#9f3327"
  red-deep: "#7d2b23"
  pending-wash: "#fdf1dc"
  pending-line: "#dcb777"
  paid-wash: "#f5fbe8"
  paid-line: "#a9cd8b"
  prep-wash: "#f2f3f6"
  prep-line: "#c5c9d2"
  shipped-wash: "#f0f1f7"
  shipped-line: "#b4b8c8"
  delivered-wash: "#eef8f2"
  delivered-line: "#a8cbb7"
  critical-wash: "#fdf3f0"
  critical-line: "#e3bcb2"
  success-wash: "#f8fbef"
  success-line: "#c8d8b0"
  tagmark: "#a39b89"
  tagmark-late: "#ad7720"
  week-bar: "#a9b0c0"
  meter-track: "#ece7db"
  # Storefront — Escaparate editorial
  store-ink: "#111111"
  store-paper: "#f5f4f1"
  store-line: "#ddd9d1"
  store-muted: "#6d6a63"
  store-stage: "#5a5750"
  store-white: "#ffffff"
  store-stock: "#546a4c"
  store-state-red: "#9f3327"
typography:
  display:
    fontFamily: "Bebas Neue, Arial, sans-serif"
    fontSize: "128px"
    fontWeight: 400
    lineHeight: 0.76
    letterSpacing: "0.012em"
  page-title-compact:
    fontFamily: "Bebas Neue, Arial, sans-serif"
    fontSize: "88px"
    fontWeight: 400
    lineHeight: 0.8
    letterSpacing: "0.012em"
  page-title-form:
    fontFamily: "Bebas Neue, Arial, sans-serif"
    fontSize: "78px"
    fontWeight: 400
    lineHeight: 0.8
    letterSpacing: "0.012em"
  section-title:
    fontFamily: "Bebas Neue, Arial, sans-serif"
    fontSize: "23px"
    fontWeight: 400
    lineHeight: 1
    letterSpacing: "0.015em"
  figure:
    fontFamily: "Outfit, Arial, sans-serif"
    fontSize: "34px"
    fontWeight: 300
    lineHeight: 1
    letterSpacing: "-0.02em"
    fontFeature: "tnum"
  figure-small:
    fontFamily: "Outfit, Arial, sans-serif"
    fontSize: "27px"
    fontWeight: 300
    lineHeight: 1
    letterSpacing: "-0.02em"
    fontFeature: "tnum"
  body:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.45
  data:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "11.5px"
    fontWeight: 400
    lineHeight: 1.45
  micro:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "9px"
    fontWeight: 600
    lineHeight: 1.45
    letterSpacing: "0.18em"
  ui-label:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "11px"
    fontWeight: 500
    lineHeight: 1
  wordmark:
    fontFamily: "Jost, Arial, sans-serif"
    fontSize: "15px"
    fontWeight: 500
    lineHeight: 1
    letterSpacing: "0.2em"
  # Storefront — Escaparate editorial
  store-title:
    fontFamily: "Outfit, Arial, sans-serif"
    fontSize: "clamp(2.6rem, 3.4vw, 3.5rem)"
    fontWeight: 300
    lineHeight: 0.91
    letterSpacing: "-0.022em"
  store-price:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "27px"
    fontWeight: 500
    lineHeight: 1.1
    letterSpacing: "-0.025em"
    fontFeature: "tnum"
  store-detail-heading:
    fontFamily: "Bebas Neue, Arial, sans-serif"
    fontSize: "35px"
    fontWeight: 400
    lineHeight: 1
    letterSpacing: "0.03em"
  store-copy:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.65
  store-detail-copy:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.6
  store-micro:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "9.5px"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.14em"
  store-cta:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "10.5px"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.18em"
rounded:
  none: "0px"
  dot: "50%"
spacing:
  "2xs": "4px"
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "20px"
  xl: "24px"
  "2xl": "28px"
  "3xl": "32px"
  "4xl": "38px"
components:
  button-primary:
    backgroundColor: "{colors.navy}"
    textColor: "{colors.paper}"
    typography: "{typography.ui-label}"
    rounded: "{rounded.none}"
    padding: "0 12px"
    height: "34px"
  button-primary-hover:
    backgroundColor: "{colors.navy-soft}"
    textColor: "{colors.white}"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    typography: "{typography.ui-label}"
    rounded: "{rounded.none}"
    padding: "0 12px"
    height: "34px"
  button-accent:
    backgroundColor: "{colors.lume}"
    textColor: "{colors.navy}"
    typography: "{typography.ui-label}"
    rounded: "{rounded.none}"
    padding: "0 12px"
    height: "34px"
  button-accent-hover:
    backgroundColor: "{colors.lume-deep}"
    textColor: "{colors.navy}"
  button-danger:
    backgroundColor: "transparent"
    textColor: "{colors.red-text}"
    typography: "{typography.ui-label}"
    rounded: "{rounded.none}"
    padding: "0 12px"
    height: "34px"
  input-text:
    backgroundColor: "{colors.field-shell}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "8px 10px"
    height: "38px"
  card:
    backgroundColor: "{colors.white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
  nav-link:
    backgroundColor: "{colors.navy}"
    textColor: "{colors.paper}"
    rounded: "{rounded.none}"
    padding: "0 10px"
    height: "35px"
  nav-link-active:
    backgroundColor: "rgba(255, 255, 255, 0.08)"
    textColor: "{colors.white}"
  stamp-pending:
    backgroundColor: "{colors.pending-wash}"
    textColor: "{colors.amber-text}"
    rounded: "{rounded.none}"
    padding: "0 6px"
    height: "19px"
  stamp-paid:
    backgroundColor: "{colors.paid-wash}"
    textColor: "{colors.green-text}"
    rounded: "{rounded.none}"
    padding: "0 6px"
    height: "19px"
  stamp-preparation:
    backgroundColor: "{colors.prep-wash}"
    textColor: "{colors.steel-text}"
    rounded: "{rounded.none}"
    padding: "0 6px"
    height: "19px"
  stamp-shipped:
    backgroundColor: "{colors.shipped-wash}"
    textColor: "{colors.navy}"
    rounded: "{rounded.none}"
    padding: "0 6px"
    height: "19px"
  stamp-delivered:
    backgroundColor: "{colors.delivered-wash}"
    textColor: "{colors.green-text}"
    rounded: "{rounded.none}"
    padding: "0 6px"
    height: "19px"
  stamp-terminal:
    backgroundColor: "{colors.critical-wash}"
    textColor: "{colors.red-text}"
    rounded: "{rounded.none}"
    padding: "0 6px"
    height: "19px"
  # Storefront — Escaparate editorial
  store-buy-cta:
    backgroundColor: "{colors.store-ink}"
    textColor: "{colors.store-white}"
    typography: "{typography.store-cta}"
    rounded: "{rounded.none}"
    height: "52px"
    width: "100%"
  store-quantity:
    backgroundColor: "{colors.store-white}"
    textColor: "{colors.store-ink}"
    rounded: "{rounded.none}"
    width: "122px"
    height: "42px"
  store-thumb:
    backgroundColor: "{colors.store-paper}"
    rounded: "{rounded.none}"
    width: "64px"
    height: "64px"
  store-spec-row:
    backgroundColor: "transparent"
    textColor: "{colors.store-ink}"
    rounded: "{rounded.none}"
    padding: "8px 0"
---

# Design System: Tag q — Admin agenda and storefront escaparate

## Overview

**Creative North Star: "Agenda operativa — the dispatch day sheet."**

This is the working surface of a small wristwatch shop: a navy watch-dispatch rail framing a warm-paper day sheet. The screen is read in one scan — the month's money, the order queue, the five working states, the stock about to fail, and the money still uncollected — then it is acted on through a row, a stamp, or a pending-payment item. The admin is in Spanish because the shop's daily language is Spanish; the typography carries the hierarchy, not translation.

The composition is dense and flat on purpose. White working cards sit directly on warm paper with a single hairline between them; there is no shadow, no gradient, no rounded container, and no nested card. Bebas Neue gives the page its dispatcher's voice, Outfit 300 carries every figure, and Inter carries every word of data. The navy rail is the one dark mass, and lume is the one electric note. The result should read as an operations ledger that was designed, not as a SaaS dashboard that was assembled.

State is the visual system. Every order row carries its state twice: as a 3px left rail and as a bordered stamp with a 4px dot, both driven by the same status tokens and the exact Spanish labels. Lume marks live or paid work, amber marks money still pending, steel marks preparation, navy marks dispatch, green marks delivery, and red marks the terminal or the critical. The same grammar governs the five-cell state rail, the agenda, the order detail, stock badges, role badges, and empty states.

**Two systems, two scopes.** This document records two coexisting visual systems. The admin system above is normative for the admin surfaces. The public product-detail system in **Storefront — Escaparate editorial** is normative only for that route. Neither composition is a global storefront rule, and the admin shell is not restyled by this document.

**Key Characteristics:**
- A fixed 232px navy rail against a warm #F6F3EA work area.
- Square, hairline-bounded surfaces; zero box shadows and zero decorative gradients.
- One display voice (Bebas Neue), one figure voice (Outfit 300), one data voice (Inter).
- Status carried by a left rail, a bordered stamp, and a 4px dot — never by color alone.
- Lume reserved for live, paid, selection, and the single Collections creation action.
- A five-state operational rail; terminal states live in a quiet footer line.
- An eight-row agenda beside a 300px operational rail that stacks and collapses.
- Spanish UI labels, COP money, and a visible provisional low-stock cutoff.

## Colors

The palette is a warm-paper ledger with a navy structural mass and one electric signal; every neutral is warmed toward the paper, except the steel that belongs to "En preparación".

### Primary

- **Dispatch Navy** (`colors.navy`): the rail, page titles, card titles, primary buttons, focus outlines, selection text, and the shipped state. It is structure, not decoration.
- **Navy Soft** (`colors.navy-soft`): the hover fill of a primary button only.
- **Lume Signal** (`colors.lume`): the live/paid signal — the paid stamp dot, the paid row rail, the live status cell rule, the active nav rail, the unread count chip, the skip link, the week's current-day bar, text selection, the admin presence dot, and the Collections creation button.
- **Lume Deep** (`colors.lume-deep`): the hover fill of the lume button.

### Secondary

- **Pending Amber** (`colors.amber`): the pending dot and rail, the low-stock meter, and the pending status color. Its text-safe partner is `colors.amber-text`.
- **Working Steel** (`colors.steel`): the "En preparación" dot and state rail; `colors.steel-text` is the stamp text.
- **Delivered Green** (`colors.green`): the "Entregado" dot; `colors.green-text` is the delivered stamp text and the active-status text. Green never marks money or navigation.

### Tertiary

- **Critical Red** (`colors.red`): out-of-stock rails and meters, destructive hover fill, canceled/refunded dots, error dots, and invalid field borders. `colors.red-text` is the readable text form; `colors.red-deep` is the error-list text.

### Neutral

- **Warm Paper** (`colors.paper`): the application background, rail-adjacent paper, and quiet control fills.
- **Deep Paper** (`colors.paper-deep`): disabled inputs and quiet pressed surfaces.
- **Work White** (`colors.white`): cards, side rail, tables, and form surfaces.
- **Field Shell** (`colors.field-shell`): the resting background of text inputs, selects, and textareas.
- **Ink** (`colors.ink`): primary body text and table data.
- **Hairline** (`colors.hair`): the 1px boundary of cards, tables, forms, search, badges, and the topbar.
- **Soft Hairline** (`colors.hair-soft`): internal row and section dividers where a full hairline would be too loud.
- **Muted Ink** (`colors.muted`): secondary labels, block titles, table headers, and inactive roles.
- **Warm Muted** (`colors.muted-warm`): micro labels, metadata, timestamps, and helper copy; it is the warm-neutral partner of `colors.muted`.
- **Row Hover** (`colors.row-hover`): the table row hover wash.
- **Late Row** (`colors.row-late`): the background of a `pendiente_de_pago` agenda or order row.
- **Form Bar** (`colors.form-bar`): the sticky-looking action bar at the foot of a form.
- **Tag Mark** (`colors.tagmark`): the small hang-tag glyph in tables; `colors.tagmark-late` marks a pending row.
- **Week Bar** (`colors.week-bar`): the six non-current day bars.
- **Meter Track** (`colors.meter-track`): the unfilled track of the low-stock meter.

### Status Color System

The status class is the single source of the four local custom properties (`--status-color`, `--status-text`, `--status-border`, `--status-bg`). It is applied to the table row, the stamp, the rail cell, or the dot; child elements inherit it. Never restate a status color locally.

| Order value | UI label (Spanish) | Dot / row rail | Stamp fill | Stamp line | Stamp text |
|---|---|---|---|---|---|
| `pendiente_de_pago` | "Pendiente de pago" | `colors.amber` | `colors.pending-wash` | `colors.pending-line` | `colors.amber-text` |
| `pagado` | "Pagado" | `colors.lume` | `colors.paid-wash` | `colors.paid-line` | `colors.green-text` |
| `en_preparacion` | "En preparación" | `colors.steel` | `colors.prep-wash` | `colors.prep-line` | `colors.steel-text` |
| `enviado` | "Enviado" | `colors.navy` | `colors.shipped-wash` | `colors.shipped-line` | `colors.navy` |
| `entregado` | "Entregado" | `colors.green` | `colors.delivered-wash` | `colors.delivered-line` | `colors.green-text` |
| `cancelado` | "Cancelado" | `colors.red` | `colors.critical-wash` | `colors.critical-line` | `colors.red-text` |
| `reembolsado` | "Reembolsado" | `colors.red` | `colors.critical-wash` | `colors.critical-line` | `colors.red-text` |

The five-state rail shows `pendiente_de_pago`, `pagado`, `en_preparacion`, `enviado`, `entregado` in that order. The `pagado` cell alone receives the live treatment: a 9% lume wash and a 3px lume bottom rule instead of the standard 2px status rule. `cancelado` and `reembolsado` never get a rail cell; when their combined count is above zero they appear as a right-aligned terminal line below the rail, each with its dot and exact label.

### Named Rules

**The Lume Scarcity Rule.** Lume is a state signal, never general chrome. It may appear on a paid/live indicator, the active nav rail, selection, the current-day bar, the admin presence dot, the count chip, and the single Collections creation action. It may not fill a generic primary button, a heading, a card, or a decorative rule.

**The Status-Only Color Rule.** Amber, steel, green, and red belong to order or inventory state. Do not use them for emphasis, illustration, or a non-state badge. If an element is not a state, it is ink on paper.

## Typography

**Display Font:** Bebas Neue (with Arial, sans-serif)
**Body Font:** Inter (with Arial, sans-serif)
**Figure Font:** Outfit 300 (with Arial, sans-serif)
**Wordmark Font:** Jost 500 (with Arial, sans-serif)

**Character:** A condensed dispatcher's headline over a neutral, very legible data face, with a light geometric figure face for money. Bebas supplies the notice-board voice; Inter keeps dense tables readable; Outfit's 300 weight makes a COP figure feel measured rather than promotional. Bebas Neue and Outfit 300 load from Bunny via `resources/css/app.css`; Inter 400/500/600/700 and Jost 500 are self-hosted by `laravel-vite-plugin/fonts` in `vite.config.js`.

### Hierarchy

- **Display** (Bebas 400, 128px/0.76, tracking 0.012em): the single `HOY` heading on the dashboard. It drops to 82px at 900px and 66px at 560px.
- **Compact page title** (Bebas 400, 88px/0.8): list screens — "PRODUCTOS", "PEDIDOS", "COLECCIONES", "USUARIOS". It drops to 68px, then 54px.
- **Form page title** (Bebas 400, 78px/0.8): form and order-detail screens — "PEDIDO", "NUEVO PRODUCTO". It drops to 62px, then 48px.
- **Section title** (Bebas 400, 21–23px/1, tracking 0.015em): card headers, form sections, order-detail sections, and the order code in the detail head (31px).
- **Figure** (Outfit 300, 34px/1, tracking −0.02em, tabular numerals): the month's sales and the order total. The unit "COP" is Inter 500 at 10px, 0.06em.
- **Figure small** (Outfit 300, 27px/1, tabular): status-rail counts and glance values; 21px for a compact glance value, 26px for the pending count, 29px for the detail amount.
- **Body** (Inter 400, 13px/1.45): the shell default. Table cells drop to 11.5px; helper text and metadata sit at 9–10.5px.
- **Data** (Inter 400, 11.5px/1.45): order, product, and user table cells; money, dates, quantities, and times always use tabular numerals.
- **Micro label** (Inter 600, 8.5–9px, tracking 0.16–0.18em, uppercase): KPI labels, block titles, column heads, and form labels. It is a label attached to a value, not a kicker above a heading.
- **UI label** (Inter 500, 11px/1): buttons, links, and row actions; row actions drop to 10px.
- **Wordmark** (Jost 500, 15px/1, tracking 0.2em): "TAG Q" in the rail only. "OPERACIONES" sits beneath it at 8.5px with 0.28em tracking in 62% paper.

### Named Rules

**The One Display Voice Rule.** Bebas Neue is the only display face. It appears in page titles, card titles, form-section titles, detail-section titles, and the order code. It never sets body copy, table data, labels, or buttons.

**The Tabular Figure Rule.** Money, counts, quantities, dates, and times are Outfit 300 or Inter with tabular numerals. A figure is never bolded to add importance; scale and position carry the hierarchy.

## Layout

The shell is a two-column grid: a fixed 232px navy rail and a fluid work column. The rail is sticky at the top, spans 100vh, and composes brand, navigation, a flexible gap, and a signed-in foot. The work column is a sticky 56px topbar over a scrolling paper area with 30px top, 32px side, and 36px bottom padding. There is no centered marketing container; the page uses the full work column.

| Region | Geometry |
|---|---|
| Application grid | `232px minmax(0, 1fr)`; `min-height: 100vh`; 208px rail at ≤1180px |
| Rail | Sticky top 0; 100vh; padding 24px 16px 20px; brand, 38px gap, nav, auto gap, foot |
| Rail navigation | 2px row gap; each link 35px tall with a 2px left rail; "Solo admin" group starts 18px below |
| Topbar | Sticky top 0; 56px min-height; 32px side padding; 20px gap; 1px hairline bottom |
| Content | Padding 30px 32px 36px |
| Page head | Flex end-aligned, `space-between`, 32px gap, 24px bottom margin; compact variant 20px |
| Sales / glance block | Right-aligned group with a 1px left hairline; 26px left padding (sales) or 24px (glance) |
| Five-state rail | 5 equal columns, 1px top and bottom hairline; each cell 16px 15px 17px with a 1px left divider |
| Body grid | `minmax(0, 1fr) 300px`, 24px gap, 28px top margin |
| Card head / foot | 50px / 42px minimum, 16px side padding, 1px hairlines |
| Table | 32px header row, 46px body rows, 12px cell padding, fixed table layout |
| Form | 1040px max width; sections 22px 24px; 2-column field grid with 15px 20px gap |
| Form layout | `minmax(0, 1fr) 280px`, 20px gap; the 280px note column hides at ≤1180px |
| Order detail | `minmax(0, 1fr) 300px`, 20px gap; detail body 24px side padding |
| Pagination | 27px square cells, 4px gap, 9.5px numerals |

The page head carries the title and its note on the left; the right side is either the month's sales figure plus seven day bars or a compact glance group. The seven bars are a 132px-wide, 30px-tall baseline strip: 7px gap, minimum 2px bar, current day in lume, the rest in `colors.week-bar`. The status rail starts 24px below the page head. The agenda card and the operational side rail start 28px below that. An agenda row is 46px and the dashboard shows at most 8 rows; the side rail is exactly 300px at desktop and holds stock, team, and pending-payment blocks with 1px top hairlines.

**Build note.** The locked direction brief named a 64px topbar; the shipped bar measures 56px, and 56px is normative here.

### Responsive

| Breakpoint | Behavior |
|---|---|
| > 1180px | Full shell: 232px rail, 300px side rail, two-column forms, 280px note column |
| ≤ 1180px | Rail 208px; body grid becomes one column and the side rail reflows to a 3-column strip; form layout and order detail become one column; the form note column is hidden; topbar and content side padding drop to 24px |
| ≤ 900px | The shell becomes a single block; the rail turns into a horizontal header with a wrapping nav row and a bordered "Solo admin" group; the topbar becomes static and wraps (date, action, then a full-width search); page titles drop to 82/68/62px; the sales or glance block moves under the title with a top hairline; the side rail stacks vertically; forms, key-value grids, and the order detail go one column; the image grid goes to 2 columns |
| ≤ 560px | Side padding 14px; titles 66/54/48px; status cells tighten and labels wrap; the week strip narrows to 120px; filters become full-width fields; page actions and filter actions stretch; forms bleed to the viewport edges; tables keep a 620–780px minimum and scroll horizontally inside their wrap |

Tables never squeeze their columns to unreadable widths. `.admin-table-wrap` is the only horizontal scroll container (`overflow-x: auto; overscroll-behavior-x: contain`); at ≤560px the general table has a 660px minimum, the orders table 780px, and the agenda 620px. The body itself never scrolls sideways and has a 320px minimum.

**The Shell Holds Rule.** Navigation, the topbar, and the five-state rail keep their geometry on every screen. A screen may change its page head, its card, or its side content; it does not invent a new chrome.

## Elevation & Depth

This system is flat. There are zero `box-shadow` declarations in the admin stylesheet, and no gradient, blur, or translucent panel is used for depth. Depth comes from three devices: the tonal step from warm paper to work white to deep paper, the 1px hairline that bounds every surface, and the sticky layering of the rail and topbar. Sticky elements do not gain a shadow when they stick; the hairline is the only boundary.

### Named Rules

**The Hairline Rule.** A surface is bounded by one 1px border, not a border plus a shadow. Internal divisions use colors.hair-soft; the outer edge uses colors.hair; inverse rails use 16% paper. No card, input, table, or alert carries a shadow.

## Shapes

The form language is square and drafted. Cards, fields, stamps, badges, buttons, pagination cells, avatars, and thumbnails all have a 0px radius. The only circles in the system are 4–6px status and presence dots, the 31px user avatar's presence mark, and the punched lume hole in the hang-tag mark; those use `rounded.dot` (50%). Nothing else rounds.

Borders are structural and carry state: 1px hairline for surfaces, 1px soft hairline for internal rows, 1px status line for stamps and stock badges, 2px lume or transparent rail for nav links, 3px status rail on the first table cell, 3px lume rule on the live status cell, and a 2px top rule on each order-flow step. The brand mark is an authored 23×27px SVG hang tag with a clipped top-left corner, 1.1px stroke, and a lume eyelet and string; table rows repeat it at 11×13px or 8×11px. Focus is a 2px navy outline offset 2px; an input focus adds a 2px 12% navy ring. There are no pills, no rounded rectangles, no clipped corners on surfaces, and no geometric masking.

**The Square Surface Rule.** If a surface is a container, it is rectangular with a 0px radius and a 1px hairline. A radius is reserved for a dot, and a 3px rail is reserved for a state.

## Components

### Buttons

Buttons are compact, rectangular, and quiet; the fill, not the size, carries priority.

- **Shape:** 0px radius, 1px border, 34px minimum height, 0 12px padding, 7px icon gap, 11px/500 label.
- **Primary (navy):** submit, filter, advance-state, and refund actions. Navy fill with paper text; hover shifts to `colors.navy-soft` and white.
- **Secondary (outline):** transparent fill, hairline border, ink text; hover adds a navy border and a white fill. Used for "Cancelar" and the filter's "Limpiar".
- **Ghost (white):** the same outline with a white resting fill; the topbar's "+ Nuevo producto" uses it.
- **Accent (lume):** navy text on a lume fill; hover shifts to `colors.lume-deep`. Only the Collections creation action uses this fill.
- **Danger:** transparent fill, red border and `colors.red-text`; hover becomes a red fill with white text. Used for destructive submits.
- **Row actions:** text-only 10px/500 navy actions with underline on hover; destructive row actions use `colors.red-text`. These are not buttons.
- **Disabled:** 45% opacity and a not-allowed cursor. **Focus:** the shared 2px navy outline with a 2px offset.

**The One Primary Action Rule.** One screen gets one filled primary action in its page head or form foot. Table rows use text actions; the topbar uses a ghost action. Do not stack two navy fills in the same visual group.

### Inputs / Fields

- **Style:** 38px minimum height, 8px 10px padding, 1px hairline, 0px radius, `colors.field-shell` fill, 12px Inter, ink text.
- **Label:** block, 9px/600, 0.12em tracking, uppercase, `colors.muted-warm`, 6px below.
- **Hover:** border shifts to a darker warm gray.
- **Focus:** navy border plus a 2px 12% navy outline; the fill becomes white.
- **Disabled:** `colors.paper-deep` fill, muted text, not-allowed cursor. This is how an Empleado sees price and stock.
- **Invalid:** red 1px border plus a 9.5px `colors.red-text` message below.
- **Help:** 9.5px `colors.muted-warm`, 5px below; placeholders use 11px and the same muted tone.
- **File input:** dashed hairline, `colors.field-shell` fill, with a paper-toned selector button.
- **Selects:** same shell, height, and focus as text inputs; no custom arrow chrome.
- **Checkboxes:** 14px, `accent-color` navy, 8px gap, with an optional 9.5px help line.

### Cards / Containers

- **Corner style:** square (0px).
- **Background:** `colors.white` on `colors.paper`; the side rail is one white card split by 1px hairlines rather than several cards.
- **Border:** exactly 1px `colors.hair`; no shadow.
- **Header:** 50px minimum, 16px side padding, Bebas 23px/1 title in navy, 9.5px meta, a right-aligned 9.5px link with a 4px gap and a small arrow.
- **Footer:** 42px minimum, 7px 16px padding, 9.5px muted summary with bold ink counts, and pagination on the right.
- **Internal padding:** 16px for blocks; 12px 14px for flash and error messages; 22px 24px for form sections.
- **Nesting:** no cards inside cards. The agenda card contains a table, not a grid of mini-cards; the side rail is a single card with hairline-separated blocks.

### Navigation

The navigation is a dark rail with a paper voice and a lume state marker.

- **Rail:** 232px (208px at ≤1180px), `colors.navy`, paper text, 24px 16px 20px padding. The brand block is the 23×27px hang tag plus the Jost wordmark and an 8.5px "OPERACIONES" line at 62% paper.
- **Links:** 35px tall, 10px side padding, 12px/400 at 74% paper, 2px transparent left border, 140ms color/background/border transition.
- **Hover:** 6% white background, lume left rail, white text.
- **Active:** 8% white background, lume left rail, white text, 600 weight first span.
- **Count chip:** 19px minimum width, 17px height, lume fill, navy 9px/700 tabular count; only shown when the pending count is above zero.
- **Admin group:** starts 18px below the main links; an 8.5px/600 0.16em uppercase "Solo admin" label at 52% paper, then "Colecciones" and "Equipo".
- **Foot:** pushed to the bottom with `margin-top: auto` and a 16% paper top hairline. The user block is a 31px square outlined avatar (admin gets a 6px lume dot), an 11px/500 name, and a 9px role in 58% paper. "Ver tienda" and "Cerrar sesión" are 9.5px links at 62% paper.
- **Topbar:** 56px, paper, 1px hairline bottom. The date is 10px muted with a 5px lume dot. The search is 32px tall, up to `min(34vw, 340px)`, hairline border, 45% white fill, 11px input; focus makes the border navy and the fill white. The admin create action sits at the far right.
- **Mobile:** at ≤900px the rail becomes a horizontal header; links sit in a wrapping row with a 2px bottom border as the active marker; the admin group becomes an inline group with a left divider and its label hidden; the foot becomes a horizontal row. The topbar stops sticking, wraps, and puts the search full-width on its own line.

### Status Stamps and Badges

The stamp is the signature state object: a small bordered rectangle with a 4px dot and the exact Spanish label.

- **Stamp:** inline-flex, 19px minimum height, 0 6px padding, 1px status line, 0px radius, 9.5px/500 label, 5px dot gap. The dot is 4px and uses `--status-color`; fill, line, and text come from the status class.
- **Table row:** the first cell carries a 3px `--status-color` rail; a pending row also gets the `colors.row-late` wash.
- **Badge:** same rectangular grammar at 20px minimum height with a 4px dot. Active uses the paid-line/paid-wash/green-text family; inactive uses hairline/`#f7f4ec`/muted-warm; the Admin role badge is a navy fill with paper text and a lume dot.
- **Stock badge:** hairline for normal, `colors.pending-line` + pending-wash + amber-text for low, `colors.critical-line` + critical-wash + red-text for out. The value includes the unit: "0 u.", "3 u.".
- **Role text:** admin navy/500, employee steel, other roles muted; the raw role value is capitalized by CSS.

### Signature: The Hang Tag

The hang tag is the brand's only pictorial mark and the agenda's row marker. The rail version is a 23×27px authored SVG: a tag outline with a clipped top-left corner, a 1.1px current-color stroke, a lume eyelet, and a short lume string. Table rows repeat it at 11×13px; the compact 8×11px version is used where space is tight. In tables it rests at `colors.tagmark` and turns `colors.tagmark-late` on a pending row. It is drawn SVG, never an icon font, emoji, or image.

### Signature: The Lume Rail

The lume rail is a 2–3px line that carries the current state through the shell. It appears as the 2px active nav border, the 3px left rail on a paid agenda row, and the 3px bottom rule on the live `Pagado` status cell. It is lume only when the state is live or paid; pending uses amber, preparation steel, shipped navy, delivered green, and terminal red. The rail is never decorative and never appears on a card, callout, or alert outside the status grammar.

### Data Tables

The table is the primary working instrument; its density is the point.

- **Frame:** a white card with a 50px header and a 42px footer; `.admin-table-wrap` is the only horizontal scroller.
- **Header:** 32px row, 9px/600 uppercase, 0.13em tracking, `colors.muted-warm`, left-aligned except the explicit right/center cells.
- **Body:** 46px rows, 8px 12px cell padding, 11.5px ink, 1px `colors.hair-soft` divider, no border on the last row.
- **Hover:** `colors.row-hover`; a pending row keeps its `colors.row-late` wash and hovers a shade deeper.
- **Status rail:** the first cell is position-relative with a 3px `--status-color` bar; its left padding is 16px (14px at ≤900px, 10px at ≤560px).
- **Money and time:** right-aligned, tabular, nowrap; dates and times use 9.5–10px muted type.
- **Primary cell:** the order code is 11px/600 ink with a 6px tag gap and a hairline-free underline on hover; product cells pair a 34×42px hairline thumbnail with a name/slug stack.
- **Empty row:** a single colspan cell with a 38px vertical padding, centered 11px muted copy, and the table frame intact. Filtered empty states add a "Limpiar filtros" link.
- **Footer pagination:** 27px square cells, 1px hairline, 9.5px tabular numerals; current is navy fill, disabled is 40% muted, ellipsis has no border.

### KPI Grammar

A KPI is three stacked parts, never a card inside a card:

1. **Micro label** — 9px/600 uppercase, 0.18em, `colors.muted-warm`.
2. **Figure** — Outfit 300, 34px/1 (or 27/26/29px in compact contexts), navy, tabular, −0.02em; a unit follows at 10px/500 with a 3px gap.
3. **Foot** — 10px muted copy with 600 ink counts, or an 8.5px note that qualifies the figure.

The dashboard's sales block is the reference: "VENTAS DEL MES", "$ 8.240.000 COP", then "9 pedidos este mes · 3 por despachar", with the seven-day bar strip to the right. Compact glance groups stack the same three parts and separate items with a 1px soft hairline and 22px padding. Do not add icons, sparklines, rings, or a second accent to a KPI.

### Operational Side Rail

The 300px side rail is one white card divided by 1px hairlines into blocks with 17px 16px 18px padding. Each block has a 9px/600 uppercase title, an optional right-aligned 9px meta, and a 13px gap before content.

- **Low stock:** a name/count row (10.5px name, 9.5px red-tinted count), a 3px meter on `colors.meter-track` with a red fill, and a note. The meter width is `max(4, min(100, round(stock / threshold * 100)))%`; zero stock is 0% and the count still reads "0 u.".
- **Team:** 26px square initials (navy fill + lume dot for Admin, paper fill for others), a 10.5px name, a 9px role, and a 9px summary note. Up to four members are shown. The block is Admin-only and is omitted entirely when the team collection is empty.
- **Pending payments:** a 26px count beside a 9px "por cobrar" cap, an 11px/500 COP total, and a 9px reference line (up to three order codes, then an ellipsis).
- **Footer link:** a full-width 48px "Ver todos los pedidos" row with a top hairline and a right arrow.

### State Conventions

| State | Visual convention |
|---|---|
| Active / selected | Lume rail + 8% white wash for nav; 10% lume wash for a selected status cell; lume fill for the skip link; lume selection background with navy text |
| Paid / live | Lume dot and row rail; paid-wash stamp; 3px lume rule and 9% lume wash on the live status cell |
| Pending / late | Amber dot and row rail; pending-wash stamp; `colors.row-late` row wash; amber-text money-adjacent labels |
| Critical | Red dot/rail, critical-wash stamp, red text, red meter, red invalid border; danger zone is a 1px critical line on a critical wash |
| Inactive / empty | Muted-warm text on a hairline badge or an unfilled frame; the empty copy stays inside the same card, table, or side block |
| Error | 1px critical line + critical wash for a flash or error list; red-deep error text; red input border for `aria-invalid` |
| Success | 1px success line + success wash for a flash; lume dot; the message is 11.5px ink |
| Disabled | 45% opacity for buttons; deep-paper fill and muted text for fields; not-allowed cursor |
| Focus | 2px navy outline, 2px offset; inputs add a 2px 12% navy outline and a white fill |
| Hover | Table row-hover; nav 6–8% white; buttons move to navy-soft, lume-deep, or a white fill; text links underline at 2–3px offset |
| Terminal | Red dot/rail, critical-wash stamp, no forward transition, and no rail cell on the dashboard |

### Role Visibility

The UI is role-aware, but the server is the guard: admin routes use `role:admin,empleado`; Collections, Users/Team, and their actions use `role:admin`.

| Area | Admin | Empleado |
|---|---|---|
| "Hoy" dashboard | Yes; team block and active-users count visible | Yes; team block and active-users count absent |
| "Pedidos" | Yes; list, detail, advance, cancel, refund | Yes; same order operations |
| "Productos" | Yes; create, edit, delete, price/stock/is_active | Yes; edit allowed fields only, no create/delete, price/stock/is_active disabled |
| "Colecciones" | Yes; create, edit, delete | No nav item and no route access |
| "Equipo" (Users) | Yes; create, edit, active toggle | No nav item and no route access |
| Topbar "+ Nuevo producto" | Visible | Absent |
| Rail "Solo admin" group | Visible with Colecciones and Equipo | Absent |

An Empleado sees the same shell, the same agenda, and the same status grammar; only the Admin-only areas and data disappear. Do not replace the missing team block with a placeholder for an Empleado — the block simply does not render.

### Provisional Low Stock

The low-stock cutoff is `stock <= 3` for active products only, read from `config('tagq.low_stock_threshold')` (default 3). The UI must call it provisional every time it appears: the side-rail meta reads "provisional ≤ 3", the products glance note reads "corte ≤ 3 · provisional", and the product form help reads "La alerta de stock bajo usa un corte provisional de 3 unidades." Inactive products are excluded from the alert list. This is a visible operating cutoff, not a settled business policy.

**The Provisional Label Rule.** Any low-stock count, meter, badge, or form hint carries the word provisional (or corte provisional) in the same block. Never present the cutoff as confirmed policy, and never hardcode 3 where the config value can be read.

## Do's and Don'ts

### Do:

- **Do** keep the 232px navy rail, the warm paper work area, and the 300px side rail as the shell geometry; collapse them only at the documented breakpoints.
- **Do** set page, card, and section titles in Bebas Neue, and set every money or count figure in Outfit 300 with tabular numerals.
- **Do** bound every surface with exactly one 1px hairline; separate internal rows with colors.hair-soft.
- **Do** drive an order row's rail, stamp fill, stamp line, and dot from the same status class and the exact Spanish label.
- **Do** reserve lume for live/paid, selection, the active nav rail, the current-day bar, the admin presence dot, the pending count chip, and the single Collections creation action.
- **Do** label low stock as provisional ≤ 3 and read the cutoff from config('tagq.low_stock_threshold').
- **Do** keep empty states inside the same frame: the card, table, or side block stays, and the copy explains what will fill it.
- **Do** let tables scroll horizontally inside .admin-table-wrap at ≤560px instead of squeezing columns.
- **Do** hide Admin-only navigation, actions, and data from an Empleado, and keep the server-side role guard as the real enforcement.
- **Do** keep forms at one column at ≤900px and let the form card bleed to the viewport edges at ≤560px.

### Don't:

- **Don't** add a box shadow, gradient, blur, or glass panel; depth is paper-to-white and a hairline.
- **Don't** round a card, field, stamp, badge, button, or thumbnail; only 4–6px status/avatar dots and the hang-tag eyelet are circular.
- **Don't** use lume as a generic primary button fill or a decorative accent; the Collections creation action is the only lume-filled control.
- **Don't** put a kicker or eyebrow above a page or card heading; the micro labels here are data labels attached to a value, not pre-headings.
- **Don't** nest a card inside a card or replace a table with a grid of same-size KPI tiles.
- **Don't** invent an order status, translate a status label, or use amber, steel, green, or red for a non-state element.
- **Don't** give cancelado or reembolsado a cell in the five-state rail; they belong in the terminal footer line.
- **Don't** show Colecciones, Equipo, the team block, the active-users count, or Admin-only product fields to an Empleado.
- **Don't** present the low-stock cutoff as a confirmed business rule, and don't hardcode the threshold in a view.
- **Don't** swap the display face for a system font or an icon font for the hang tag; the mark is authored SVG and Bebas Neue is loaded.

## Storefront — Escaparate editorial

### Surface overview

**Storefront North Star: "Escaparate editorial — the display window."**

The public product-detail page at `/catalogo/{slug}` treats the watch as a storefront window: a paper stage holding one real photograph, a left rail of real views, a purchase rail at the right edge, and the technical sheet as a quiet second act. The shopper reads the object first, checks availability and material facts, then adds a quantity while the price and stock stay in view.

The build is code-led. The selected composition decision is `Escaparate editorial`; the selected gallery decision is `Riel izquierdo`. `.impeccable/mocks/decision/product-escaparate-riel.html` is the critique reference, not an approved comp. No generated raster asset ships.

This system is normative only for the product-detail route. It does not restyle the storefront navbar, footer, catalog, cart, checkout, or home. The two systems coexist under the Tag q brand and share square edges, hairlines, and type-led hierarchy, but the admin's navy rail and lume signal do not appear here.

### Stage and rail composition

The page releases the shell's reading container so the piece can use the full store width, capped at 1440px with the navbar gutter. The composition is scoped to `[data-product-detail]` and does not change other storefront routes.

| Region | Geometry |
|---|---|
| Shell | 1440px maximum; padding 18px `clamp(20px, 2.8vw, 40px)` 56px; white page |
| Utility bar | Back link and cart link, 20px gap, 18px bottom margin; 10px/600, 0.17em, uppercase |
| Hero | `minmax(0, 1fr) 350px`, 45px gap, height `clamp(540px, 40vw, 576px)`, stretch |
| Gallery | `70px minmax(0, 1fr)`, 14px gap, full height; the single-image variant drops to one column |
| Thumbnail rail | Column, 8px gap, 18px top padding; 64px square thumbnails |
| Photo frame | Paper stage, `position: relative`, `overflow: hidden`, `isolation: isolate`; full height |
| Main photo | Absolute at 4% top and 5% left, 90% × 92%, `object-fit: contain`, `mix-blend-mode: multiply` |
| Purchase rail | Flex column, padding `8px 0 0 32px`, 1px left hairline, no overflow clipping |
| Details | `1.05fr 0.95fr`, 48px gap, 27px top margin, 17px top padding, 1px ink top rule |
| Spec list | Two columns, 28px column gap; each row 8px 0 with a 1px bottom line |

A normal product fills six rows — `Marca`, `Movimiento`, `Material del pulso`, `Cristal`, `Ancho de correa`, and `Categoría` — and `Categoría` appears only when at least one technical row has a value. An empty spec list renders `Sin especificaciones`.

The purchase rail is a single column and is not a card: one 1px left hairline separates it from the stage, and the H1 opens the rail directly with a 15px top margin. An earlier eyebrow above the H1 was removed in finish review; it is not a component and not a rule. The remaining `PIEZA <b>marca</b>` stage line (`PIEZA KAIROS` in the captured state) is metadata inside the photo frame at 18px top / 22px left (14px / 16px at ≤640px), 9px/600, 0.2em, with the brand at weight 400 and an 8px gap. It is not a pre-heading.

With more than one image, the frame also carries a bottom-left index (16px / 22px, current view in tabular ink, total in muted; 14px / 16px at ≤640px). At desktop, every framed state carries a 54 × 1px rule at 22px right / 19px bottom; at ≤640px the rule becomes 40 × 1px at 16px right / 16px bottom. A single image removes the thumbnail rail and the index. The empty state keeps the paper frame, shows an authored 88px watch-outline SVG at 1px stroke, the word `Sin imagen`, and one sentence explaining that the photograph is being prepared.

The stage has no shadow, gradient, blur, or glass. Its depth is paper behind the photograph, white around it, and hairlines between regions.

### Type scale

| Role | Values | Use |
|---|---|---|
| Storefront display | Outfit 300, `clamp(2.6rem, 3.4vw, 3.5rem)`, 0.91 line-height, −0.022em, uppercase, `text-wrap: balance` | Product H1; measured 49px at 1440. At ≤1100px `clamp(2.6rem, 6vw, 4.6rem)` with an 80% max width; at ≤640px `clamp(2.7rem, 12vw, 3.8rem)` with no max width |
| Price | Inter 500, 27px/1.1, −0.025em, tabular | Product price; the `COP` unit is 11px/500 at 0.1em in muted |
| Body copy | Inter 400, 13px/1.65 | Product description; 290px maximum line length on desktop |
| Detail intro | Inter 400, 12px/1.6 | Technical-sheet intro; 430px maximum line length on desktop |
| Detail heading | Bebas Neue 400, 35px/1, 0.03em | `FICHA TÉCNICA` only |
| Spec label / value | 10px muted label; 9.5px/500, 0.04em, uppercase ink value | Technical rows, right-aligned with `nowrap` until the phone breakpoint |
| Micro label | Inter 600, 9–10px, 0.12–0.2em, uppercase | Utility links, stage metadata, stock line, purchase metadata, photo index, spec labels |
| CTA label | Inter 600, 10.5px, 0.18em, uppercase | The add-to-cart bar |

The product name is the only Outfit display voice on the page; Bebas appears only on the technical heading. Do not set the H1 in Bebas or the technical heading in Outfit.

### Material tokens

| Token | Value | Role |
|---|---|---|
| `colors.store-ink` | `#111111` | Product name, price, spec values, CTA fill, focus outlines, selection fill |
| `colors.store-paper` | `#f5f4f1` | Photo frame and thumbnail resting fill |
| `colors.store-line` | `#ddd9d1` | Purchase-rail hairline, stepper border, spec-row rules, metadata rule |
| `colors.store-muted` | `#6d6a63` | Utility links, spec labels, `COP`, secondary metadata |
| `colors.store-stage` | `#5a5750` | Stage metadata and photo index inside the frame |
| `colors.store-white` | `#ffffff` | Page background, stepper shell, CTA label |
| `colors.store-stock` | `#546a4c` | Available-stock dot |
| `colors.store-state-red` | `#9f3327` | Out-of-stock line and dot, purchase error message |

The durable material set is deliberately narrow. One-off copy greys (`#5f5c55`, `#625f58`) and interaction washes (`#f7f5f0`, `#c2beb5`) are local refinements, not new tokens. The stylesheet still defines `--pd-navy`, but this page does not use it, so navy is not a storefront token here. Do not add a second accent or a shadow to fill a gap.

### Depth and motion

The stage is flat. There are no shadows, gradients, blur, or glass panels; depth is paper behind white behind a hairline. Inside `[data-product-detail]`, the only `box-shadow` declarations are inset ink rings: the active thumbnail's 1px inset ring and the quantity stepper's `focus-within` 1px inset ring. The frame is square-cornered; the only circular status/control mark is the 5px stock dot, and the empty-state watch outline is a drawn illustration.

Motion is one authored event plus state transitions: the gallery crossfades the main photograph at 420ms `cubic-bezier(0.22, 1, 0.36, 1)` on opacity; thumbnail, border, and CTA color changes run at 180ms. `prefers-reduced-motion: reduce` removes the crossfade and the listed transitions. There are no entrance animations, parallax, or scroll effects inside the surface.

### Named rules

**The Display Window Rule.** The photograph fills a paper stage and the interface stays at its edge. No card frame, gradient, glass, or decorative shadow may compete with the product image.

**The Purchase Rail Rule.** The buy column is a 350px rail separated by one hairline, and the H1 opens it directly. The rail must not clip focus; the CTA focus ring is a 2px ink outline with a 3px offset and must remain fully visible.

**The One Stage Accent Rule.** Ink is the only strong fill on this page. Stock green and state red are state-only. Lume, amber, steel, and the admin status palette never appear on the storefront detail.

**The Product-Detail Scope Rule.** This composition governs `/catalogo/{slug}` only. Do not promote its stage/rail geometry to the catalog, home, cart, or checkout, and do not restyle the shared storefront shell from this page.

### Responsive

| Breakpoint | Behavior |
|---|---|
| > 1100px | Two-column hero: gallery and 350px purchase rail, fixed stage height, two-column details, two-column specs |
| ≤ 1100px | Hero stacks gallery over the buy rail with no gap and auto height; gallery rail 64px with a 12px gap; frame `clamp(380px, 55vw, 560px)`; buy rail gets a 22px top padding, a top hairline, and no left border; details go one column with a 22px gap |
| ≤ 640px | Shell padding 14px 18px 36px; gallery becomes a column; frame takes order 1 at `min(78vw, 440px)`; the thumbnail rail becomes a horizontal 58px strip below the photo with an 8px gap and horizontal scroll; specs go one column and values may wrap |

The product section fits at 390px. At that width the shared storefront navbar overflows its row by about 23px; that is a pre-existing shell defect outside this surface, recorded as an evidence gap in Provenance, not as a storefront rule.

### Storefront components

**Buy CTA (`store-buy-cta`).** A 52px full-width ink bar with a white 10.5px/600, 0.18em uppercase label. Hover deepens to `#000000`; disabled is 0.72 opacity with a wait cursor; focus is a 2px ink outline at 3px offset and is never clipped by the purchase rail.

**Quantity stepper (`store-quantity`).** A 122 × 42px grid of `34px minmax(0, 1fr) 34px`, 1px `store-line` border, white shell. The minus button disables at 1 and the plus at the product's stock maximum. `focus-within` turns the border ink and adds a 1px inset ink ring. Step buttons hover to `#f7f5f0`; disabled copy is `#c2beb5`. The native number input is centered at 12px/500, hides its spinners, and keeps the form usable without JavaScript.

**Gallery thumbnail (`store-thumb`).** A 64px square (58px at ≤640px) with a 1px 20%-ink border on paper. The image is `cover` with `mix-blend-mode: multiply`. Resting opacity is 0.58, hover 0.88, active 1 with an ink border plus a 1px inset ink ring. State changes take 180ms; focus is the shared 2px ink outline at 3px offset. The active thumb carries `aria-pressed="true"`.

**Spec row (`store-spec-row`).** A flex row with a 10px muted label and a 9.5px/500, 0.04em uppercase ink value, right-aligned and nowrap. The row is 8px 0 with a 1px bottom line, two per row on desktop, one per row at ≤640px where the value may wrap.

### Storefront do's and don'ts

#### Do:

- **Do** let the photograph hold the stage: paper `#f5f4f1`, `contain`, `multiply`, and the 4% / 5% inset.
- **Do** open the purchase rail with the H1 and keep the 1px left hairline; the rail is not a card.
- **Do** keep the 350px buy rail and the 70px thumbnail rail at desktop, then stack at ≤1100px and turn the rail into a 58px horizontal strip at ≤640px.
- **Do** drive thumbnail state with opacity plus an ink border and inset ring, not with a shadow or a second accent.
- **Do** keep the focus ring visible: 2px ink at 3px offset, with no clipping overflow on the purchase rail.
- **Do** show the category spec row only when at least one technical spec exists, and keep `Sin especificaciones` for a truly empty spec list.
- **Do** keep the single-image state free of a thumbnail rail and the empty state inside the same paper frame.
- **Do** disable the gallery crossfade and state transitions under `prefers-reduced-motion: reduce`.
- **Do** keep the technical sheet in Bebas and the product name in Outfit 300.

#### Don't:

- **Don't** add a box shadow, gradient, blur, or glass panel to the stage; the only inset rings are the active thumb and the quantity focus.
- **Don't** round the frame, thumbnails, stepper, or CTA; the only circular status/control mark is the 5px stock dot, and the empty-state watch outline is an illustration.
- **Don't** use navy, lume, amber, steel, or the admin status palette on this page; `--pd-navy` is defined but unused.
- **Don't** turn `PIEZA` into a kicker or add an eyebrow above the H1; the stage metadata lives inside the frame.
- **Don't** crop the main product photo with `cover`; the stage uses `contain`, and only thumbnails use `cover`.
- **Don't** promote this composition to the catalog, home, cart, or checkout, or restyle the shared navbar and footer from this surface.
- **Don't** replace the native number input or the submit path with JavaScript-only behavior.
- **Don't** add an entrance animation, parallax, or scroll effect inside the product detail.

## Provenance

- **Surface and mode:** Two coexisting systems. Tag q admin, Operate mode, code-led build. Admin scope: dashboard, products, orders, collections, users/team. The public product-detail surface is documented separately in the Storefront section; the rest of the storefront (home, catalog, cart, checkout) remains out of scope. Admin surface contract: `.impeccable/surfaces/resources-views-admin.md`; locked direction "Agenda operativa"; seed `bd41bb1dea2e62f6`. No approved comp exists, and no shipping design rasters exist.
- **Admin review disposition:** finish review verdict `ship` on the four scored fixes. The review was a fresh-verifier substitution disclosed by the parent; this document records that disposition and does not re-score it.
- **Admin verification state (recorded from the build's verification run; not re-run during this documentation pass):** 474 tests and 3,219 assertions passed; `npm run build` passed; the Dusk browser suite was not executed.
- **Storefront surface and mode:** public product detail at `/catalogo/{slug}`, Persuade mode, code-led build. Composition decision `Escaparate editorial`; gallery decision `Riel izquierdo`. Decision cards: `.impeccable/mocks/decision/product-composition.json` and `.impeccable/mocks/decision/product-gallery.json`; surface contract: `.impeccable/surfaces/resources-views-catalog-show-blade-php.md`; seed `none (unavailable)`. `.impeccable/mocks/decision/product-escaparate-riel.html` is a critique reference only, not an approved comp.
- **Storefront review disposition:** finish review verdict `ship` on four scored fixes only, not the whole surface. The review was a fresh-verifier substitution disclosed by the parent; this document records that disposition and does not re-score it.
- **Storefront verification state (recorded from the build's verification run; not re-run during this documentation pass):** 476 tests and 3,250 assertions passed; `npm run build` passed; the Dusk browser suite was not executed; no commit, push, or deploy was made.
- **Rasters:** no design raster ships. Admin review evidence lives in `.impeccable/review/orders-desktop.png`, `.impeccable/review/orders-mobile.png`, and `/tmp/opencode/admin-shots/*`; the former admin `desktop.png` and `mobile.png` paths now hold the storefront captures listed below. Admin product photography is catalogue content served from Cloudinary, not a design asset. If any raster is ever shipped, it needs exact provenance (source, author, and license).
- **Storefront rasters:** no shipping raster exists. `.impeccable/review/desktop.png` (1440), `.impeccable/review/mobile.png` (390), `.impeccable/review/desktop-1280.png`, `.impeccable/review/pd-single-desktop.png`, `.impeccable/review/pd-single-mobile.png`, `.impeccable/review/pd-gallery-active-3.png`, and `.impeccable/review/pd-focus-cta.png` are review evidence only. The multi-image captures use a temporary four-crop QA fixture; the database was restored to the single-image state. Product photographs are Cloudinary catalogue content, and QA fixture crops are review evidence only.
- **Evidence checked:** `PRODUCT.md`; `.impeccable/surfaces/resources-views-admin.md`; `odd/tasks/admin-redesign.md`; `config/tagq.php`; `app/Http/Controllers/Admin/DashboardController.php`; `app/Services/OrderStatusService.php`; `resources/css/admin.css`; `resources/css/app.css`; `vite.config.js`; `resources/views/layouts/admin.blade.php`; the admin screen set under `resources/views/admin/`; `.impeccable/review/*.png`; `/tmp/opencode/admin-shots/*`; `routes/web.php`.
- **Storefront evidence checked:** `PRODUCT.md`; `.impeccable/surfaces/resources-views-catalog-show-blade-php.md`; `.impeccable/mocks/decision/product-composition.json`; `.impeccable/mocks/decision/product-gallery.json`; `.impeccable/mocks/decision/product-escaparate-riel.html`; `resources/views/catalog/show.blade.php`; `resources/views/livewire/cart/add-to-cart.blade.php`; `app/Livewire/Cart/AddToCart.php`; `resources/css/app.css` (the `[data-product-detail]` block); `.impeccable/review/desktop.png`; `.impeccable/review/mobile.png`; `.impeccable/review/desktop-1280.png`; `.impeccable/review/pd-single-desktop.png`; `.impeccable/review/pd-single-mobile.png`; `.impeccable/review/pd-gallery-active-3.png`; `.impeccable/review/pd-focus-cta.png`.
- **Evidence gaps:** no approved comp or client-supplied design raster; the topbar is 56px in the build against 64px in the direction brief (the build is recorded); the low-stock threshold is provisional and not business-confirmed; Dusk was not executed; no admin-specific brand book, logo rule, or voice guide exists.
- **Storefront evidence gaps:** no approved comp — the selected sketch is a critique reference; the shared storefront navbar overflows its row by about 23px at 390px, which is pre-existing shell drift outside this surface and is not canonized; Dusk was not executed; no shipping raster exists; the multi-image state has no persistent QA fixture after the database restore.
