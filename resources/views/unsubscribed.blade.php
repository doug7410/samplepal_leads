<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed Successfully</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 40px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 15px;
        }
        .footer {
            margin-top: 30px;
            font-size: 0.9em;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('logo.svg') }}" alt="Logo" class="logo">
        
        <h1>You've Been Unsubscribed</h1>
        
        <p>Hello {{ $contact->first_name }},</p>
        
        <p>You have been successfully unsubscribed from our email communications. You will no longer receive emails from this campaign or future campaigns.</p>
        
        <p>If you unsubscribed by mistake or wish to resubscribe in the future, please contact us directly.</p>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>