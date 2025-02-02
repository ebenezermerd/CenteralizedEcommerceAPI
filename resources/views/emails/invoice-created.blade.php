<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #f8f9fa;
        }
        .logo {
            max-width: 200px;
        }
        .invoice-details {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Created</h1>
            <p>Thank you for your business with Koricha</p>
        </div>

        <div class="content">
            <p>Dear Valued Customer,</p>

            <p>Your invoice has been created successfully. Please find the details below:</p>

            <div class="invoice-details">
                <div class="amount-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format($invoice->subtotal, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span>Taxes:</span>
                    <span>${{ number_format($invoice->taxes, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span>Shipping:</span>
                    <span>${{ number_format($invoice->shipping, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span>Discount:</span>
                    <span>-${{ number_format($invoice->discount, 2) }}</span>
                </div>

                <div class="amount-row total">
                    <span>Total Amount:</span>
                    <span>${{ number_format($invoice->total_amount, 2) }}</span>
                </div>

                <p style="margin-top: 20px;">Status: <span style="text-transform: uppercase; color: #007bff;">{{ $invoice->status }}</span></p>
            </div>

            <p>If you have any questions or concerns, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br>
            The Koricha Team</p>
        </div>
    </div>
</body>
</html>
