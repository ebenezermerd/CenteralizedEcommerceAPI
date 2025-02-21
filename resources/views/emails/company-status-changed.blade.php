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
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            background: {{
                match($company->status) {
                    'active' => '#C6F6D5',
                    'inactive' => '#FED7D7',
                    'blocked' => '#FC8181',
                    default => '#FEF3C7'
                }
            }};
            color: {{
                match($company->status) {
                    'active' => '#276749',
                    'inactive' => '#9B2C2C',
                    'blocked' => '#822727',
                    default => '#92400E'
                }
            }};
            margin-top: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo/full-logo.png') }}" alt="Korecha Logo" class="logo">
            <h1>Company Status Update</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $company->owner->firstName }} {{ $company->owner->lastName }},</p>
                <p>Your company status has been updated:</p>
            </div>

            <div class="company-details">
                <div class="detail-row">
                    <span class="detail-label">Company Name</span>
                    <span class="detail-value">{{ $company->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Previous Status</span>
                    <span class="detail-value">{{ ucfirst($previousStatus) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">New Status</span>
                    <span class="status-badge">{{ ucfirst($company->status) }}</span>
                </div>
            </div>

            @if($company->status === 'active')
            <div class="notice-box" style="background: #C6F6D5; border-left-color: #276749; color: #276749;">
                <strong>Congratulations!</strong>
                <p>Your company has been approved. You can now:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Access your supplier dashboard</li>
                    <li>List your products</li>
                    <li>Manage your inventory</li>
                    <li>Start receiving orders</li>
                </ul>
            </div>
            @elseif($company->status === 'inactive')
            <div class="notice-box" style="background: #FED7D7; border-left-color: #9B2C2C; color: #9B2C2C;">
                <strong>Notice:</strong>
                <p>Your company account has been deactivated. This may be due to:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Inactivity</li>
                    <li>Incomplete documentation</li>
                    <li>Violation of terms</li>
                </ul>
                <p>Please contact support for more information.</p>
            </div>
            @elseif($company->status === 'blocked')
            <div class="notice-box" style="background: #FC8181; border-left-color: #822727; color: #822727;">
                <strong>Important Notice:</strong>
                <p>Your company account has been suspended due to a violation of our terms of service. Please contact our support team immediately for more information.</p>
            </div>
            @endif

            <div class="contact-info">
                <strong>Need Help?</strong><br>
                Email: support@korecha.com<br>
                Phone: +251 911 123 456<br>
                Hours: Monday - Friday, 9:00 AM - 6:00 PM EAT
            </div>

            <div class="footer">
                <p>Best regards,<br>The Korecha Team</p>
            </div>
        </div>
    </div>
</body>
</html>
