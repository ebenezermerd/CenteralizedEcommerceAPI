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
            transform: translateY(4px);
            transition: transform 0.3s ease;
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
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            background: #FED7D7;
            color: #9B2C2C;
            margin: 15px 0;
        }
        .notice-box {
            background: #FEF3C7;
            border-left: 4px solid #D97706;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #92400E;
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
            <h1>Product Creation Restricted</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $company->owner->firstName }} {{ $company->owner->lastName }},</p>
                <p>We noticed that you attempted to create products in your supplier account. However, your company account requires approval before you can start listing products.</p>
            </div>

            <div class="status-badge">
                Current Status: {{ ucfirst($company->status) }}
            </div>

            <div class="notice-box">
                <strong>Next Steps:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Wait for admin approval of your company account</li>
                    <li>Complete any pending documentation if requested</li>
                    <li>Ensure your company profile is complete</li>
                    <li>You will receive an email notification once approved</li>
                </ul>
            </div>

            <p>Once your company is approved, you will have full access to:</p>
            <ul style="margin-left: 20px;">
                <li>Product listing creation</li>
                <li>Inventory management</li>
                <li>Order processing</li>
                <li>Sales analytics</li>
            </ul>

            <div class="contact-info">
                <strong>Need Help?</strong><br>
                Email: support@korecha.com<br>
                Phone: +251 911 123 456<br>
                Hours: Monday - Friday, 9:00 AM - 6:00 PM EAT
            </div>

            <div class="footer">
                <p>Thank you for your patience.</p>
                <p>Best regards,<br>The Korecha Team</p>
            </div>
        </div>
    </div>
</body>
</html>
