<?php

namespace Modules\Listings\Support;

use RuntimeException;

/**
 * Reads listing import files (CSV or JSON) into plain associative arrays.
 *
 * CSV files exported from Excel are common in Mexico: the reader tolerates a
 * BOM, Windows-1252 encoding and comma, semicolon or tab delimiters.
 */
final class ListingImportFileReader
{
    public const MAX_ROWS = 500;

    /**
     * @return array{rows: array<int, array<string, mixed>>, first_line: int}
     */
    public function read(string $path, string $extension): array
    {
        $extension = strtolower(ltrim($extension, '.'));

        if ($extension === 'json') {
            return [
                'rows' => $this->readJson($path),
                'first_line' => 1,
            ];
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            return [
                'rows' => $this->readCsv($path),
                'first_line' => 2,
            ];
        }

        throw new RuntimeException('El archivo debe ser .csv, .txt o .json.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readJson(string $path): array
    {
        $contents = file_get_contents($path);
        $decoded = json_decode((string) $contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('El JSON no es válido.');
        }

        if (isset($decoded['listings']) && is_array($decoded['listings'])) {
            $decoded = $decoded['listings'];
        }

        if (! array_is_list($decoded)) {
            $decoded = [$decoded];
        }

        return $this->limit(array_values(array_filter($decoded, 'is_array')));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readCsv(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo abrir el archivo.');
        }

        $contents = $this->toUtf8($contents);
        $contents = $this->stripBom($contents);

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        $delimiter = $this->detectDelimiter($contents);
        $headers = fgetcsv($stream, null, $delimiter, '"', '');

        if ($headers === false) {
            fclose($stream);

            return [];
        }

        $headers = array_map(
            fn (mixed $header): string => trim((string) $header),
            $headers,
        );

        $rows = [];

        while (($values = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            if ($values === [null]) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $values[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $this->limit($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function limit(array $rows): array
    {
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException(
                'El archivo tiene '.count($rows).' filas; el máximo es '.self::MAX_ROWS.'.',
            );
        }

        return $rows;
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\n");

        if ($firstLine === false || trim($firstLine) === '') {
            return ',';
        }

        $best = ',';
        $bestColumns = 0;

        foreach ([',', ';', "\t"] as $candidate) {
            $columns = count(str_getcsv($firstLine, $candidate, '"', ''));

            if ($columns > $bestColumns) {
                $best = $candidate;
                $bestColumns = $columns;
            }
        }

        return $best;
    }

    private function stripBom(string $contents): string
    {
        return str_starts_with($contents, "\xEF\xBB\xBF")
            ? substr($contents, 3)
            : $contents;
    }

    private function toUtf8(string $contents): string
    {
        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        return mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
    }
}
