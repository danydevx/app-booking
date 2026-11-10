<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <title>{{ $subjectLine }}</title>
    </head>
    <body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
        <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;">
            <p style="margin:0 0 16px;font-size:15px;">Hola {{ $customerName }},</p>

            <div style="font-size:15px;line-height:1.6;">
                {!! nl2br(e($body)) !!}
            </div>

            <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0;" />

            <p style="margin:0;font-size:12px;line-height:1.5;color:#64748b;">
                Recibiste este correo porque tenemos una relación comercial
                contigo. Si no deseas recibir más correos, puedes
                <a href="{{ $unsubscribeUrl }}" style="color:#0f172a;">cancelar la suscripción</a>.
            </p>
        </div>
    </body>
</html>
