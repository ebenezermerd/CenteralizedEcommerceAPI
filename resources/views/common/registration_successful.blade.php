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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
            transform: translateY(4px);
            transition: transform 0.3s ease;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px;
            background: #f5f5f5;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #2d3748;
        }
        .registration-details {
            background: #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(4px);
            transition: transform 0.3s ease;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #4a5568;
            font-weight: 500;
        }
        .detail-value {
            font-family: 'Courier New', monospace;
            color: #2d3748;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #4a5568;
            font-size: 14px;
        }
        .contact-info {
            margin-top: 15px;
            padding: 15px;
            background: #edf2f7;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(4px);
            transition: transform 0.3s ease;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            background: {{ $user->getRoleNames()->first() === 'supplier' ? '#FED7D7' : '#C6F6D5' }};
            color: {{ $user->getRoleNames()->first() === 'supplier' ? '#9B2C2C' : '#276749' }};
            margin-top: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(4px);
            transition: transform 0.3s ease;
        }
        .company-details {
            background: #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(4px);
            transition: transform 0.3s ease;
        }
        .notice-box {
            background: #FEF3C7;
            border-left: 4px solid #D97706;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #92400E;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo/full-logo.png') }}" alt="Korecha Logo" class="logo">
            <h1>Registration Successful</h1>
            <p>Thank you for choosing Korecha</p>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $user->firstName }} {{ $user->lastName }},</p>
                <p>Your registration has been successful. Here are your details:</p>
            </div>

            <div class="registration-details">
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">  {{ $user->email }}  </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Phone Number</span>
                    <span class="detail-value">  {{ $user->phone }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Address</span>
                    <span class="detail-value">  {{ $user->address }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Role</span>
                    <span class="detail-value">  {{ $user->getRoleNames()->first() }}</span>
                </div>

                <div class="status-badge">
                    {{ $user->getRoleNames()->first() === 'supplier' ? 'Pending Approval' : 'Active' }}
                </div>
            </div>

            @if($user->getRoleNames()->first() === 'supplier' && $user->company)
            <div class="company-details">
                <h3>Company Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Company Name</span>
                    <span class="detail-value">{{ $user->company->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span class="detail-value">{{ $user->company->description }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">{{ $user->company->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value">{{ $user->company->phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Country</span>
                    <span class="detail-value">{{ $user->company->country }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">City</span>
                    <span class="detail-value">{{ $user->company->city }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address</span>
                    <span class="detail-value">{{ $user->company->address }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: {{ $user->company->status === 'pending' ? '#9B2C2C' : '#276749' }}">
                        {{ ucfirst($user->company->status) }}
                    </span>
                </div>
            </div>

            <div class="notice-box">
                <strong>Important Notice:</strong>
                <p>Your supplier account and company registration are currently pending approval from our admin team. This process typically takes 24-48 hours. You will receive a notification email once your account has been approved.</p>
                <p>During this time:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>You can review and update your company profile</li>
                    <li>Prepare your product listings</li>
                    <li>Review our supplier guidelines and policies</li>
                    <li>Contact our support team if you have any questions</li>
                </ul>
            </div>
            @endif

            <div class="contact-info">
                <strong>Need Help?</strong><br>
                Email: support@korecha.com<br>
                Phone: +251 922 496 959<br>
                Hours: Monday - Friday, 9:00 AM - 6:00 PM EAT
            </div>

            <div class="footer">
                <p>Thank you for joining Korecha! {{ $user->getRoleNames()->first() === 'supplier' ? 'We look forward to partnering with you.' : 'We look forward to serving you.' }}</p>
                <p>Best regards,<br>The Korecha Team</p>
            </div>
        </div>
    </div>
</body>
</html>

