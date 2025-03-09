<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .header {
            text-align: center;
            padding: 30px 0;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            color: #2d3748;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .logo {
            width: 150px;
            height: auto;
            margin: 0 auto 15px;
            display: block;
        }
        .content {
            padding: 30px;
            background: #f5f5f5;
        }
        .role-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            background: #EBF4FF;
            color: #2B6CB0;
            margin: 15px 0;
        }
        .notice-box {
            background: #E6FFFA;
            border-left: 4px solid #319795;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #2C7A7B;
        }
        .contact-info {
            margin-top: 15px;
            padding: 15px;
            background: #edf2f7;
            border-radius: 6px;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #4a5568;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo/full-logo.png') }}" alt="Korecha Logo" class="logo">
            <h1>Account Role Updated</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $user->firstName }} {{ $user->lastName }},</p>
                <p>Your account role on the Korecha platform has been updated.</p>
            </div>

            <div class="notice-box">
                <strong>Role Change Details:</strong>
                <p>Your role has been changed from <strong>{{ ucfirst($oldRole) }}</strong> to <strong>{{ ucfirst($newRole) }}</strong>.</p>
            </div>

            <p>This change may affect your access levels and available features on the platform.</p>

            <div class="contact-info">
                <strong>Need Help?</strong><br>
                Email: support@korecha.com<br>
                Phone: +251 922 496 959<br>
                Hours: Monday - Friday, 9:00 AM - 6:00 PM EAT
            </div>

            <div class="footer">
                <p>Best regards,<br>The Korecha Team</p>
            </div>
        </div>
    </div>
</body>
</html>
