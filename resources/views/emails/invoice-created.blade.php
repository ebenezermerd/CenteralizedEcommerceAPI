<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9; /* Added a light background color for a clean look */
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Added a subtle shadow for depth */
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0; /* Rounded corners for a card-like look */
        }
        .logo {
            max-width: 200px;
            margin: auto; /* Center the logo horizontally */
            padding: 20px 0; /* Added padding for better spacing */
        }
        .invoice-details {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Added a subtle shadow for depth */
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
            <div class="logo">
                <img src="{{ asset('images/logo/full-logo.png') }}" alt="Koricha Logo">
            </div>
            <h1>Invoice Created</h1>
            <p>Thank you for your business with Koricha</p>
        </div>

        <div class="content">
            <p>Dear Valued Customer,</p>

            <p>Your invoice has been created successfully. Please find the details below:</p>

            <div class="invoice-details">
                <div class="amount-row">
                    <span>Subtotal:</span>
                    <span>ETB {{ number_format($invoice->subtotal, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span>Taxes:</span>
                    <span>ETB {{ number_format($invoice->taxes, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span>Shipping:</span>
                    <span>ETB {{ number_format($invoice->shipping, 2) }}</span>
                </div>

                <div class="amount-row">
                    <span>Discount:</span>
                    <span>-ETB {{ number_format($invoice->discount, 2) }}</span>
                </div>

                <div class="amount-row total">
                    <span>Total Amount:</span>
                    <span>ETB {{ number_format($invoice->total_amount, 2) }}</span>
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
