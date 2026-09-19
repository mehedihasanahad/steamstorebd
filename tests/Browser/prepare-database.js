import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { appEnv } from '../../playwright.config.js';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

/*
 | The suite is served the built assets, never a running `npm run dev` (see
 | STOREFRONT_IGNORE_VITE_HOT in playwright.config.js). So the build has to
 | exist, and saying so here is far kinder than letting every page render
 | unstyled and every screenshot diff fail for a reason none of them names.
 */
const manifest = path.join(root, 'public', 'build', 'manifest.json');

if (! fs.existsSync(manifest)) {
    process.stderr.write('public/build/manifest.json is missing - run `npm run build` before the browser suite.\n');
    process.exit(1);
}

/**
 * Build the storefront the browser suite drives: a fresh SQLite database with
 * the real migrations and the DemoStorefrontSeeder fixture.
 *
 * This runs as the first half of the webServer command rather than as a
 * globalSetup, because Playwright starts the web server before global setup —
 * a server booted against a database this script was about to delete would
 * serve 500s until the health check gave up.
 *
 * The file is removed rather than migrated over, so a run can never inherit a
 * half-state from the run before it and pass for the wrong reason.
 */
const database = appEnv.DB_DATABASE;

fs.mkdirSync(path.dirname(database), { recursive: true });
fs.rmSync(database, { force: true });
fs.writeFileSync(database, '');

// shell: true because Windows resolves `php` through PATHEXT, which
// execFileSync does not do on its own.
const artisan = (args) => execFileSync('php', ['artisan', ...args], {
    cwd: root,
    env: { ...process.env, ...appEnv },
    stdio: ['ignore', 'pipe', 'pipe'],
    shell: true,
}).toString();

try {
    artisan(['migrate', '--force']);

    // The fixture writes banner artwork to the public disk; without the link
    // every /storage/... URL on the page would 403.
    artisan(['storage:link']);

    artisan(['db:seed', '--class=DemoStorefrontSeeder', '--force']);

    // The catalog cache is on the file driver here, so a previous run's tree
    // would otherwise survive into this one.
    artisan(['cache:clear']);
    artisan(['view:clear']);

    process.stdout.write('storefront fixture ready\n');
} catch (error) {
    const detail = [error.message, error.stdout?.toString(), error.stderr?.toString()]
        .filter(Boolean)
        .join('\n');

    process.stderr.write(`fixture build failed:\n${detail}\n`);
    process.exit(1);
}
