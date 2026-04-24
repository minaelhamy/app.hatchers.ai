<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your sign-in code</title>
</head>
<body style="margin:0; padding:24px; background:#f6f1e8; font-family:Arial, sans-serif; color:#141414;">
    <div style="max-width:640px; margin:0 auto; background:#fffdf8; border:1px solid #dccfbf; border-radius:24px; padding:32px;">
        <p style="margin:0 0 12px; font-size:12px; letter-spacing:0.18em; text-transform:uppercase; color:#6a6259;">Hatchers Ai Business OS</p>
        <h1 style="margin:0 0 16px; font-size:32px;">Complete your founder sign-in</h1>
        <p style="margin:0 0 18px; line-height:1.6;">Hi {{ $founder->full_name }}, use the code below to finish signing in to your founder dashboard.</p>
        <div style="margin:0 0 18px; padding:18px 20px; background:#f0e7da; border-radius:18px; font-size:32px; font-weight:700; letter-spacing:0.3em; text-align:center;">
            {{ $code }}
        </div>
        <p style="margin:0 0 12px; line-height:1.6;">This code expires at {{ $expiresAt->format('M d, Y g:i A') }} Cairo time.</p>
        <p style="margin:0; line-height:1.6; color:#6a6259;">If this sign-in was not you, please reset your password and review your founder account immediately.</p>
    </div>
</body>
</html>
