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
        .alert-box {
            background: #FEF3C7;
            border-left: 4px solid #D97706;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #92400E;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #cbd5e0;
        }
        .products-table th {
            background: #edf2f7;
            font-weight: 600;
            color: #4a5568;
        }
        .products-table tr:last-child td {
            border-bottom: none;
        }
        .stock-critical {
            color: #e53e3e;
            font-weight: 600;
        }
        .stock-warning {
            color: #dd6b20;
            font-weight: 600;
        }
        .action-needed {
            background: #FED7D7;
            border-left: 4px solid #9B2C2C;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #9B2C2C;
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
            <h1>Low Stock Alert</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear Supplier,</p>
                <p>This is an automated alert to inform you that the following products are running low on stock:</p>
            </div>

            <div class="alert-box">
                <strong>Action Required:</strong>
                <p>The following products have stock levels below the minimum threshold of {{ $threshold }} units.</p>
            </div>

            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->sku }}</td>
                        <td class="{{ $product->quantity < 5 ? 'stock-critical' : 'stock-warning' }}">
                            {{ $product->quantity }}
                        </td>
                        <td>{{ $product->quantity < 5 ? 'Critical' : 'Warning' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="action-needed">
                <strong>Recommended Actions:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Review your inventory levels</li>
                    <li>Restock the listed products</li>
                    <li>Update stock quantities in your dashboard</li>
                    <li>Check for any pending orders</li>
                </ul>
            </div>

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
