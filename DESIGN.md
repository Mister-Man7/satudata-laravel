# DESIGN.md - Monitoring System (Generic Template)

## 1. Visual Theme & Atmosphere
The design should convey **technical reliability** and **control**. It should not feel like a social media app or a game. The interface is meant to be viewed on a laptop or tablet, in a professional or home environment. The vibe is an "analytics dashboard" simplified for the end user.

## 2. Color Palette
- **Primary Background:** `#121212` (Dark mode for visual efficiency and data emphasis).
- **Card Surface:** `#1E1E1E`.
- **Primary Accent (Data):** `#00E5FF` (Cyan/Neon) for charts, main KPIs, and action buttons.
- **Alert Accent:** `#FF453A` (Red) for critical values or anomalies.
- **Secondary Accent:** `#32D74B` (Lime Green) for "Live" status or normal values.
- **Primary Text:** `#FFFFFF` (High contrast).
- **Secondary Text:** `#98989D` (Gray for labels).

## 3. Component Styling
- **Cards:** `border-radius: 16px`. Background `#1E1E1E`. Internal padding `20px`. Subtle border: `1px solid #2C2C2E`.
- **Charts:** Grid lines in `#2C2C2E`. Data in `#00E5FF` with `#30D158` for area gradients. No thick border lines.
- **Tables:** Rows with `border-bottom: 1px solid #2C2C2E`. Row hover changes background to `#252525`.
- **Alerts:** Box with background `#3A1C1C`, red text `#FF453A`, and a left border of `4px solid #FF453A`.

## 4. Typography
- **Headings:** Inter, Semi Bold, 24px.
- **KPI Metrics (main numeric values):** JetBrains Mono or Fira Code (monospaced font for numbers), Bold, 32px.
- **Body:** Inter Regular, 14px.

## 5. Layout Principles
- **Grid:** 12 columns.
- **Spacing:** Multiples of 8px (8px, 16px, 24px, 32px).
- **Dashboard:** 3-column view (KPI, KPI, KPI) at the top, main chart spanning 8 columns, Alerts/Notifications panel spanning 4 columns on the right side.

## 6. Do's and Don'ts
- Do: Use area charts to smooth out spikes or variations in the data.
- Do: Include relevant units or conversions next to each metric.
- Don't: Use pastel colors or white backgrounds (they hinder prolonged data reading).
- Don't: Use generic or overly obvious icons. Choose icons specific to the product's domain.

## 7. Interactive States
- **Buttons:**
  - Default: background `#00E5FF`, text `#121212`.
  - Hover: background lightened 10% (`#33EAFF`), cursor pointer.
  - Active/Pressed: background darkened 10% (`#00B8CC`).
  - Disabled: background `#2C2C2E`, text `#98989D`, cursor not-allowed, no hover effect.
  - Focus (keyboard nav): `2px solid #00E5FF` outline with `2px` offset.
- **Cards/Rows (clickable):** Hover background `#252525`, transition per section 9.
- **Inputs:** Focus border changes to `#00E5FF`, default border `#2C2C2E`.

## 8. Empty, Loading, and Error States
- **Empty State:** Centered icon (outline style, `#98989D`), one-line message in secondary text, optional action button below. Never leave a blank card with no explanation.
- **Loading State:** Skeleton screens using `#1E1E1E` base with a subtle shimmer animation (`#2C2C2E` sweep), matching the shape of the real content (card, chart, table row). Avoid generic spinners for content areas; spinners are acceptable only for button-level actions.
- **Error State:** Same visual language as Alerts (section 3), plus a short explanation and a retry action. Never show a raw error message or stack trace to the end user.
- **No Connection / Stale Data:** Small badge or banner indicating last successful update time, using secondary text color, not the red alert color unless data is critically outdated.

## 9. Motion & Transitions
- **Duration:** 150ms for micro-interactions (hover, focus), 250ms for state changes (loading to loaded, panel open/close).
- **Easing:** `ease-out` for elements entering or expanding, `ease-in` for elements exiting or collapsing.
- **Chart data updates:** Animate value transitions over 300-400ms; avoid instant jumps that make trends hard to follow.
- **Do not** animate purely decorative elements or exceed 400ms for any UI transition, it should feel responsive, not slow.

## 10. Elevation & Shadow System
- **Base cards:** No shadow, rely on the `1px solid #2C2C2E` border for separation (per section 3).
- **Dropdowns/Menus:** `box-shadow: 0 4px 12px rgba(0,0,0,0.4)`.
- **Modals:** `box-shadow: 0 8px 24px rgba(0,0,0,0.5)`, with a background scrim `rgba(0,0,0,0.6)` behind it.
- **Tooltips:** `box-shadow: 0 2px 8px rgba(0,0,0,0.35)`, background `#2C2C2E`, small `border-radius: 8px`.
- Elevation increases with interaction layer depth: base content < dropdown/tooltip < modal.

## 11. Iconography
- **Icon set:** Choose one library and use it consistently (e.g., Lucide or Phosphor Icons); do not mix icon sets.
- **Style:** Outline/stroke icons, not filled, to match the technical/analytical tone.
- **Stroke width:** 1.5px-2px, consistent across all icons.
- **Sizing:** 16px for inline/table icons, 20px for buttons and labels, 24px for section headers or empty states.
- **Color:** Match the text color it accompanies unless the icon conveys status (use alert/secondary accent colors for status icons only).

## 12. Forms & Inputs
- **Text Fields:** Background `#1E1E1E`, border `1px solid #2C2C2E`, `border-radius: 8px`, padding `12px 16px`. Focus border `#00E5FF`.
- **Dropdowns/Selects:** Same base styling as text fields, with a chevron icon from the chosen icon set (section 11).
- **Checkboxes/Toggles:** Use the primary accent (`#00E5FF`) for the checked/on state, `#2C2C2E` for unchecked/off.
- **Validation:** Error border uses `#FF453A` with a short helper text below in the same color. Success state (if needed) uses `#32D74B`.
- **Placeholder text:** Secondary text color `#98989D`, never the same weight or color as entered content.

## 13. Accessibility
- **Contrast:** All text must meet WCAG AA contrast ratios against its background (4.5:1 for body text, 3:1 for large text/headings). Verify `#98989D` on `#121212` and `#1E1E1E` specifically, as gray-on-dark combinations are prone to failing this.
- **Focus visibility:** Every interactive element must have a visible focus state (see section 7); never remove outline without replacing it.
- **Color independence:** Never rely on color alone to convey status (e.g., alerts should pair red with an icon or label, not just a red tint).
- **Touch targets:** Minimum `44x44px` for any clickable element if the interface may be used on touch devices.
- **Text scaling:** Layout should not break if the user increases browser font size up to 200%.

## 14. Responsive Breakpoints
- **Desktop (default):** 1280px and above, full 12-column grid as described in section 5.
- **Tablet:** 768px-1279px, KPI row collapses to 2 columns, main chart and alerts panel stack vertically (chart on top, alerts below, both full width).
- **Mobile:** below 768px, if supported, all sections stack in a single column; KPIs become horizontally scrollable or stacked individually. If mobile is explicitly out of scope, state that here instead of leaving it undefined.