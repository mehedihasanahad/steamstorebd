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
  // Enough to outlast a refusal that is really a challenge, few enough that
  // a shop which has genuinely stopped answering is not argued with.
  attempts = 3,
} = config;

/** Everything that is not the document itself: never read, so never fetched. */
const SKIP_RESOURCES = new Set(['image', 'media', 'font', 'stylesheet']);

/** Answers that mean "prove you are a browser", not "this page is gone". */
const CHALLENGE_STATUSES = new Set([403, 429, 503]);

function send(payload) {
  process.stdout.write(JSON.stringify(payload) + '\n');
}

let browser;

/**
 * A browsing session: its own cookie jar, its own request count.
 *
 * The shop's bot protection meters a session rather than an address, so what
 * matters is how many readings one of these is asked for -- see fetchPage.
 */
async function newSession() {
  // No user agent is set. Chromium sends one that matches its own TLS
  // handshake and client hints; overriding it makes the two disagree, and a
  // browser claiming to be a different browser is what the check looks for.
  const session = await browser.newContext({
    locale,
    viewport: { width: 1366, height: 768 },
  });

  // Roughly halves the time a page takes and most of the bytes. The price
  // lives in a script tag in the document.
  await session.route('**/*', async (route) => {
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

  return session;
}

try {
  browser = await chromium.launch({
    headless,
    args,
    ...(proxy ? { proxy: { server: proxy } } : {}),
  });

  // Opened here only to prove the browser works before the handshake is
  // answered; the sweep itself opens one of its own per reading.
  await (await newSession()).close();

  send({ ready: true });
} catch (error) {
  // First line only: Playwright follows its message with a boxed install
  // hint that turns one console line into eight.
  const [reason] = error.message.split('\n');
  send({ ready: false, error: `Could not start a browser: ${reason}` });
  process.exit(1);
}

/**
 * Loads one URL, on a page of its own, in a session of its own.
 *
 * A page per URL, rather than one page reused for the whole sweep. Reuse
 * works exactly once: these pages keep background requests in flight long
 * after they have loaded, and navigating away leaves those still being
 * answered by the route handler above while the next navigation queues
 * behind them. It presents as every page after the first timing out, however
 * generous the timeout -- which reads like a slow network or a blocked
 * address, and is neither.
 *
 * A session per URL too, which is the part that was wrong. Carrying one
 * context through the sweep carries its cookies, and the bot protection
 * meters a session by them: a context is allowed a budget of readings and
 * then answers 403 to everything, for good. Sharing it therefore does not
 * spend a clearance across the catalogue, it spends the catalogue against
 * one budget -- so the sweep would read a card or two, trip the limit and
 * fail every remaining card of the night without ever recovering, because
 * nothing here ever dropped the session that had been refused.
 *
 * Opening a context costs no measurable time next to loading the page, and a
 * refusal in a fresh one is answered by a fresh one again rather than by
 * asking the same refused session a second time.
 */
async function loadOnce(session, url) {
  const page = await session.newPage();

  try {
    // domcontentloaded, not networkidle: the structured data is server
    // rendered and present the moment the document is, while this page keeps
    // chattering to analytics long after it is useful.
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout });

    return { status: response?.status() ?? 0, html: await page.content() };
  } finally {
    await page.close();
  }
}

async function fetchPage(url) {
  let last = { status: 0, html: '' };

  for (let attempt = 0; attempt < attempts; attempt++) {
    // A session is never reused across an attempt. The first attempt answers
    // a shop that challenges a session's opening navigation; the ones after
    // it answer a session that has been refused outright, which is a refusal
    // the cookie jar carries and a new jar does not.
    const session = await newSession();

    try {
      last = await loadOnce(session, url);
    } finally {
      await session.close();
    }

    if (!CHALLENGE_STATUSES.has(last.status)) {
      return last;
    }

    // Lengthening, because a refusal means the address is being read as
    // busy and answering it at the same pace is what keeps it that way.
    if (attempt < attempts - 1) {
      await new Promise((resolve) => setTimeout(resolve, retryDelay * (attempt + 1)));
    }
  }

  return last;
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
