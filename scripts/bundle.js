// Builds the assets and packages the theme for upload: jameskellie-vX.Y.Z.zip
//   npm run bundle
// The zip holds only what the live site needs; src/, node_modules/, vendor/ (Composer is
// dev-only: functions.php has its own autoloader), docs and scripts stay out.
import archiver from 'archiver';
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');

// Version from style.css (source of truth).
const styleFile = fs.readFileSync(path.join(ROOT, 'style.css'), 'utf8');
const versionMatch = styleFile.match(/^Version:\s+(.+)$/m);
const version = versionMatch ? versionMatch[1].trim() : '0.0.0';

// Folder name inside the zip: must be "jameskellie", the Vite `base` in vite.config.js
// (/wp-content/themes/jameskellie/dist/) points there.
const THEME_DIR = 'jameskellie';
const zipName = `${THEME_DIR}-v${version}.zip`;
const zipPath = path.join(ROOT, zipName);

console.log(`\n📦 Bundling James Kellie (React) v${version}...\n`);

// 1. Refuse to bundle while the dev server runs: `hot` makes PHP load assets from localhost.
if (fs.existsSync(path.join(ROOT, 'hot'))) {
  console.error('  ✖ Found "hot" file. Stop "npm run dev" (or delete hot) before bundling.');
  process.exit(1);
}

// 2. The version must agree everywhere (npm run version:* keeps them in step).
const php = fs.readFileSync(path.join(ROOT, 'functions.php'), 'utf8');
const phpVersion = (php.match(/define\(\s*'JK_VERSION',\s*'([^']+)'/) || [])[1];
const pkgVersion = JSON.parse(fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8')).version;
if (phpVersion !== version || pkgVersion !== version) {
  console.error(`  ✖ Version mismatch: style.css ${version}, JK_VERSION ${phpVersion}, package.json ${pkgVersion}.`);
  console.error('    Use "npm run version:patch|minor|major" to change the version.');
  process.exit(1);
}

// 3. Build assets.
console.log('  → Building assets...');
execSync('npx vite build', { cwd: ROOT, stdio: 'inherit' });

// 4. Verify dist was produced.
if (!fs.existsSync(path.join(ROOT, 'dist/.vite/manifest.json'))) {
  console.error('  ✖ Build failed: dist/.vite/manifest.json not found.');
  process.exit(1);
}

// 5. Remove an old zip of the same version.
if (fs.existsSync(zipPath)) fs.unlinkSync(zipPath);

// 6. Create the zip.
console.log(`\n  → Creating ${zipName}...`);

const output = fs.createWriteStream(zipPath);
const archive = archiver('zip', { zlib: { level: 9 } });
let files = 0;

archive.on('entry', () => files++);
archive.on('warning', (err) => console.warn(`  ⚠ ${err.message}`));
archive.on('error', (err) => {
  throw err;
});

output.on('close', () => {
  const kb = (archive.pointer() / 1024).toFixed(1);
  console.log(`\n✅ Done! ${zipName} (${kb} KB, ${files} files)\n`);
  console.log('   Install via WordPress Admin → Appearance → Themes → Add New → Upload Theme');
  console.log('   (or upload the jameskellie folder over SFTP to wp-content/themes/).\n');
});

archive.pipe(output);

// Folders the theme needs at runtime.
const dirs = [
  'inc', // PHP classes
  'config', // settings schema and default content
  'templates', // page templates (Book Appointment)
  'assets', // self-hosted fonts
  'dist', // built React app and CSS
  'languages', // translations, if any
];

for (const dir of dirs) {
  const full = path.join(ROOT, dir);
  if (!fs.existsSync(full)) {
    if (dir !== 'languages') console.warn(`  ⚠ Skipping missing: ${dir}/`);
    continue;
  }
  // .vite/manifest.json is needed (PHP reads it); stray test pages starting with _ are not.
  archive.directory(full, `${THEME_DIR}/${dir}`, (entry) => (dir === 'dist' && /^_/.test(path.basename(entry.name)) ? false : entry));
}

// style.css, every root PHP template and the optional extras.
for (const file of fs.readdirSync(ROOT)) {
  const extra = ['screenshot.png', 'screenshot.jpg', 'readme.txt', 'LICENSE'].includes(file);
  if (file === 'style.css' || file.endsWith('.php') || extra) {
    archive.file(path.join(ROOT, file), { name: `${THEME_DIR}/${file}` });
  }
}

archive.finalize();
