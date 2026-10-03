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
            $config['node'],
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

        $process = proc_open($command, $descriptors, $pipes, base_path());

        if (! is_resource($process)) {
            throw CompetitorFetchException::transport('Could not start the browser process');
        }

        $this->process = $process;
        $this->pipes   = $pipes;

        // Launching Chromium is the slow part, so the handshake waits longer
        // than a page load does.
        stream_set_timeout($this->pipes[1], (int) $config['timeout'] + 30);

        $ready = $this->read();

        if (! ($ready['ready'] ?? false)) {
            $this->close();

            throw CompetitorFetchException::transport((string) ($ready['error'] ?? 'Browser did not start'));
        }
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
                $this->close();

                throw CompetitorFetchException::transport('Browser stopped responding');
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
