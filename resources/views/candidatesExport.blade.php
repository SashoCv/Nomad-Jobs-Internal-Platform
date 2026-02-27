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
        <th>#</th>
        <th>Име и Презиме</th>
        <th>Име (Ќирилица)</th>
        <th>Е-пошта</th>
        <th>Телефон</th>
        <th>Пол</th>
        <th>Националност</th>
        <th>Пасош</th>
        <th>Важи до</th>
        <th>Адреса (живеење)</th>
        <th>Работа (адреса)</th>
        <th>Позиција</th>
        <th>Договор</th>
        <th>Почеток</th>
        <th>Крај</th>
        <th>Плата (€)</th>
        <th>Дата на статус</th>
        <th>Статус</th>
        <th>Компанија</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($candidates as $index => $candidate)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $candidate->fullName }}</td>
            <td>{{ $candidate->fullNameCyrillic }}</td>
            <td>{{ $candidate->email }}</td>
            <td>{{ $candidate->phoneNumber }}</td>
            <td>{{ ucfirst($candidate->gender) }}</td>
            <td>{{ $candidate->nationality }}</td>
            <td>{{ $candidate->passportRecord?->passport_number }}</td>
            <td>{{ $candidate->passportRecord?->expiry_date }}</td>
            <td>{{ $candidate->addressOfResidence }}</td>
            <td>{{ $candidate->addressOfWork }}</td>
            <td>{{ $candidate->position->jobPosition ?? 'N/A' }}</td>
            <td>{{ $candidate->contractType }}</td>
            <td>{{ $candidate->startContractDate ?? '-' }}</td>
            <td>{{ $candidate->endContractDate ?? '-' }}</td>
            <td>{{ $candidate->salary }}</td>
            <td>
                {{ $candidate->latestStatusHistory && $candidate->latestStatusHistory->statusDate
                    ? \Carbon\Carbon::parse($candidate->latestStatusHistory->statusDate)->format('d-m-Y')
                    : '-' }}
            </td>
            <td>{{ $candidate->latestStatusHistory->status->nameOfStatus ?? 'N/A' }}</td>
            <td>{{ $candidate->company->nameOfCompany ?? 'N/A' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
