# 📧 Gmail SMTP Setup Guide

## Quick Setup for Email Verification

Your platform now uses **Gmail SMTP** to send real verification emails. Follow these steps:

---

## Step 1: Create Gmail App Password

⚠️ **Important:** You CANNOT use your regular Gmail password for security reasons. You must create an "App Password".

### Instructions:

1. **Go to your Google Account:**
   - Visit: https://myaccount.google.com/

2. **Enable 2-Step Verification** (if not already enabled):
   - Go to Security → 2-Step Verification
   - Click "Get Started" and follow instructions
   - **This is required** before you can create App Passwords

3. **Create App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - OR: Security → 2-Step Verification → App passwords (at bottom)
   - Select app: "Mail"
   - Select device: "Other" and type "Sex Ed Platform"
   - Click "Generate"
   - **Copy the 16-character password** (e.g., `abcd efgh ijkl mnop`)
   - Save it securely - you won't see it again!

---

## Step 2: Update Your `.env` File

Open `.env` in your project root and update these lines:

```env
# Change from 'log' to 'smtp'
MAIL_MAILER=smtp

# Gmail SMTP Settings
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your.email@gmail.com
MAIL_PASSWORD=your_16_char_app_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your.email@gmail.com
MAIL_FROM_NAME="Sex Ed Platform"
```

### Example:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sexedplatform@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=sexedplatform@gmail.com
MAIL_FROM_NAME="Sex Ed Platform"
```

⚠️ **Remove spaces** from the app password when pasting!

---

## Step 3: Clear Config Cache

After updating `.env`, run:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Step 4: Test Email Sending

Test if emails work:

```bash
php artisan tinker
```

Then run:
```php
Mail::raw('Test email', function($msg) {
    $msg->to('test@gmail.com')->subject('Test');
});
```

Check the inbox of `test@gmail.com` - you should receive the email!

---

## ✅ What Emails Will Be Sent?

Your platform will send:

1. **Email Verification** - When 13+ users register
2. **Parent Verification** - When parents create accounts
3. **Password Reset** - When users forget password
4. **Child Account Created** - Credentials sent to parent
5. **Progress Notifications** - Optional, for parents

---

## 📊 Gmail Sending Limits

| Account Type | Daily Limit |
|--------------|-------------|
| **Free Gmail** | 500 emails/day |
| **Google Workspace** | 2,000 emails/day |

**For your thesis:** 500 emails/day is more than enough!

---

## 🛡️ Security Best Practices

1. ✅ **Never commit `.env` to Git** - It's already in `.gitignore`
2. ✅ **Use App Password, not regular password**
3. ✅ **Create a dedicated Gmail account** for the platform (optional but recommended)
4. ✅ **Keep App Password secret** - Treat it like a password

---

## 🚨 Troubleshooting

### Error: "Username and Password not accepted"
- ✅ Make sure you're using the **App Password**, not your Gmail password
- ✅ Remove any spaces from the app password
- ✅ Ensure 2-Step Verification is enabled

### Error: "Could not authenticate"
- ✅ Check `MAIL_USERNAME` matches the Gmail account
- ✅ Check `MAIL_ENCRYPTION=tls` (not `ssl`)
-  Check port is `587`

### Error: "Connection could not be established"
- ✅ Check your internet connection
- ✅ Try port `465` with `MAIL_ENCRYPTION=ssl` instead
- ✅ Check if firewall is blocking SMTP

### Emails going to Spam
- ✅ Ask recipients to mark as "Not Spam"
- ✅ Add a proper sender name in `MAIL_FROM_NAME`
- ✅ Consider using a custom domain in production

---

## 🎯 For Thesis Demo

### Recommended Setup:

1. **Create dedicated Gmail:** `your-thesis-sexed@gmail.com`
2. **Enable 2-Step Verification** on that account
3. **Generate App Password** for the platform
4. **Configure `.env`** with those credentials
5. **Test with panel members' emails** during defense

---

## 📝 Alternative: Mailtrap (For Testing Only)

If Gmail setup is complex, use Mailtrap for testing:

1. Sign up at https://mailtrap.io (free)
2. Get SMTP credentials from dashboard
3. Update `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_mailtrap_username
   MAIL_PASSWORD=your_mailtrap_password
   MAIL_ENCRYPTION=tls
   ```

**Advantage:** No real emails sent, all caught in Mailtrap inbox  
**Disadvantage:** Cannot demo to panel members (they won't receive emails)

---

## ✨ You're Ready!

Once configured, your platform will:
- ✅ Send verification emails to real Gmail accounts
- ✅ Verify 13+ users before allowing access
- ✅ Send parent registration confirmations
- ✅ Notify parents when child accounts are created

**Test registration now:**
1. Go to `http://127.0.0.1:8000/register`
2. Fill form with your Gmail
3. Check inbox for verification email!
