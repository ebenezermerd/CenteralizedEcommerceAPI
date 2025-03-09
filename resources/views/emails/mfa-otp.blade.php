<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 200px;
            height: auto;
        }
        .otp-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 32px;
            letter-spacing: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .expiry-text {
            color: #666;
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <!-- Replace with your actual logo URL -->
            <img src="{{ asset('images/logo/full-logo.png') }}" alt="Korecha Logo">
        </div>

        <h1 style="text-align: center; color: #333;">Authentication Code</h1>

        <div class="otp-box">
            <p style="margin: 0;">Your One-Time Password is:</p>
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <p class="expiry-text">
            This code will expire in 10 minutes.<br>
            Please do not share this code with anyone.
        </p>

        <div class="footer">
            © {{ date('Y') }} Korecha. All rights reserved.<br>
            This is an automated message, please do not reply.
        </div>
    </div>
</body>
</html>
