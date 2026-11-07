<?php

namespace Modules\Bookings\Support;

use App\Models\Team;
use Modules\Bookings\Models\Customer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerCsvExport
{
    public function stream(Team $team, string $search = ''): StreamedResponse
    {
        $search = trim($search);

        $filename = sprintf(
            'clientes-%s-%s.csv',
            $team->slug,
            now()->format('Ymd-His'),
        );

        return response()->streamDownload(function () use ($team, $search): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Nombre', 'Correo', 'Teléfono', 'WhatsApp', 'Notas', 'Alta']);

            Customer::query()
                ->where('team_id', $team->id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($searchQuery) use ($search): void {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->chunk(200, function ($customers) use ($output): void {
                    foreach ($customers as $customer) {
                        fputcsv($output, [
                            $this->sanitize($customer->name),
                            $this->sanitize($customer->email),
                            $this->sanitize($customer->phone),
                            $this->sanitize($customer->whatsapp_phone),
                            $this->sanitize($customer->notes),
                            $this->sanitize($customer->created_at?->toDateString()),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Prevent spreadsheet formula injection while preserving plain text.
     */
    private function sanitize(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
