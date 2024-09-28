<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Company Invoices</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Invoice Number</th>
                <th>Company Name</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($companyInvoices as $companyInvoice)
                <tr>
                    <td>{{ $companyInvoice['Invoice Number'] }}</td>
                    <td>{{ $companyInvoice['Company Name'] }}</td>
                    <td>{{ $companyInvoice['Invoice Date'] }}</td>
                    <td>{{ $companyInvoice['Due Date'] }}</td>
                    <td>{{ $companyInvoice['Status'] }}</td>
                    <td>{{ $companyInvoice['Invoice Amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
