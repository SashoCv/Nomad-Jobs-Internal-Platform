<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Contracts Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Notification: Expiring Contracts</h1>

    <p>The following candidates have contracts expiring in one month:</p>

    <table>
        <thead>
        <tr>
            <th>Candidate Name</th>
            <th>Company</th>
            <th>Contract Expiration Date</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $candidate)
            <tr>
                <td>{{ $candidate->fullName }}</td>
                <td>{{ $candidate->company->nameOfCompany ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($candidate->contractPeriodDate)->format('d-m-Y') }} </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This is an automated notification. Please do not reply to this email.</p>
    </div>
</div>
</body>
</html>
