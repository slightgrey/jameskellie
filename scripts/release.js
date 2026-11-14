// Publishes the bundled zip as a GitHub release, which live sites pick up as a theme update
// (inc/Updater.php). Run after `npm run bundle` and after committing and pushing:
//   npm run release
// Needs the GitHub CLI (gh), logged in with access to the repo.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');

const run = (cmd, args) => execFileSync(cmd, args, { cwd: ROOT, encoding: 'utf8' }).trim();
const fail = (msg) => {
  console.error(`  ✖ ${msg}`);
  process.exit(1);
};

const style = fs.readFileSync(path.join(ROOT, 'style.css'), 'utf8');
const version = (style.match(/^Version:\s+(\d+\.\d+\.\d+)\s*$/m) || [])[1];
if (!version) fail('Could not find a "Version: X.Y.Z" header in style.css.');

const repoUrl = (style.match(/^Update URI:\s+https:\/\/github\.com\/([\w.-]+\/[\w.-]+)\s*$/m) || [])[1];
if (!repoUrl) fail('style.css needs "Update URI: https://github.com/owner/repo".');

const tag = `v${version}`;
const zip = path.join('releases', `jameskellie-${tag}.zip`);

console.log(`\n🚀 Releasing James Kellie (React) ${tag} to ${repoUrl}...\n`);

// 1. The zip for this version must exist.
if (!fs.existsSync(path.join(ROOT, zip))) fail(`${zip} not found. Run "npm run bundle" first.`);

// 2. Clean working tree, so the release matches a commit.
if (run('git', ['status', '--porcelain'])) fail('Uncommitted changes. Commit (and push) them first.');

// 3. HEAD must be on GitHub.
const head = run('git', ['rev-parse', 'HEAD']);
const branch = run('git', ['rev-parse', '--abbrev-ref', 'HEAD']);
try {
  run('git', ['fetch', '--quiet', 'origin', branch]);
} catch {
  fail(`Branch "${branch}" is not on origin. Push it first.`);
}
if (run('git', ['branch', '-r', '--contains', head]).split('\n').every((b) => !b.trim())) {
  fail('HEAD is not pushed. Run "git push" first.');
}

// 4. The tag must be new.
let exists = true;
try {
  run('gh', ['release', 'view', tag, '-R', repoUrl, '--json', 'tagName']);
} catch {
  exists = false;
}
if (exists) fail(`Release ${tag} already exists. Bump the version (npm run version:patch) and bundle again.`);

// 5. Create the release with the zip attached.
const url = run('gh', ['release', 'create', tag, zip, '-R', repoUrl, '--target', head, '--title', tag, '--generate-notes']);

console.log(`  ✔ ${zip} attached`);
console.log(`\n✅ Released ${tag}: ${url}\n`);
console.log('   Live sites see it under Dashboard → Updates within 12 hours ("Check again" for now).\n');
