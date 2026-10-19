# Design tokens (Stage 2)

One source: `resources/design/tokens.cjs`.

- **Staff (Tailwind):** `tailwind.config.js` reads the file. Run `npm run build` after a change. The compiled output in `public/build` is committed.
- **Customers (website CSS):** `node scripts/sync-tokens.cjs` writes the generated block at the end of `public/assets/css/styles.css`, between `tokens:start` and `tokens:end`. Don't edit that block by hand.
- **Status words:** `config/az.php`. Customers see some friendlier words, e.g. disbursed is shown as "Active" and pending as "Under review".

## Colours

| Token | Hex | CSS | Tailwind |
|---|---|---|---|
| ink-blue (primary) | #1b2b6b | `var(--ink-blue)` | `bg-az-ink-blue` |
| harbour | #3d5a9e | `--harbour` | `az-harbour` |
| royal (secondary/links) | #2b4fd8 | `--royal` | `az-royal` (≈ `brand-500`) |
| royal-tint / mist / cloud | #e6ecfc / #c9d6f0 / #eef2fa | `--royal-tint` `--mist` `--cloud` | `az-royal-tint` … |
| ink / slate / line / paper | #151a2e / #5a6178 / #dde3ef / #fff | `--ink` `--slate` `--line` `--paper` | `az-ink` … |
| sand, sage, apricot, rose, amber, steel (+ `-d` dark) | tints and text pairs | `--sage` `--sage-d` … | `az-sage` `az-sage-d` … |

## Loan status and payment state colours (background / text)

| Status | Pair | CSS class / vars | Tailwind |
|---|---|---|---|
| pending | sand / sand-d | `.st-pending` (`--st-pending-bg/-fg`) | `bg-status-pending-bg text-status-pending-fg` |
| approved | royal-tint / royal | `.st-approved` | `status-approved-*` |
| disbursed (and legacy `active`) | mist / ink-blue | `.st-disbursed` | `status-disbursed-*` |
| overdue | apricot / apricot-d | `.st-overdue` | `status-overdue-*` |
| defaulted | rose / rose-d | `.st-defaulted` | `status-defaulted-*` |
| recovery | rose-d / white (solid) | `.st-recovery` | `status-recovery-*` |
| forbearance | amber / amber-d | `.st-forbearance` | `status-forbearance-*` |
| repaid | sage / sage-d | `.st-repaid` | `status-repaid-*` |
| written_off | steel / steel-d | `.st-written-off` | `status-written-off-*` |
| rejected | cloud / slate | `.st-rejected` | `status-rejected-*` |
| payment pending / received / failed | sand / sage / rose | `.pay-pending` `.pay-received` `.pay-failed` | `bg-pay-received-bg text-pay-received-fg` … |

The `status-*` and `pay-*` Tailwind classes are safelisted, so they can be built from a variable.

## Type, spacing, radius

- **Fonts:** Sora for headings (`var(--display)`, `font-sora`), Figtree for body text (`var(--body)`, `font-figtree`), Outfit for numbers (`var(--num)`, `font-outfit`).
- **Spacing:** `--sp-1` … `--sp-10`, which is 4, 8, 12, 16, 20, 24, 32 and 40px. On the staff side, use the Tailwind spacing scale.
- **Radius:** `--r-sm` 12, `--r-md` 18, `--r-lg` 26, `--r-xl` 36 and `--r-pill` 999px. In Tailwind these are `rounded-az-sm` … `rounded-az-xl`.
