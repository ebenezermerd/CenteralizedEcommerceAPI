<!DOCTYPE html>
<html>
<head>
    <title>Your Verification Code</title>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fd;
            color: #2d3748;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 1s ease-out;
        }

        .logo img {
            max-width: 180px;
            height: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #1a202c;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #718096;
            font-size: 16px;
            margin: 0;
        }

        .otp-box {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin: 30px 0;
            animation: pulse 2s infinite;
        }

        .otp-code {
            font-size: 36px;
            letter-spacing: 8px;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .security-notice {
            background: #f7fafc;
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }

        .expiry-text {
            color: #64748b;
            text-align: center;
            font-size: 14px;
            margin-top: 30px;
            padding: 20px;
            background: #f1f5f9;
            border-radius: 8px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 13px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-links a {
            color: #4f46e5;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('images/logo/full-logo.png') }}" alt="Korecha Logo">
        </div>

        <div class="header">
            <h1>Verify Your Email</h1>
            <p>Please use the verification code below to complete your authentication</p>
        </div>

        <div class="otp-box">
            <p style="margin: 0; font-size: 16px;">Your Verification Code</p>
            <div class="otp-code">{{ $otp }}</div>
            <p style="margin: 5px 0; font-size: 14px;">Valid for 10 minutes only</p>
        </div>

        <div class="security-notice">
            <strong>🔒 Security Notice:</strong>
            <p style="margin: 5px 0;">Never share this code with anyone. Our team will never ask for this code.</p>
        </div>

        <div class="expiry-text">
            This verification code will expire in 10 minutes.<br>
            If you didn't request this code, please ignore this email.
        </div>

        <div class="footer">
            <div class="social-links">
                <a href="#">Facebook</a> • 
                <a href="#">Twitter</a> • 
                <a href="#">Instagram</a>
            </div>
            © {{ date('Y') }} Korecha. All rights reserved.<br>
            This is an automated message, please do not reply.<br>
            <small>Sent with ❤️ from Korecha Team</small>
        </div>
    </div>
</body>
</html>
