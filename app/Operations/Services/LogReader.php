<?php

namespace App\Operations\Services;

use RuntimeException;

class LogReader
{
    /**
     * Get the current Laravel log file path.
     */
    public function path(): string
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            throw new RuntimeException('Laravel log file not found.');
        }

        return $path;
    }

    /**
     * Get basic information about the log file.
     */
    public function information(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [
                'exists' => false,
                'path' => $path,
                'filename' => basename($path),
                'size' => 0,
                'size_human' => '0 B',
                'last_modified' => null,
            ];
        }

        $size = filesize($path);

        return [
            'exists' => true,
            'path' => $path,
            'filename' => basename($path),
            'size' => $size,
            'size_human' => $this->humanSize($size),
            'last_modified' => date(
                'Y-m-d H:i:s',
                filemtime($path)
            ),
        ];
    }

    /**
     * Read and parse log entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function entries(
        ?string $level = null,
        ?string $search = null,
        int $limit = 100
    ): array {
        $path = $this->path();

        $content = file_get_contents($path);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $entries = $this->parse($content);

        if ($level !== null && $level !== '') {
            $level = strtolower($level);

            $entries = array_filter(
                $entries,
                fn (array $entry) =>
                    strtolower($entry['level'] ?? '') === $level
            );
        }

        if ($search !== null && trim($search) !== '') {
            $search = strtolower(trim($search));

            $entries = array_filter(
                $entries,
                function (array $entry) use ($search) {
                    return str_contains(
                        strtolower($entry['message'] ?? ''),
                        $search
                    )
                    || str_contains(
                        strtolower($entry['context'] ?? ''),
                        $search
                    )
                    || str_contains(
                        strtolower($entry['environment'] ?? ''),
                        $search
                    );
                }
            );
        }

        $entries = array_values($entries);

        /*
         * Newest entries first.
         */
        $entries = array_reverse($entries);

        return array_slice(
            $entries,
            0,
            min(max($limit, 1), 500)
        );
    }

    /**
     * Find a single log entry by its generated ID.
     */
    public function find(string $id): ?array
    {
        $path = $this->path();

        $content = file_get_contents($path);

        if ($content === false || trim($content) === '') {
            return null;
        }

        foreach ($this->parse($content) as $entry) {
            if (($entry['id'] ?? null) === $id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Clear the Laravel log.
     */
    public function clear(): void
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to open Laravel log file for clearing.'
            );
        }

        fclose($handle);
    }

    /**
     * Get the raw Laravel log contents.
     */
    public function raw(): string
    {
        $path = $this->path();

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(
                'Unable to read Laravel log file.'
            );
        }

        return $content;
    }

    /**
     * Parse Laravel log content.
     *
     * Supports entries such as:
     *
     * [2026-09-03 22:41:13] production.ERROR: Something happened
     *
     * Stack traces and multiline messages are kept with
     * their original log entry.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parse(string $content): array
    {
        $lines = preg_split(
            "/\r\n|\n|\r/",
            $content
        );

        $entries = [];

        $current = null;

        foreach ($lines as $line) {
            if ($this->isEntryStart($line)) {

                if ($current !== null) {
                    $entries[] = $this->finalize($current);
                }

                $current = $this->startEntry($line);

                continue;
            }

            if ($current !== null) {
                $current['raw'] .= "\n" . $line;
            }
        }

        if ($current !== null) {
            $entries[] = $this->finalize($current);
        }

        return $entries;
    }

    /**
     * Determine whether a line starts a Laravel log entry.
     */
    protected function isEntryStart(string $line): bool
    {
        return preg_match(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $line
        ) === 1;
    }

    /**
     * Start parsing a log entry.
     */
    protected function startEntry(string $line): array
    {
        $pattern = '/^\[
            (?P<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})
        \]
        \s+
        (?P<environment>[A-Za-z0-9_-]+)
        \.
        (?P<level>[A-Za-z]+)
        :
        \s*
        (?P<message>.*)
        $/x';

        if (! preg_match($pattern, $line, $matches)) {
            return [
                'id' => sha1($line),
                'timestamp' => null,
                'environment' => null,
                'level' => 'unknown',
                'message' => trim($line),
                'context' => null,
                'raw' => $line,
            ];
        }

        $message = $matches['message'];

        /*
         * Laravel often stores context as JSON after the message:
         *
         * Something happened {"user_id":123}
         */
        $context = null;

        $jsonPosition = strpos($message, ' {');

        if ($jsonPosition !== false) {
            $possibleContext = trim(
                substr($message, $jsonPosition)
            );

            $decoded = json_decode(
                $possibleContext,
                true
            );

            if (json_last_error() === JSON_ERROR_NONE) {
                $context = $decoded;

                $message = trim(
                    substr(
                        $message,
                        0,
                        $jsonPosition
                    )
                );
            }
        }

        return [
            'id' => sha1(
                $matches['timestamp']
                . '|' .
                $matches['environment']
                . '|' .
                $matches['level']
                . '|' .
                $message
            ),

            'timestamp' => $matches['timestamp'],

            'environment' => $matches['environment'],

            'level' => strtolower(
                $matches['level']
            ),

            'message' => trim($message),

            'context' => $context,

            'raw' => $line,
        ];
    }

    /**
     * Finalize a multiline entry.
     */
    protected function finalize(array $entry): array
    {
        $raw = $entry['raw'] ?? '';

        /*
         * Keep the complete raw entry available for
         * the detail view, while exposing the first
         * line as the summary message.
         */
        $entry['raw'] = trim($raw);

        if (str_contains($entry['raw'], "\n")) {
            $lines = explode(
                "\n",
                $entry['raw'],
                2
            );

            $entry['message'] = trim(
                $entry['message']
            );

            $entry['details'] = trim(
                $lines[1] ?? ''
            );
        } else {
            $entry['details'] = null;
        }

        return $entry;
    }

    /**
     * Convert bytes to a readable size.
     */
    protected function humanSize(int|false $bytes): string
    {
        if ($bytes === false || $bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power = floor(
            log($bytes, 1024)
        );

        $power = min(
            $power,
            count($units) - 1
        );

        return round(
            $bytes / (1024 ** $power),
            2
        ) . ' ' . $units[$power];
    }
}
