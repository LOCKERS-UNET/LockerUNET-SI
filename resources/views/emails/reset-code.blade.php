<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: #213779; padding: 28px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
        .body { padding: 32px 28px; text-align: center; }
        .body p { color: #404040; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .code-box { display: inline-block; background: #f0f4ff; border: 2px solid #213779; border-radius: 12px; padding: 16px 40px; font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #213779; margin: 8px 0 24px; }
        .note { color: #A3A3A3; font-size: 13px; }
        .footer { background: #f4f4f4; padding: 16px; text-align: center; color: #A3A3A3; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Lockers UNET</h1>
        </div>
        <div class="body">
            <p>Recibiste este correo porque solicitaste recuperar tu contraseña.<br>Usa el siguiente código de verificación:</p>
            <div class="code-box">{{ $code }}</div>
            <p class="note">Este código expira en <strong>15 minutos</strong>.<br>Si no solicitaste esto, ignora este correo.</p>
        </div>
        <div class="footer">
            © {{ date('Y') }} Lockers UNET — Universidad Nacional Experimental del Táchira
        </div>
    </div>
</body>
</html>