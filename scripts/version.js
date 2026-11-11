// Bumps the theme version everywhere it is written:
// style.css (theme header, the source of truth), JK_VERSION in functions.php and package.json.
//   npm run version:patch | version:minor | version:major
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');

const bump = process.argv[2]; // patch | minor | major

if (!['patch', 'minor', 'major'].includes(bump)) {
  console.error('Usage: npm run version:patch | version:minor | version:major');
  process.exit(1);
}

// --- Current version from style.css ---
const cssPath = path.join(ROOT, 'style.css');
const cssContent = fs.readFileSync(cssPath, 'utf8');
const headerMatch = cssContent.match(/^Version:\s+(\d+)\.(\d+)\.(\d+)\s*$/m);
if (!headerMatch) {
  console.error('Could not find a "Version: X.Y.Z" header in style.css');
  process.exit(1);
}

const [major, minor, patch] = headerMatch.slice(1).map(Number);
const current = `${major}.${minor}.${patch}`;
const next = {
  major: `${major + 1}.0.0`,
  minor: `${major}.${minor + 1}.0`,
  patch: `${major}.${minor}.${patch + 1}`,
}[bump];

console.log(`\n🔖 Bumping version: ${current} → ${next} (${bump})\n`);

// --- functions.php constant (checked before anything is written) ---
const phpPath = path.join(ROOT, 'functions.php');
const phpContent = fs.readFileSync(phpPath, 'utf8');
const phpPattern = /(define\(\s*'JK_VERSION',\s*')[^']+(')/;
if (!phpPattern.test(phpContent)) {
  console.error("Could not find define( 'JK_VERSION', '…' ) in functions.php");
  process.exit(1);
}

// --- style.css theme header ---
fs.writeFileSync(cssPath, cssContent.replace(/^(Version:\s+)[\d.]+/m, `$1${next}`), 'utf8');
console.log('  ✔ style.css');

// --- functions.php ---
fs.writeFileSync(phpPath, phpContent.replace(phpPattern, `$1${next}$2`), 'utf8');
console.log('  ✔ functions.php (JK_VERSION)');

// --- package.json (and the lockfile's copy of it) ---
const pkgPath = path.join(ROOT, 'package.json');
const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
pkg.version = next;
fs.writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + '\n', 'utf8');
console.log('  ✔ package.json');

const lockPath = path.join(ROOT, 'package-lock.json');
if (fs.existsSync(lockPath)) {
  const lock = JSON.parse(fs.readFileSync(lockPath, 'utf8'));
  lock.version = next;
  if (lock.packages && lock.packages['']) lock.packages[''].version = next;
  fs.writeFileSync(lockPath, JSON.stringify(lock, null, 2) + '\n', 'utf8');
  console.log('  ✔ package-lock.json');
}

console.log(`\n✅ Version is now ${next}\n`);
console.log('   Run "npm run bundle" to build and package the release.\n');
