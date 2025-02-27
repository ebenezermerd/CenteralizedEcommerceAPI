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
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 30px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px 8px 0 0;
            color: #ffffff;
        }
        .content {
            padding: 30px;
        }
        .product-info {
            background: #f8fafc;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .product-details {
            margin: 10px 0;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-approved {
            background-color: #c6f6d5;
            color: #2f855a;
        }
        .status-rejected {
            background-color: #fed7d7;
            color: #c53030;
        }
        .reason-box {
            background: #fff5f5;
            border-left: 4px solid #fc8181;
            padding: 15px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #4c51bf;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-size: 0.875rem;
        }
        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Product {{ ucfirst($status) }}</h1>
        </div>

        <div class="content">
            <p>Dear {{ $product->vendor->firstName }},</p>

            @if($status === 'approved')
                <p>We are pleased to inform you that your product has been approved and is now live on our platform.</p>
            @else
                <p>Your product submission requires some changes before it can be approved.</p>
            @endif

            <div class="product-info">
                <h2>Product Details</h2>
                <div class="product-details">
                    <p><strong>Name:</strong> {{ $product->name }}</p>
                    <p><strong>SKU:</strong> {{ $product->sku }}</p>
                    <p><strong>Price:</strong> ${{ number_format($product->price, 2) }}</p>
                </div>
            </div>

            <div class="status {{ $status === 'approved' ? 'status-approved' : 'status-rejected' }}">
                Status: {{ ucfirst($status) }}
            </div>

            @if($status === 'rejected' && $reason)
                <div class="reason-box">
                    <h3>Reason for Changes Required:</h3>
                    <p>{{ $reason }}</p>
                </div>
                <p>Please review the feedback above and make the necessary changes to your product listing.</p>
            @endif

            @if($status === 'approved')
                <a href="{{ config('app.url') }}/dashboard/product/{{ $product->id }}" class="button">
                    View Your Product
                </a>
            @else
                <a href="{{ config('app.url') }}/dashboard/product/{{ $product->id }}/edit" class="button">
                    Edit Product
                </a>
            @endif

            <div class="divider"></div>

            <div class="footer">
                <p>Need help? Contact our support team at support@korecha.com</p>
                <p>© {{ date('Y') }} Korecha. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
