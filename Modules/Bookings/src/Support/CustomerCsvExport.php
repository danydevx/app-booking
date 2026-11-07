<?php

namespace Modules\Bookings\Support;

use App\Models\Team;
use Modules\Bookings\Models\Customer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerCsvExport
{
    public function stream(Team $team): StreamedResponse
    {
        $filename = sprintf(
            'clientes-%s-%s.csv',
            $team->slug,
            now()->format('Ymd-His'),
        );

        return response()->streamDownload(function () use ($team): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Nombre', 'Correo', 'Teléfono', 'WhatsApp', 'Notas', 'Alta']);

            Customer::query()
                ->where('team_id', $team->id)
                ->orderBy('name')
                ->chunk(200, function ($customers) use ($output): void {
                    foreach ($customers as $customer) {
                        fputcsv($output, [
                            $customer->name,
                            $customer->email,
                            $customer->phone,
                            $customer->whatsapp_phone,
                            $customer->notes,
                            $customer->created_at?->toDateString(),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
