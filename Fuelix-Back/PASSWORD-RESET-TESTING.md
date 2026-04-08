# Password Reset Testing Guide

## Quick Test

### Method 1: Using PHP Script (Recommended)

1. **Request password reset:**
   ```bash
   php test-with-plain-token.php request
   ```

2. **Check Mailtrap inbox:**
   - Go to https://mailtrap.io/inboxes
   - Login with your credentials
   - Open the latest email
   - Find the reset URL, it looks like:
     ```
     http://localhost:8000/reset-password?token=LONG_TOKEN_HERE&email=test@example.com
     ```
   - Copy the token value

3. **Reset password with token:**
   ```bash
   php test-with-plain-token.php reset YOUR_TOKEN_HERE
   ```

### Method 2: Using cURL

1. **Request reset:**
   ```bash
   curl -X POST http://localhost:8000/api/forgot-password \
     -H "Content-Type: application/json" \
     -d "{\"email\": \"test@example.com\"}"
   ```

2. **Get token from Mailtrap email**

3. **Reset password:**
   ```bash
   curl -X POST http://localhost:8000/api/reset-password \
     -H "Content-Type: application/json" \
     -d "{
       \"token\": \"YOUR_TOKEN\",
       \"email\": \"test@example.com\",
       \"password\": \"newpassword123\",
       \"password_confirmation\": \"newpassword123\"
     }"
   ```

4. **Test login:**
   ```bash
   curl -X POST http://localhost:8000/api/login \
     -H "Content-Type: application/json" \
     -d "{
       \"email\": \"test@example.com\",
       \"password\": \"newpassword123\"
     }"
   ```

### Method 3: Using Postman/Thunder Client

Import the `password-reset-tests.json` collection and run the requests in order.

## Current Test User

- Email: `test@example.com`
- Password: (check database or reset it)

## Mailtrap Configuration

Your `.env` is configured with:
- Host: sandbox.smtp.mailtrap.io
- Port: 2525
- Username: 2979cff8af25a5
- From: app-fuelix@gmail.com

## Email Content

The reset email is in French and includes:
- Subject: "Réinitialisation de votre mot de passe - Fuelix"
- A reset button with the full URL
- Token expires in 60 minutes
- Throttle: 60 seconds between requests

## Troubleshooting

### "passwords.throttled" error
Wait 60 seconds between reset requests for the same email.

### "This password reset token is invalid"
- Token might be expired (60 minutes)
- Token might have been used already
- Make sure you copied the full token from the email URL

### Email not received
- Check Mailtrap inbox at https://mailtrap.io
- Verify MAIL_MAILER=smtp in .env
- Check Laravel logs: `storage/logs/laravel.log`

## Testing Flow

1. ✅ Forgot password request sent
2. ✅ Email received in Mailtrap
3. ⏳ Extract token from email
4. ⏳ Reset password with token
5. ⏳ Login with new password
6. ⏳ Verify old tokens are invalidated
