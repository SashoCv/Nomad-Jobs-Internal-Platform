<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Contracts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Notification: Expiring Contracts</h1>

        <p>The following candidates have contracts expiring in one month:</p>
        <table class="table table-striped">
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
                    <td>{{ $candidate->contractPeriodDate }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
