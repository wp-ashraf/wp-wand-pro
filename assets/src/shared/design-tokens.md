# WP Wand — Design Tokens (extracted from the existing UI)

These tokens are reverse-engineered from the legacy settings page (`inc/admin.php` +
`assets/css/admin.css`) so the React rebuild keeps the SAME visual language while being
clean, scoped, and enhanceable (no legacy markup copied verbatim). Reuse them across every
new screen for consistency.

## Color
- `--wpw-brand`: `#3767FB`  (user-overridable via the `wpwand_brand_color` option)
- `--wpw-text`: `#18181B`        (labels, strong text)
- `--wpw-text-2`: `#080E13`      (input text / active nav)
- `--wpw-muted`: `#454F5C`       (field descriptions, 12px)
- `--wpw-muted-2`: `#7C838A`     (inactive tab / placeholder)
- `--wpw-border`: `#E4E4E7`      (input borders)
- `--wpw-divider`: `rgba(22,22,23,.08)` (tab underline / hairlines)
- `--wpw-ok`: `#3BCB38`          (active key dot/badge)
- `--wpw-off`: `#D1D6DB`         (inactive key dot)
- `--wpw-track`: `#EAEDF0`       (slider unfilled track)
- `--wpw-card`: `#fff`

## Typography
- Family: `Inter` (already enqueued by the plugin).
- Label: 14px / 600 / `--wpw-text`.
- Field description: 12px / 400 / `--wpw-muted`, block, margin-top 8px.
- Input/select: 14px / 400 / `--wpw-text-2`.
- Tab: 14px / 500; inactive `--wpw-muted-2`, active `--wpw-text-2`.

## Layout / shape
- Card: `--wpw-card`, radius 10px, page wrap padding 30px.
- Row: two columns — label column 339px, then the control.
- Control width: 350px, height 45px, border 1px `--wpw-border`, radius 5px, padding 15px.
- Textarea: height 140px.
- Tabs bar: padding `0 25px`, 2px bottom divider; active tab 2px brand underline.
- Primary button: brand bg, height 40px, radius 8px, white, 14px / 500, padding `0 21px`.

## Components
- **API key row**: text input + status line below = circle icon (`--wpw-ok` / `--wpw-off`)
  + "Active" / "Not active + get-key link". (UX add: password masking + a live "Test" button.)
- **Slider**: brand-filled track (3px) + 16px circular handle with 3px brand border, paired
  with a 100px number box to its right. (Rebuilt as a native range input — same look, better a11y.)
- **Select**: native, grouped by provider (model) — only providers with an active key show.
- **Checkbox**: native (e.g. "Hide ChatGPT Assistant inside Gutenberg").

## Notes for the rebuild
- Keep the existing *flow*: one General panel (+ an Advanced tab reserved for Pro/business
  fields), Update button at the bottom, Sync action.
- Enhance the *UX*, not the layout: reactive validation, inline key testing, instant
  save feedback (no full-page reload), model list that reacts to which keys are active.
