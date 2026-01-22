<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Напомняне: Изтичащи визи</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #9333ea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #9333ea;
            margin: 0;
            font-size: 24px;
        }
        .warning-badge {
            display: inline-block;
            background: #9333ea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #9333ea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f4ff;
        }
        .expiry-date {
            color: #9333ea;
            font-weight: 600;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .action-required {
            background: #fef3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .action-required h3 {
            color: #856404;
            margin: 0 0 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Напомняне за изтичащи визи</h1>
            <span class="warning-badge">Остават {{ $data['daysRemaining'] }}</span>
        </div>

        <div class="action-required">
            <h3>Изисква се действие</h3>
            <p>Следните визи изтичат след <strong>{{ $data['daysRemaining'] }}</strong>. Моля, предприемете необходимите действия за подновяване.</p>
        </div>

        @if(count($data['visas']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Кандидат</th>
                    <th>Компания</th>
                    <th>Дата на изтичане</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['visas'] as $visa)
                <tr>
                    <td>{{ $visa->candidate->fullNameCyrillic ?? $visa->candidate->fullName }}</td>
                    <td>{{ $visa->candidate->company->nameOfCompany ?? 'N/A' }}</td>
                    <td class="expiry-date">{{ \Carbon\Carbon::parse($visa->end_date)->format('d.m.Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="footer">
            <p>Това е автоматично съобщение. Моля, не отговаряйте на този имейл.</p>
            <p>© {{ date('Y') }} Nomad Cloud</p>
        </div>
    </div>
</body>
</html>
