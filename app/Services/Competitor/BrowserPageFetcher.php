<?php

namespace App\Services\Competitor;

use App\Exceptions\CompetitorFetchException;

/**
 * Fetches through a real browser, for shops that will not serve a script.
 *
 * G2A fingerprints the TLS and HTTP/2 handshake, so no combination of headers
 * gets a PHP request past it -- every one comes back 403. Chromium is let
 * through, so the sweep drives Chromium.
 *
 * The browser is started once and held for the whole run: launching it costs
 * about as much as loading a page, and a sweep loads one page per watched
 * card. Requests go out over the process stdin and pages come back on stdout,
 * one JSON object per line.
 */
class BrowserPageFetcher implements PageFetcher
{
    /** The line of a crash that names the fault, rather than locating it. */
    private const MESSAGE_LINE = '/^[A-Za-z]*Error[: ]/m';

    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    public function fetch(string $url): FetchedPage
    {
        $this->start();

        $this->write(['url' => $url]);

        $response = $this->read();

        if (! ($response['ok'] ?? false)) {
            throw CompetitorFetchException::transport((string) ($response['error'] ?? 'Browser gave no answer'));
        }

        return new FetchedPage((int) ($response['status'] ?? 0), (string) ($response['html'] ?? ''));
    }

    /**
     * Starts the browser on first use, so a run with nothing to check never
     * pays for one.
     */
    private function start(): void
    {
        if (is_resource($this->process)) {
            return;
        }

        $config = config('competitor.browser');

        $command = [
            // Split on whitespace so the node setting can carry a wrapper as
            // well as the binary. A headless Linux server has no display for
            // a headed Chromium, so it runs behind a virtual one and this is
            // set to "xvfb-run -a node" -- which has to reach proc_open as
            // three arguments, not one impossible filename.
            ...preg_split('/\s+/', trim((string) $config['node'])),
            base_path($config['script']),
            // No user agent is passed on purpose. Chromium sends one that
            // matches its own TLS handshake and client hints; overriding it
            // with the string the HTTP fetcher uses makes the two disagree,
            // and a browser claiming to be a different browser is precisely
            // what the bot protection looks for -- it answers 403.
            json_encode([
                'headless' => (bool) $config['headless'],
                'timeout'  => (int) $config['timeout'] * 1000,
                'proxy'    => config('competitor.http.proxy') ?: null,
            ], JSON_THROW_ON_ERROR),
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $script = base_path($config['script']);

        if (! is_file($script)) {
            throw CompetitorFetchException::transport("Browser script is missing at {$script}");
        }

        // Suppressed so a failure to launch arrives as this exception, naming
        // the command, rather than as a PHP warning turned into a stack trace
        // several layers up that says nothing about what was run.
        $process = @proc_open($command, $descriptors, $pipes, base_path(), $this->environment($config));

        if (! is_resource($process)) {
            $attempted = implode(' ', array_slice($command, 0, count($command) - 1));

            throw CompetitorFetchException::transport("Could not run [{$attempted}]");
        }

        $this->process = $process;
        $this->pipes   = $pipes;

        // Non-blocking, so draining it for a crash message can never hang.
        stream_set_blocking($this->pipes[2], false);

        // Launching Chromium is the slow part, so the handshake waits longer
        // than a page load does.
        stream_set_timeout($this->pipes[1], (int) $config['timeout'] + 30);

        $ready = $this->read();

        if (! ($ready['ready'] ?? false)) {
            $detail = (string) ($ready['error'] ?? '') ?: $this->drainErrors();

            $this->close();

            throw CompetitorFetchException::transport($detail ?: 'Browser did not start');
        }
    }

    /**
     * The environment the browser process runs in.
     *
     * Merged onto the current one rather than replacing it, because handing
     * proc_open an array replaces the lot -- and a browser launched without
     * PATH finds neither node nor itself.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    private function environment(array $config): array
    {
        $overrides = array_filter([
            'PLAYWRIGHT_BROWSERS_PATH' => $config['browsers_path'] ?? null,
            'HOME'                     => $config['home'] ?? null,
        ], fn ($value) => filled($value));

        return array_merge(getenv(), $overrides);
    }

    /** @param array<string, mixed> $payload */
    private function write(array $payload): void
    {
        fwrite($this->pipes[0], json_encode($payload, JSON_THROW_ON_ERROR) . "\n");
        fflush($this->pipes[0]);
    }

    /**
     * Reads one answer.
     *
     * A page is upwards of a megabyte and arrives as a single line, which
     * fgets() will happily return in pieces, so this keeps reading until the
     * line ends rather than trusting the first chunk to be whole.
     *
     * @return array<string, mixed>
     */
    private function read(): array
    {
        $line = '';

        while (true) {
            $chunk = fgets($this->pipes[1]);

            if ($chunk === false) {
                // Node writes its crashes to stderr, not stdout -- a missing
                // playwright install, a browser that will not start, a bad
                // display. Without this they all arrive as the same useless
                // "stopped responding" and the real cause is thrown away.
                $detail = $this->drainErrors();

                $this->close();

                throw CompetitorFetchException::transport(
                    $detail === '' ? 'Browser stopped responding' : $detail,
                );
            }

            $line .= $chunk;

            if (str_ends_with($chunk, "\n")) {
                break;
            }

            if (stream_get_meta_data($this->pipes[1])['timed_out'] ?? false) {
                $this->close();

                throw CompetitorFetchException::transport('Browser timed out');
            }
        }

        $decoded = json_decode(trim($line), true);

        return is_array($decoded) ? $decoded : ['ok' => false, 'error' => 'Unreadable answer from the browser'];
    }

    /** Whatever the process complained about, trimmed to one useful line. */
    private function drainErrors(): string
    {
        if (! isset($this->pipes[2]) || ! is_resource($this->pipes[2])) {
            return '';
        }

        // A dying process writes its source header first and the line that
        // actually names the fault a moment later, so one read reliably
        // returns the useless half. Switching the pipe to blocking and
        // reading to EOF would be tidier, but Windows ignores that on a
        // proc_open pipe -- so this accumulates until the message it wants
        // has arrived, with a short budget to bound a silent process.
        $errors   = '';
        $deadline = microtime(true) + 1.5;

        do {
            $errors .= (string) stream_get_contents($this->pipes[2]);

            if (preg_match(self::MESSAGE_LINE, $errors)) {
                break;
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        $errors = trim($errors);

        if ($errors === '') {
            return '';
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $errors)),
            // A node crash leads with a "node:internal/..." source header and
            // trails into stack frames. Neither names the problem.
            fn (string $line) => $line !== ''
                && ! str_starts_with($line, 'at ')
                && ! str_starts_with($line, 'node:internal'),
        ));

        // The line that actually says what went wrong, where there is one:
        // "Error: Cannot find module", "ReferenceError: ...", and so on.
        foreach ($lines as $line) {
            if (preg_match(self::MESSAGE_LINE, $line)) {
                return mb_substr($line, 0, 300);
            }
        }

        return mb_substr($lines[0] ?? $errors, 0, 300);
    }

    /** Shuts the browser down. Closing stdin is what tells it to stop. */
    public function close(): void
    {
        if (! is_resource($this->process)) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        proc_close($this->process);

        $this->process = null;
        $this->pipes   = [];
    }

    public function __destruct()
    {
        $this->close();
    }
}
