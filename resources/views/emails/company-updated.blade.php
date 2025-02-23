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
            transform: translateY(4px);
            transition: transform 0.3s ease;
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
        .company-details {
            background: #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-label {
            color: #4a5568;
            font-weight: 500;
        }
        .detail-value {
            font-family: 'Courier New', monospace;
            color: #2d3748;
        }
        .changes-box {
            background: #EBF8FF;
            border-left: 4px solid #4299E1;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #2B6CB0;
        }
        .contact-info {
            margin-top: 15px;
            padding: 15px;
            background: #edf2f7;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            <h1>Company Information Updated</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $company->owner->firstName }} {{ $company->owner->lastName }},</p>
                <p>Your company information has been updated successfully.</p>
            </div>

            <div class="changes-box">
                <strong>Updated Information:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    @foreach($changedFields as $field => $value)
                        <li>{{ ucfirst(str_replace('_', ' ', $field)) }}: {{ $value }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="company-details">
                <h3>Current Company Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Company Name</span>
                    <span class="detail-value">{{ $company->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">{{ $company->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value">{{ $company->phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address</span>
                    <span class="detail-value">{{ $company->address }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">City</span>
                    <span class="detail-value">{{ $company->city }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Country</span>
                    <span class="detail-value">{{ $company->country }}</span>
                </div>
            </div>

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
