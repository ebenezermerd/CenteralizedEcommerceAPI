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
            background: {{
                match($invoice->status) {
                    'paid' => '#C6F6D5',
                    'pending' => '#FEF3C7',
                    'overdue' => '#FED7D7',
                    default => '#EDF2F7'
                }
            }};
            color: {{
                match($invoice->status) {
                    'paid' => '#276749',
                    'pending' => '#92400E',
                    'overdue' => '#9B2C2C',
                    default => '#2D3748'
                }
            }};
            margin: 15px 0;
        }
        .invoice-details {
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
            border-bottom: 1px solid #cbd5e0;
        }
        .detail-label {
            color: #4a5568;
            font-weight: 500;
        }
        .detail-value {
            font-family: 'Courier New', monospace;
            color: #2d3748;
        }
        .amount {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
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
            <h1>Invoice Status Updated</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear {{ $invoice->user->firstName }} {{ $invoice->user->lastName }},</p>
                <p>The status of your invoice #{{ $invoice->invoice_number }} has been updated.</p>
            </div>

            <div class="invoice-details">
                <div class="detail-row">
                    <span class="detail-label">Previous Status</span>
                    <span class="detail-value">{{ ucfirst($previousStatus) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">New Status</span>
                    <span class="status-badge">{{ ucfirst($invoice->status) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Invoice Number</span>
                    <span class="detail-value">#{{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Due Date</span>
                    <span class="detail-value">
                        @php
                            try {
                                if (is_string($invoice->due_date)) {
                                    echo \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y');
                                } else {
                                    echo $invoice->due_date->format('M d, Y');
                                }
                            } catch (\Exception $e) {
                                echo $invoice->due_date;
                            }
                        @endphp
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount</span>
                    <span class="amount">ETB {{ number_format($invoice->total_amount, 2) }}</span>
                </div>
            </div>

            @if($invoice->status === 'paid')
            <div class="notice-box" style="background: #C6F6D5; border-left: 4px solid #276749; padding: 15px; margin: 20px 0; border-radius: 4px; color: #276749;">
                <strong>Thank You!</strong>
                <p>We have received your payment. Thank you for your business!</p>
            </div>
            @elseif($invoice->status === 'pending')
            <div class="notice-box" style="background: #FEF3C7; border-left: 4px solid #92400E; padding: 15px; margin: 20px 0; border-radius: 4px; color: #92400E;">
                <strong>Action Required:</strong>
                <p>Please process the payment before the due date to avoid any late fees.</p>
            </div>
            @elseif($invoice->status === 'overdue')
            <div class="notice-box" style="background: #FED7D7; border-left: 4px solid #9B2C2C; padding: 15px; margin: 20px 0; border-radius: 4px; color: #9B2C2C;">
                <strong>Important Notice:</strong>
                <p>This invoice is overdue. Please process the payment as soon as possible to avoid additional charges.</p>
            </div>
            @endif

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
