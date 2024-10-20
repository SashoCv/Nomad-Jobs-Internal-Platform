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
                <th>Candidate Name</th>
                <th>Invoice Date</th>
                @foreach($companyInvoices as $companyInvoice)
                    @foreach($companyInvoice['Items'] as $invoiceItem)
                        <th>{{ $invoiceItem['Item Name'] }}</th>
                    @endforeach
                @endforeach
                <th>Amount For Invoice</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($companyInvoices as $companyInvoice)
                <tr>
                    <td>{{ $companyInvoice['Invoice Number'] }}</td>
                    <td>{{ $companyInvoice['Company Name'] }}</td>
                    <td>{{ $companyInvoice['candidate'] }}</td>
                    <td>{{ $companyInvoice['Invoice Date'] }}</td>
                    @foreach($companyInvoice['Items'] as $invoiceItem)
                        <td>{{ $invoiceItem['Total'] }} / {{ $invoiceItem['Percentage'] }}%</td>
                    @endforeach
                    <td>{{ $companyInvoice['Invoice Amount'] }}</td>
                    <td>{{ $companyInvoice['Status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
