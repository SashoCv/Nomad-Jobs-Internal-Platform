<!DOCTYPE html>
<html lang="bg">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Уведомление за пристигане на {{ $data['candidateName'] }}</title>
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
    <h2>Уведомление за пристигане на {{ $data['candidateName'] }}</h2>
    <p>Здравейте,</p>
    <p>Следният кандидат ще пристигне скоро:</p>

    <div class="info">
        <p><strong>Име на кандидат:</strong> {{ $data['candidateName'] }}</p>
        <p><strong>Статус:</strong> {{ $data['status'] }}</p>
        <p><strong>Телефон за контакт:</strong> {{ $data['phone_number'] }}</p>
    </div>

    <p>
        Уведомяваме ви, че кандидатът {{ $data['candidateName'] }}, на длъжност, процедура {{ $data['contractType'] }},
        за "{{ $data['companyName'] }}", {{ $data['companyAddress'] }} ще пристигне на дата {{ $data['arrivalDate'] }} в {{ $data['arrivalTime'] }}.
        Наш сътрудник ще я посрещне и служителят ще бъде доведен до работното си място.
    </p>

    <div class="footer">
        <p>С уважение,</p>
        <p><strong>Nomad Partners</strong></p>
        <p>
            <a href="https://www.nomadpartners.bg" target="_blank">Посетете нашия уебсайт</a>
        </p>
    </div>
</div>
</body>
</html>
