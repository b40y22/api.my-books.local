<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .logo::before {
            content: "🔐";
            font-size: 24px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
            color: #2d3748;
        }

        .message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }

        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .warning-box {
            background-color: #fff5f5;
            border-left: 4px solid #feb2b2;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }

        .warning-text {
            color: #c53030;
            font-size: 14px;
            font-weight: 500;
        }

        .expiry-info {
            background-color: #f7fafc;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }

        .expiry-text {
            color: #4a5568;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .expiry-text::before {
            content: "⏰";
            margin-right: 8px;
            font-size: 16px;
        }

        .alternative-text {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #718096;
        }

        .alternative-link {
            color: #ff6b6b;
            text-decoration: none;
            word-break: break-all;
            background-color: #fff5f5;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 8px;
            border: 1px solid #fed7d7;
        }

        .footer {
            background-color: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            font-size: 14px;
            color: #718096;
            margin-bottom: 10px;
        }

        .company-name {
            font-weight: 600;
            color: #2d3748;
        }

        .copyright {
            font-size: 12px;
            color: #a0aec0;
        }

        .security-tip {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border: 1px solid #e1bee7;
        }

        .security-tip-title {
            font-weight: 600;
            color: #4a148c;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .security-tip-title::before {
            content: "🛡️";
            margin-right: 8px;
        }

        .security-tip-text {
            color: #6a1b9a;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }

            .content {
                padding: 30px 20px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .reset-button {
                padding: 14px 28px;
                font-size: 15px;
            }

            .alternative-link {
                word-break: break-all;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <div class="logo"></div>
        <h1>{{ config('app.name') }}</h1>
        <p>Reset your password securely</p>
    </div>

    <div class="content">
        <div class="greeting">Hello, {{ $user->name }}!</div>

        <div class="message">
            You are receiving this email because we received a password reset request for your account. Click the button below to reset your password:
        </div>

        <div class="button-container">
            <a href="{{ $resetUrl }}" class="reset-button">Reset Password</a>
        </div>

        <div class="expiry-info">
            <div class="expiry-text">
                This password reset link will expire in {{ config('auth.passwords.users.expire', 60) }} minutes.
            </div>
        </div>

        <div class="warning-box">
            <div class="warning-text">
                If you did not request a password reset, please ignore this email. No changes will be made to your account.
            </div>
        </div>

        <div class="security-tip">
            <div class="security-tip-title">Security Tip</div>
            <div class="security-tip-text">
                Always verify that password reset emails come from {{ config('app.name') }}. Never share your password or reset links with others.
            </div>
        </div>

        <div class="alternative-text">
            If you're having trouble clicking the "Reset Password" button, copy and paste this link into your web browser:<br>
            <a href="{{ $resetUrl }}" class="alternative-link">{{ $resetUrl }}</a>
        </div>
    </div>

    <div class="footer">
        <div class="footer-text">
            Best regards,<br>
            Team <span class="company-name">{{ config('app.name') }}</span>
        </div>
        <div class="copyright">
            © {{ date('Y') }} All rights reserved
        </div>
    </div>
</div>
</body>
</html>
