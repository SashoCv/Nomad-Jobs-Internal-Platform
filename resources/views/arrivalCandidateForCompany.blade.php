<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Arrival Notification for {{ $data['candidateName'] }}</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #f2f3f8;
            font-family: Arial, sans-serif;
        }
        .container {
            width: 100%;
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333333;
        }
        p {
            font-size: 16px;
            color: #555555;
            line-height: 1.5;
        }
        .info {
            margin: 15px 0;
        }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            text-align: center;
            font-size: 14px;
            color: #888888;
        }
        .footer a {
            color: #4285f4;
            text-decoration: none;
        }
    </style>
</head>

<body>
<div class="container">
    <h2>Arrival Notification for {{ $data['candidateName'] }}</h2>
    <p>Hello,</p>
    <p>The following candidate is scheduled to arrive soon:</p>

    <div class="info">
        <p><strong>Candidate Name:</strong> {{ $data['candidateName'] }}</p>
        <p><strong>Status:</strong> {{ $data['status'] }}</p>
        <p><strong>Contact Phone:</strong> {{ $data['phone_number'] }}</p>
    </div>

    <p>If you have any questions or require further details, feel free to contact us. We look forward to ensuring a smooth process for the candidate's arrival.</p>

    <div class="footer">
        <p>Best regards,</p>
        <p><strong>Nomad Partners</strong></p>
        <p>
            <a href="https://www.nomadpartners.com" target="_blank">Visit Our Website</a>
        </p>
    </div>
</div>
</body>
</html>
