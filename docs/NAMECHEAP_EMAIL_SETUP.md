# Namecheap Email Setup

This document sets up outbound email for:

- `app.hatchers.ai`
- `bazaar.hatchers.ai`
- `servio.hatchers.ai`

The goal is:

- founders receive signup verification emails
- founders receive sign-in verification emails
- customers receive order and booking follow-up emails from Bazaar and Servio

## Recommended Mailbox

Create one dedicated transactional mailbox in Namecheap Private Email, for example:

- `no-reply@app.hatchers.ai`

You can also use:

- `support@app.hatchers.ai`

But `no-reply` is better for verification and automated follow-up.

## Namecheap SMTP Values

For Namecheap Private Email, the usual SMTP settings are:

- `MAIL_HOST=mail.privateemail.com`
- `MAIL_PORT=587`
- `MAIL_ENCRYPTION=tls`
- `MAIL_USERNAME=no-reply@app.hatchers.ai`
- `MAIL_PASSWORD=YOUR_NAMECHEAP_MAILBOX_PASSWORD`

If Namecheap requires SSL on the mailbox instead, use:

- `MAIL_PORT=465`
- `MAIL_ENCRYPTION=ssl`

## app.hatchers.ai

Add these values to `/home/hatchwan/app.hatchers.ai/.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.privateemail.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@app.hatchers.ai
MAIL_PASSWORD=YOUR_NAMECHEAP_MAILBOX_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@app.hatchers.ai
MAIL_FROM_NAME="Hatchers Ai Business OS"
MAIL_EHLO_DOMAIN=app.hatchers.ai
```

This powers:

- founder signup verification email
- founder sign-in verification email

## Bazaar

Add the same SMTP values to `/home/hatchwan/bazaar.hatchers.ai/.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.privateemail.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@app.hatchers.ai
MAIL_PASSWORD=YOUR_NAMECHEAP_MAILBOX_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@app.hatchers.ai
MAIL_FROM_NAME="Hatchers Commerce"
MAIL_EHLO_DOMAIN=bazaar.hatchers.ai
```

Bazaar already has dynamic mail/template support. This mailbox gives it a working fallback transactional sender for:

- order confirmation
- order status update
- order follow-up from Hatchers OS

## Servio

Add the same SMTP values to `/home/hatchwan/servio.hatchers.ai/.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.privateemail.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@app.hatchers.ai
MAIL_PASSWORD=YOUR_NAMECHEAP_MAILBOX_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@app.hatchers.ai
MAIL_FROM_NAME="Hatchers Services"
MAIL_EHLO_DOMAIN=servio.hatchers.ai
```

This powers:

- booking confirmation
- booking status update
- product-order follow-up
- booking follow-up from Hatchers OS

## DNS Checks In Namecheap

Make sure these records are configured for the sending domain:

- SPF
- DKIM
- MX

If Namecheap Private Email created them automatically, leave them as-is.

If mail lands in spam, first verify:

- mailbox password is correct
- SMTP host/port/encryption are correct
- SPF and DKIM are present on the domain

## Deployment Commands

After updating `.env` on `app.hatchers.ai`:

```bash
cd /home/hatchwan/app.hatchers.ai
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```

After updating `.env` on Bazaar:

```bash
cd /home/hatchwan/bazaar.hatchers.ai
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

After updating `.env` on Servio:

```bash
cd /home/hatchwan/servio.hatchers.ai
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

## Validation Checklist

Test these in order:

1. Founder signup on `app.hatchers.ai`
2. Founder receives email verification code
3. Founder verifies email
4. Founder logs in
5. Founder receives sign-in verification code
6. Founder completes login
7. Founder updates an order from OS with `Email` channel
8. Customer receives order follow-up email
9. Founder updates a booking from OS with `Email` channel
10. Customer receives booking follow-up email
