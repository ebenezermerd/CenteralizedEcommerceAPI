<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Support Inquiry</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #362F2D;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
        }
        .content {
            background-color: #F9FAFB;
            padding: 20px;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #776B61;
        }
        .inquiry-details {
            margin-top: 20px;
            padding: 15px;
            background-color: #FFFFFF;
            border-left: 4px solid #8B593E;
        }
        .action-button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #8B593E;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .action-button:hover {
            background-color: #5E3A28;
        }
        h1 {
            color: #8B593E;
        }
        p {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ config('app.url') }}/images/logo/full-logo.png" alt="Korecha Logo" class="logo">
        <h1>New Support Inquiry</h1>
    </div>
    
    <div class="content">
        <p>A new support inquiry has been submitted and requires your attention.</p>
        
        <div class="inquiry-details">
            <h3>Inquiry Details:</h3>
            <p><strong>Reference:</strong> INQ-{{ $inquiry->id }}</p>
            <p><strong>Name:</strong> {{ $inquiry->name }}</p>
            <p><strong>Email:</strong> {{ $inquiry->email }}</p>
            @if($inquiry->order_number)
            <p><strong>Order Number:</strong> {{ $inquiry->order_number }}</p>
            @endif
            <p><strong>Subject:</strong> {{ $inquiry->subject }}</p>
            <p><strong>Message:</strong> {{ $inquiry->message }}</p>
            <p><strong>Submitted on:</strong> {{ $inquiry->created_at->format('F j, Y, g:i a') }}</p>
            @if($inquiry->attachment_path)
            <p><strong>Attachment:</strong> Available in the admin panel</p>
            @endif
        </div>
        
        <p>Please log in to the admin panel to respond to this inquiry.</p>
        
        <a href="{{ config('app.admin_url') }}/support/inquiries/{{ $inquiry->id }}" class="action-button">View Inquiry</a>
    </div>
    
    <div class="footer">
        <p>This is an automated message from the Korecha Support System.</p>
        <p>&copy; {{ date('Y') }} Korecha. All rights reserved.</p>
    </div>
</body>
</html>