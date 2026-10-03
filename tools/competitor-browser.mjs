/**
 * One browser, held open for a whole price sweep.
 *
 * G2A sits behind bot protection that reads the TLS and HTTP/2 fingerprint of
 * whatever connects, so PHP cannot reach it at all: every request is refused
 * with a 403 no matter which headers it sends. A real browser is accepted, so
 * this is a real browser.
 *
 * It is a long-lived process rather than one invocation per URL because
 * launching Chromium costs more than loading a page does. URLs arrive on
 * stdin as one JSON object per line and the page comes back on stdout the
 * same way; the process exits when stdin closes.
 *
 * Nothing is parsed here. The HTML goes back whole and PHP reads it with the
 * same parser it uses for a plain HTTP fetch, so the two paths can never
 * drift into disagreeing about what a page said.
 */

import { chromium } from 'playwright';
import { createInterface } from 'node:readline';

const config = JSON.parse(process.argv[2] ?? '{}');

const {
  headless = false,
  timeout = 45000,
  locale = 'en-US',
  userAgent = null,
  proxy = null,
  // Chromium negotiates HTTP/2 with Cloudflare and has the connection reset
  // mid-handshake. Forcing 1.1 is what makes the page load at all.
  args = ['--disable-http2'],
} = config;

/** Everything that is not the document itself: never read, so never fetched. */
const SKIP_RESOURCES = new Set(['image', 'media', 'font', 'stylesheet']);

function send(payload) {
  process.stdout.write(JSON.stringify(payload) + '\n');
}

let browser;
let page;

try {
  browser = await chromium.launch({
    headless,
    args,
    ...(proxy ? { proxy: { server: proxy } } : {}),
  });

  const context = await browser.newContext({
    locale,
    viewport: { width: 1366, height: 768 },
    ...(userAgent ? { userAgent } : {}),
  });

  // Roughly halves the time a page takes and most of the bytes. The price
  // lives in a script tag in the document.
  await context.route('**/*', (route) =>
    SKIP_RESOURCES.has(route.request().resourceType()) ? route.abort() : route.continue(),
  );

  page = await context.newPage();

  send({ ready: true });
} catch (error) {
  // First line only: Playwright follows its message with a boxed install
  // hint that turns one console line into eight.
  const [reason] = error.message.split('\n');
  send({ ready: false, error: `Could not start a browser: ${reason}` });
  process.exit(1);
}

const input = createInterface({ input: process.stdin, crlfDelay: Infinity });

for await (const line of input) {
  const trimmed = line.trim();

  if (trimmed === '') {
    continue;
  }

  let url;

  try {
    ({ url } = JSON.parse(trimmed));
  } catch {
    send({ ok: false, error: 'Unreadable request' });
    continue;
  }

  try {
    // domcontentloaded, not networkidle: the structured data is server
    // rendered and present the moment the document is, while this page keeps
    // chattering to analytics long after it is useful.
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout });

    send({ ok: true, status: response?.status() ?? 0, html: await page.content() });
  } catch (error) {
    send({ ok: false, error: error.message.split('\n')[0] });
  }
}

await browser?.close();
