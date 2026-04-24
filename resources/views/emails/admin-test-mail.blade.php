<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hatchers Ai Business OS test mail</title>
</head>
<body style="margin:0; padding:24px; background:#f6f1e8; font-family:Arial, sans-serif; color:#141414;">
    <div style="max-width:640px; margin:0 auto; background:#fffdf8; border:1px solid #dccfbf; border-radius:24px; padding:32px;">
        <p style="margin:0 0 12px; font-size:12px; letter-spacing:0.18em; text-transform:uppercase; color:#6a6259;">Hatchers Ai Business OS</p>
        <h1 style="margin:0 0 16px; font-size:32px;">Test mail delivered</h1>
        <p style="margin:0 0 16px; line-height:1.6;">This confirms that the OS mailer can send email from the current environment.</p>
        <div style="margin:0 0 18px; padding:18px 20px; background:#f0e7da; border-radius:18px;">
            <strong style="display:block; margin-bottom:8px;">Mail diagnostics</strong>
            <div style="line-height:1.7; color:#6a6259;">
                Mailer: {{ $diagnostics['mailer'] ?? 'smtp' }}<br>
                Host: {{ $diagnostics['host'] ?? 'missing' }}<br>
                Port: {{ $diagnostics['port'] ?? 'missing' }}<br>
                From: {{ $diagnostics['from_address'] ?? 'missing' }}<br>
                Encryption: {{ $diagnostics['encryption'] ?? 'missing' }}<br>
                Sent at: {{ $sentAt->format('M d, Y g:i A') }} Cairo time
            </div>
        </div>
        <p style="margin:0; line-height:1.6; color:#6a6259;">Triggered by {{ $admin->full_name }} from the Support Center.</p>
    </div>
</body>
</html>
