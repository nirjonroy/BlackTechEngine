<?php

namespace App\Services\Analytics;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsCsvExporter
{
    /** @param iterable<int, array<int, bool|int|string|null>> $rows */
    public function response(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');

            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->safeCsv(...), $row));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function safeCsv(bool|int|string|null $value): string
    {
        $value = match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Yes' : 'No',
            default => (string) $value,
        };

        return preg_match('/^[\p{Z}\s]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
