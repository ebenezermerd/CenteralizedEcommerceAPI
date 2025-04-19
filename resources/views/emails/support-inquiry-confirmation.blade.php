<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Inquiry Confirmation</title>
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
        <h1>Support Inquiry Confirmation</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $inquiry->name }},</p>
        
        <p>Thank you for contacting Korecha Support. We have received your inquiry and will respond as soon as possible.</p>
        
        <p>Your inquiry has been assigned the reference number: <strong>INQ-{{ $inquiry->id }}</strong>. Please quote this reference in any future correspondence regarding this matter.</p>
        
        <div class="inquiry-details">
            <h3>Your Inquiry Details:</h3>
            <p><strong>Subject:</strong> {{ $inquiry->subject }}</p>
            @if($inquiry->order_number)
            <p><strong>Order Number:</strong> {{ $inquiry->order_number }}</p>
            @endif
            <p><strong>Message:</strong> {{ $inquiry->message }}</p>
            <p><strong>Submitted on:</strong> {{ $inquiry->created_at->format('F j, Y, g:i a') }}</p>
        </div>
        
        <p>Our support team typically responds within 24-48 hours during business days. We appreciate your patience.</p>
        
        <p>If you have any additional information to add to your inquiry, please reply to this email with your reference number in the subject line.</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
        <p>&copy; {{ date('Y') }} Korecha. All rights reserved.</p>
    </div>
</body>
</html> 