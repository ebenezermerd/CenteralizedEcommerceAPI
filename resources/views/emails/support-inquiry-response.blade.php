<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Response to Your Support Inquiry</title>
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
        .response {
            margin-top: 20px;
            padding: 15px;
            background-color: #EFE6DD;
            border-radius: 5px;
        }
        h1 {
            color: #8B593E;
        }
        p {
            margin-bottom: 15px;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #8B593E;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .button:hover {
            background-color: #5E3A28;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ config('app.url') }}/images/logo/full-logo.png" alt="Korecha Logo" class="logo">
        <h1>Response to Your Support Inquiry</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $inquiry->name }},</p>
        
        <p>Thank you for contacting Korecha Support. We have reviewed your inquiry (Reference: <strong>INQ-{{ $inquiry->id }}</strong>) and are pleased to provide you with a response.</p>
        
        <div class="inquiry-details">
            <h3>Your Original Inquiry:</h3>
            <p><strong>Subject:</strong> {{ $inquiry->subject }}</p>
            @if($inquiry->order_number)
            <p><strong>Order Number:</strong> {{ $inquiry->order_number }}</p>
            @endif
            <p><strong>Message:</strong> {{ $inquiry->message }}</p>
            <p><strong>Submitted on:</strong> {{ $inquiry->created_at->format('F j, Y, g:i a') }}</p>
        </div>
        
        <div class="response">
            <h3>Our Response:</h3>
            <p>{!! nl2br(e($responseMessage)) !!}</p>
        </div>
        
        <p>We hope this addresses your concerns. If you have any additional questions or need further assistance, please feel free to reply to this email or submit a new inquiry through our support page.</p>
        
        <p>Thank you for choosing Korecha.</p>
        
        <a href="{{ config('app.url') }}/support" class="button">Visit Support Center</a>
    </div>
    
    <div class="footer">
        <p>This email is in response to your support inquiry. Please do not reply to this email if your issue has been resolved.</p>
        <p>&copy; {{ date('Y') }} Korecha. All rights reserved.</p>
    </div>
</body>
</html>