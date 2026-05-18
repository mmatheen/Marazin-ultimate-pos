/**
 * Split pos-main.monolith.css → pos-base, pos-desktop, pos-tablet, pos-mobile.
 * Run: node scripts/split-pos-css.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const cssDir = path.join(__dirname, '../public/assets/css/pos_page_style');
const sourcePath = path.join(cssDir, 'pos-main.monolith.css');

const HEADER = {
  base: `/* POS – shared/base styles (all viewports)\n * Breakpoints: phone <768px | tablet 768–991px | desktop ≥992px\n */\n\n`,
  desktop: `/* POS – desktop (≥992px, Bootstrap lg+)\n */\n\n`,
  tablet: `/* POS – tablet (768px–991px)\n */\n\n`,
  mobile: `/* POS – mobile & checkout UI (<992px)\n */\n\n`,
};

function dedent(css) {
  const lines = css.split('\n');
  let min = Infinity;
  for (const line of lines) {
    if (!line.trim()) continue;
    const m = line.match(/^(\s+)/);
    if (m) min = Math.min(min, m[1].length);
    else min = 0;
  }
  if (!Number.isFinite(min) || min === 0) return css.trim();
  return lines
    .map((line) => {
      if (!line.trim()) return '';
      const m = line.match(/^(\s+)/);
      const lead = m ? m[1].length : 0;
      return line.slice(Math.min(min, lead));
    })
    .join('\n')
    .trim();
}

function classifyMediaQuery(mq) {
  const q = mq.toLowerCase().replace(/\s+/g, ' ');
  const mins = [...q.matchAll(/min-width:\s*(\d+)px/g)].map((m) => parseInt(m[1], 10));
  const maxes = [...q.matchAll(/max-width:\s*(\d+)px/g)].map((m) => parseInt(m[1], 10));
  const minW = mins.length ? Math.max(...mins) : 0;
  const maxW = maxes.length ? Math.min(...maxes) : null;

  if (minW >= 992 && (maxW === null || maxW >= 992)) return 'desktop';
  if (minW >= 1024 && maxW === null) return 'desktop';
  if (minW >= 1200 && maxW === null) return 'desktop';
  if (minW >= 1360 && maxW === null) return 'desktop';
  if (minW >= 1400 && maxW === null) return 'desktop';
  if (minW >= 768 && maxW !== null && maxW <= 1024) return 'tablet';
  if (minW >= 576 && maxW !== null && maxW <= 1024) return 'tablet';
  if (maxW !== null && maxW <= 991 && minW >= 768) return 'tablet';
  if (maxW !== null && maxW <= 1199 && minW >= 768) return 'tablet';
  if (maxW !== null && maxW <= 1023 && minW >= 768) return 'tablet';
  if (maxW !== null && maxW <= 991) return 'mobile';
  if (maxW !== null && maxW <= 767) return 'mobile';
  if (maxW !== null && maxW <= 599) return 'mobile';
  if (maxW !== null && maxW <= 576) return 'mobile';
  if (maxW !== null && maxW <= 575) return 'mobile';
  if (maxW !== null && maxW <= 400) return 'mobile';
  if (minW >= 768 && maxW === null) return 'base';
  if (minW >= 576 && maxW === null) return 'base';
  return 'base';
}

function splitByMediaBlocks(css) {
  const parts = [];
  // Only match real @media at line start (skip "@media" inside /* comments */)
  const re = /^\s*@media\s+([^{]+)\{/gm;
  let cursor = 0;
  let m;

  while ((m = re.exec(css)) !== null) {
    if (m.index > cursor) {
      parts.push({ type: 'base', content: css.slice(cursor, m.index) });
    }
    const mq = m[1].trim();
    let depth = 1;
    let pos = re.lastIndex;
    while (depth > 0 && pos < css.length) {
      const ch = css[pos];
      if (ch === '{') depth += 1;
      else if (ch === '}') depth -= 1;
      pos += 1;
    }
    parts.push({ type: 'media', mq, content: css.slice(m.index, pos) });
    cursor = pos;
    re.lastIndex = pos;
  }

  if (cursor < css.length) {
    parts.push({ type: 'base', content: css.slice(cursor) });
  }
  return parts;
}

if (!fs.existsSync(sourcePath)) {
  console.error('Missing pos-main.monolith.css – restore the original bundle first.');
  process.exit(1);
}

const raw = fs.readFileSync(sourcePath, 'utf8');
const parts = splitByMediaBlocks(raw);
const buckets = { base: [], desktop: [], tablet: [], mobile: [] };

for (const part of parts) {
  const text = dedent(part.content);
  if (!text) continue;
  if (part.type === 'media') {
    buckets[classifyMediaQuery(part.mq)].push(text);
  } else {
    buckets.base.push(text);
  }
}

function writeBucket(name, chunks) {
  fs.writeFileSync(path.join(cssDir, `pos-${name}.css`), HEADER[name] + chunks.join('\n\n') + '\n', 'utf8');
  return chunks.length;
}

const counts = {
  base: writeBucket('base', buckets.base),
  desktop: writeBucket('desktop', buckets.desktop),
  tablet: writeBucket('tablet', buckets.tablet),
  mobile: writeBucket('mobile', buckets.mobile),
};

const mainAggregator = `/**
 * POS page styles – entry file.
 * Load order: base → desktop → tablet → mobile (later wins when media queries overlap).
 *
 *   pos-base.css     – shared layout, forms, product grid, autocomplete
 *   pos-desktop.css  – desktop billing table, footer, order summary (≥992px)
 *   pos-tablet.css   – tablet-only tweaks (768px–991px)
 *   pos-mobile.css   – mobile header, cart cards, checkout dock (<992px)
 *
 * Regenerate: node scripts/split-pos-css.mjs  (reads pos-main.monolith.css)
 */

@import url('pos-base.css');
@import url('pos-desktop.css');
@import url('pos-tablet.css');
@import url('pos-mobile.css');
`;

fs.writeFileSync(path.join(cssDir, 'pos-main.css'), mainAggregator, 'utf8');

const total =
  fs.statSync(path.join(cssDir, 'pos-base.css')).size +
  fs.statSync(path.join(cssDir, 'pos-desktop.css')).size +
  fs.statSync(path.join(cssDir, 'pos-mobile.css')).size +
  fs.statSync(path.join(cssDir, 'pos-tablet.css')).size;

console.log('Split complete:', counts);
console.log('Output bytes:', total, '| Monolith:', fs.statSync(sourcePath).size);
