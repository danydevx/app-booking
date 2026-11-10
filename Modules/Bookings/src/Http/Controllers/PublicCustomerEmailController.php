<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;

class PublicCustomerEmailController extends Controller
{
    public function __construct(
        private readonly RecordCustomerActivity $activityLogger,
    ) {}

    public function unsubscribe(Customer $customer): Response
    {
        if ($customer->email_opt_out_at === null) {
            $customer->forceFill(['email_opt_out_at' => now()])->save();

            $this->activityLogger->handle(
                $customer,
                CustomerActivityType::System,
                'El cliente canceló la suscripción de correos.',
            );
        }

        return Inertia::render('Bookings/Public/Unsubscribed', [
            'customerName' => $customer->name,
        ]);
    }
}
