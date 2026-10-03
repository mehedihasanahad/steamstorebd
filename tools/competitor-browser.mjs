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
  proxy = null,
  // Chromium negotiates HTTP/2 with Cloudflare and has the connection reset
  // mid-handshake. Forcing 1.1 is what makes the page load at all.
  args = ['--disable-http2'],
  retryDelay = 3000,
} = config;

/** Everything that is not the document itself: never read, so never fetched. */
const SKIP_RESOURCES = new Set(['image', 'media', 'font', 'stylesheet']);

/** Answers that mean "prove you are a browser", not "this page is gone". */
const CHALLENGE_STATUSES = new Set([403, 429, 503]);

function send(payload) {
  process.stdout.write(JSON.stringify(payload) + '\n');
}

let browser;
let context;

try {
  browser = await chromium.launch({
    headless,
    args,
    ...(proxy ? { proxy: { server: proxy } } : {}),
  });

  // No user agent is set. Chromium sends one that matches its own TLS
  // handshake and client hints; overriding it makes the two disagree, and a
  // browser claiming to be a different browser is what the check looks for.
  context = await browser.newContext({
    locale,
    viewport: { width: 1366, height: 768 },
  });

  // Roughly halves the time a page takes and most of the bytes. The price
  // lives in a script tag in the document.
  await context.route('**/*', async (route) => {
    try {
      await (SKIP_RESOURCES.has(route.request().resourceType())
        ? route.abort()
        : route.continue());
    } catch {
      // The page this request belonged to has already closed. Answering a
      // request on a dead page throws, and an unhandled rejection in here
      // stalls whatever navigation is running now.
    }
  });

  send({ ready: true });
} catch (error) {
  // First line only: Playwright follows its message with a boxed install
  // hint that turns one console line into eight.
  const [reason] = error.message.split('\n');
  send({ ready: false, error: `Could not start a browser: ${reason}` });
  process.exit(1);
}

/**
 * Loads one URL on a page of its own.
 *
 * A page per URL, rather than one page reused for the whole sweep. Reuse
 * works exactly once: these pages keep background requests in flight long
 * after they have loaded, and navigating away leaves those still being
 * answered by the route handler above while the next navigation queues
 * behind them. It presents as every page after the first timing out, however
 * generous the timeout -- which reads like a slow network or a blocked
 * address, and is neither.
 *
 * Cookies belong to the context, not the page, so a clearance won past the
 * bot check is still carried from one URL to the next.
 */
async function fetchPage(url) {
  const page = await context.newPage();

  try {
    // domcontentloaded, not networkidle: the structured data is server
    // rendered and present the moment the document is, while this page keeps
    // chattering to analytics long after it is useful.
    let response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout });

    // A shop that challenges the first navigation of a session answers it
    // with a 403 and sets a clearance cookie while doing so, which means the
    // very next request through the same context is let through. Retrying
    // once costs a couple of seconds and saves the first card of every sweep.
    if (CHALLENGE_STATUSES.has(response?.status())) {
      await page.waitForTimeout(retryDelay);
      response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout });
    }

    return { status: response?.status() ?? 0, html: await page.content() };
  } finally {
    await page.close();
  }
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
    const { status, html } = await fetchPage(url);

    send({ ok: true, status, html });
  } catch (error) {
    send({ ok: false, error: error.message.split('\n')[0] });
  }
}

await browser?.close();
