/**
 * Aimzone design tokens: the ONE source of truth for both sides.
 *   - staff (TailAdmin / Tailwind): tailwind.config.js requires this file -> az-*, status-*, pay-* colours
 *   - customers (website CSS): `node scripts/sync-tokens.cjs` writes the generated block in
 *     public/assets/css/styles.css (between the "tokens:start" / "tokens:end" markers)
 * Labels for each status live in config/az.php. Docs: docs/stage2/DESIGN-TOKENS.md
 */
const color = {
  'ink-blue': '#1b2b6b',
  harbour: '#3d5a9e',
  royal: '#2b4fd8',
  'royal-d': '#2140b3',
  'royal-tint': '#e6ecfc',
  mist: '#c9d6f0',
  cloud: '#eef2fa',
  paper: '#ffffff',
  ink: '#151a2e',
  slate: '#5a6178',
  line: '#dde3ef',
  sand: '#f4ede2',
  'sand-d': '#7a5b2e',
  sage: '#dcebe2',
  'sage-d': '#2f6b4f',
  apricot: '#f8e1cf',
  'apricot-d': '#9a4a1c',
  rose: '#f6dce0',
  'rose-d': '#9b2c3b',
  amber: '#fdf1d6',
  'amber-d': '#7a5200',
  steel: '#e4e7ee',
  'steel-d': '#3b4256',
};

// [background, foreground] per loan status, as colour token names (CSS reuses var(--<name>))
const loanStatus = {
  pending: ['sand', 'sand-d'],
  approved: ['royal-tint', 'royal'],
  disbursed: ['mist', 'ink-blue'],
  overdue: ['apricot', 'apricot-d'],
  defaulted: ['rose', 'rose-d'],
  recovery: ['rose-d', 'paper'],
  forbearance: ['amber', 'amber-d'],
  repaid: ['sage', 'sage-d'],
  written_off: ['steel', 'steel-d'],
  rejected: ['cloud', 'slate'],
};

// [background, foreground] per payment state, as colour token names
const paymentStatus = {
  pending: ['sand', 'sand-d'],
  received: ['sage', 'sage-d'],
  failed: ['rose', 'rose-d'],
};

const font = {
  display: "'Sora', 'Helvetica Neue', Arial, sans-serif",
  body: "'Figtree', 'Helvetica Neue', Arial, sans-serif",
  num: "'Outfit', 'Helvetica Neue', Arial, sans-serif",
};

// spacing scale (4px base) and radius
const space = { 1: '4px', 2: '8px', 3: '12px', 4: '16px', 5: '20px', 6: '24px', 8: '32px', 10: '40px' };
const radius = { sm: '12px', md: '18px', lg: '26px', xl: '36px', pill: '999px' };

module.exports = { color, loanStatus, paymentStatus, font, space, radius };
