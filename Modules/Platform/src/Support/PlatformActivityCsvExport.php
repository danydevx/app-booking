<?php

namespace Modules\Platform\Support;

use Modules\Platform\Queries\PlatformActivityQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PlatformActivityCsvExport
{
    public function __construct(
        private readonly PlatformActivityQuery $query,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function stream(array $filters): StreamedResponse
    {
        $filename = 'auditoria-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Fecha', 'Evento', 'Objeto', 'Nombre', 'Actor', 'Motivo', 'Propiedades']);

            $this->query->builder($filters)
                ->latest('id')
                ->chunk(200, function ($activities) use ($output): void {
                    foreach ($activities as $activity) {
                        $presented = ActivityPresenter::present($activity);
                        $properties = $presented['properties'];
                        $reason = is_array($properties) && is_string($properties['reason'] ?? null)
                            ? $properties['reason']
                            : '';

                        fputcsv($output, [
                            $this->sanitize($presented['created_at']),
                            $this->sanitize($presented['description']),
                            $this->sanitize($presented['subject_type']),
                            $this->sanitize($presented['subject_name']),
                            $this->sanitize($presented['causer']),
                            $this->sanitize($reason),
                            $this->sanitize((string) json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sanitize(mixed $value): string
    {
        $value = (string) ($value ?? '');
        $trimmed = ltrim($value, " \t\n\r");

        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
