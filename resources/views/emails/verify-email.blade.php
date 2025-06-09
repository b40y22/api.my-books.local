<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Email Verification</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            content: "📧";
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

        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .alternative-text {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #718096;
        }

        .alternative-link {
            color: #667eea;
            text-decoration: none;
            word-break: break-all;
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

            .verify-button {
                padding: 14px 28px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <div class="logo"></div>
        <h1>{{ config('app.name') }}</h1>
        <p>Please verify your email address</p>
    </div>
    <div class="content">
        <div class="greeting">Hello, {{ $user->name }}!</div>

        <div class="message">
            Thank you for registering with {{ config('app.name') }}. To complete your registration and activate your account, please verify your email address by clicking the button below:
        </div>

        <div class="button-container">
            <a href="{{ $verifyUrl }}" class="verify-button">Verify Email</a>
        </div>

        <div class="alternative-text">
            If you cannot click the button, copy and paste this link into your browser:<br>
            <a href="{{ $verifyUrl }}" class="alternative-link">{{ $verifyUrl }}</a>
        </div>

        <div class="message" style="margin-top: 30px; font-size: 14px;">
            If you did not create an account, please ignore this email.
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
