<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verifica tu correo - Lockers UNET</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ asset('img/Logo_Lockers_UNET.png') }}" alt="Lockers UNET" style="max-width: 200px;">
    </div>

    <h2 style="color: #213779; text-align: center;">¡Bienvenido, {{ $userName }}!</h2>
    
    <p style="font-size: 16px;">
        Gracias por registrarte en <strong>Lockers UNET</strong>. 
        Para activar tu cuenta, usa el siguiente código de verificación:
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <span style="display: inline-block; background: #213779; color: white; font-size: 28px; font-weight: bold; padding: 15px 30px; border-radius: 8px; letter-spacing: 8px;">
            {{ $code }}
        </span>
    </div>

    <p style="font-size: 14px; color: #666;">
        ⏰ Este código expira en <strong>15 minutos</strong>.
    </p>

    <p style="font-size: 14px; color: #666;">
        🔒 Si no te registraste en Lockers UNET, puedes ignorar este correo.
    </p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

    <p style="font-size: 12px; color: #999; text-align: center;">
        Este es un mensaje automático, por favor no respondas.<br>
        © {{ date('Y') }} Lockers UNET - Universidad Nacional Experimental del Táchira
    </p>

</body>
</html>