<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Напомняне: Изтичащи договори</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
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
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 24px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        tr:hover {
            background-color: #f0faf0;
        }
        .expiry-date {
            color: #d97706;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        .status-expiring {
            background: #fef3cd;
            color: #856404;
        }
        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-none {
            background: #f3f4f6;
            color: #6b7280;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Напомняне: Изтичащи договори</h1>
    </div>

    <div class="action-required">
        <h3>Изисква се действие</h3>
        <p>Следните кандидати имат договори, изтичащи след <strong>един месец</strong>. Моля, предприемете необходимите действия за подновяване.</p>
    </div>

    <table>
        <thead>
        <tr>
            <th>Кандидат</th>
            <th>Компания</th>
            <th>Обект / Адрес</th>
            <th>Позиция</th>
            <th>Статус на договор</th>
            <th>Дата на изтичане</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $contract)
            @php
                $contractStatus = $contract->contract_status ?? 'Няма данни';
                $statusClass = match($contractStatus) {
                    'Active' => 'status-active',
                    'Expiring soon' => 'status-expiring',
                    'Expired' => 'status-expired',
                    default => 'status-none',
                };
                $statusLabel = match($contractStatus) {
                    'Active' => 'Активен',
                    'Expiring soon' => 'Изтича скоро',
                    'Expired' => 'Изтекъл',
                    'No end date' => 'Без крайна дата',
                    default => 'Няма данни',
                };
                $facility = $contract->name_of_facility ?: $contract->address_of_work ?: $contract->companyAddress?->address ?: 'N/A';
                $position = $contract->position?->jobPosition ?? 'N/A';
            @endphp
            <tr>
                <td>{{ $contract->candidate?->fullNameCyrillic ?? $contract->candidate?->fullName ?? 'N/A' }}</td>
                <td>{{ $contract->candidate?->company?->nameOfCompany ?? 'N/A' }}</td>
                <td>{{ $facility }}</td>
                <td>{{ $position }}</td>
                <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td class="expiry-date">{{ \Carbon\Carbon::parse($contract->end_contract_date)->format('d.m.Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Това е автоматично съобщение. Моля, не отговаряйте на този имейл.</p>
        <p>&copy; {{ date('Y') }} Nomad Cloud</p>
    </div>
</div>
</body>
</html>
