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
        .invoice-details {
            background: #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(4px);
            transition: transform 0.3s ease;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .amount-row:last-child {
            border-bottom: none;
        }
        .amount-label {
            color: #4a5568;
            font-weight: 500;
        }
        .amount-value {
            font-family: 'Courier New', monospace;
            color: #2d3748;
        }
        .total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            background: {{ $invoice->status === 'paid' ? '#C6F6D5' : '#FED7D7' }};
            color: {{ $invoice->status === 'paid' ? '#276749' : '#9B2C2C' }};
            margin-top: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(4px);
            transition: transform 0.3s ease;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo/full-logo.png') }}" alt="Korecha Logo" class="logo">
            <h1>Invoice #{{ $invoice->invoice_number }}</h1>
            <p>Thank you for choosing Korecha</p>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $invoice->billTo->name }},</p>
                <p>Your invoice has been created successfully. Here are the details:</p>
            </div>

            <div class="invoice-details">
                <div class="amount-row">
                    <span class="amount-label">Subtotal</span>
                    <span class="amount-value">ETB {{ number_format($invoice->subtotal, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span class="amount-label">Taxes</span>
                    <span class="amount-value">ETB {{ number_format($invoice->taxes, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span class="amount-label">Shipping</span>
                    <span class="amount-value">ETB {{ number_format($invoice->shipping, 2) }}</span>
                </div>

                @if($invoice->discount > 0)
                <div class="amount-row">
                    <span class="amount-label">Discount</span>
                    <span class="amount-value">-ETB {{ number_format($invoice->discount, 2) }}</span>
                </div>
                @endif

                <div class="amount-row total">
                    <span class="amount-label">Total Amount</span>
                    <span class="amount-value">ETB {{ number_format($invoice->total_amount, 2) }}</span>
                </div>

                <div class="status-badge">
                    {{ $invoice->status }}
                </div>
            </div>

            <div class="contact-info">
                <strong>Need Help?</strong><br>
                Email: support@korecha.com<br>
                Phone: +251 911 123 456<br>
                Hours: Monday - Friday, 9:00 AM - 6:00 PM EAT
            </div>

            <div class="footer">
                <p>Thank you for your business! We appreciate your trust in Korecha.</p>
                <p>Best regards,<br>The Korecha Team</p>
            </div>
        </div>
    </div>
</body>
</html>
