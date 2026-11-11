// Copies the self-hosted latin woff2 files from @fontsource into assets/fonts.
// Run once after npm install (npm run fonts); the copies are committed.
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const out = path.join(root, 'assets/fonts');
fs.mkdirSync(out, { recursive: true });

const fonts = [
  ['inter', [400, 500, 600]],
  ['inter-tight', [500, 600, 700, 800]],
  ['roboto-condensed', [700]],
];

for (const [family, weights] of fonts) {
  for (const w of weights) {
    const file = `${family}-latin-${w}-normal.woff2`;
    fs.copyFileSync(path.join(root, 'node_modules/@fontsource', family, 'files', file), path.join(out, file));
    console.log('copied', file);
  }
}
