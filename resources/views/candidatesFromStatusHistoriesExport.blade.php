<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        h2 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<h2>Листа на кандидати</h2>

<table>
    <thead>
    <tr>
        <th>Име и Презиме</th>
        <th>Име (Ќирилица)</th>
        <th>Код на служител (ID)</th>
        <th>Националност</th>
        <th>Компанија</th>
        <th>Тип Договор</th>
        <th>Статус</th>
        <th>Дата на Статус</th>
        <th>Агент</th>
        <th>Бележки</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($allData as $index => $data)
        <tr>
            <td>{{ $data->candidate->fullName ?? '' }}</td>
            <td>{{ $data->candidate->fullNameCyrillic ?? '' }}</td>
            <td>{{ $data->candidate->id ?? '' }}</td>
            <td>{{ $data->candidate->nationality ?? '' }}</td>
            <td>{{ $data->candidate->company->nameOfCompany ?? 'N/A' }}</td>
            <td>
                {{ $data->candidate->contractType ?? '' }}
                @if(!empty($data->candidate->contractPeriod))
                    / {{ $data->candidate->contractPeriod }}
                @endif
            </td>
            <td>{{ $data->status->nameOfStatus ?? '' }}</td>
            <td>{{ optional($data->statusDate)->format('d.m.Y') ?? '' }}</td>
            <td>
                {{ optional($data->agent)->firstName ?? '' }}
                {{ optional($data->agent)->lastName ?? '' }}
            </td>
            <td>{{ $data->description ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
