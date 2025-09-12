<table>
    <thead>
    <!-- Company Header -->
    <tr></tr>
    
    <!-- Report Title -->
    <tr></tr>
    
    <!-- Date Generated -->
    <tr></tr>
    
    <!-- Column Headers -->
    <tr>
        <th>ID</th>
        <th>Кандидат (Ќирилица)</th>
        <th>Кандидат (Латинично)</th>
        <th>Компанија</th>
        <th>Тип на услуга</th>
        <th>Статус</th>
        <th>Датум</th>
        <th>Износ (BGN)</th>
        <th>Статус на фактура</th>
        <th>Забелешки</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($invoices as $invoice)
        <tr>
            <td>{{ $invoice->id }}</td>
            <td>{{ $invoice->candidate->fullNameCyrillic ?? '' }}</td>
            <td>{{ $invoice->candidate->fullName ?? '' }}</td>
            <td>{{ $invoice->company->nameOfCompany ?? '' }}</td>
            <td>{{ $invoice->contractServiceType->name ?? 'N/A' }}</td>
            <td>{{ $invoice->statusName ?? '' }}</td>
            <td>{{ $invoice->statusDate ? \Carbon\Carbon::parse($invoice->statusDate)->format('d-m-Y') : '' }}</td>
            <td>{{ number_format($invoice->price, 2, ',', '.') }}</td>
            <td>{{ strtoupper(str_replace('_', ' ', $invoice->invoiceStatus)) }}</td>
            <td>{{ $invoice->notes ?? '' }}</td>
        </tr>
    @endforeach
    
    <!-- Summary Section -->
    <tr></tr> <!-- Empty row before summary -->
    <tr>
        <td colspan="7">Фактурирано / Invoiced:</td>
        <td>{{ number_format($totalInvoiced, 2, ',', '.') }}</td>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td colspan="7">Нефактурирано / Not Invoiced:</td>
        <td>{{ number_format($totalNotInvoiced, 2, ',', '.') }}</td>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td colspan="7">Отхвърлено / Rejected:</td>
        <td>{{ number_format($totalRejected, 2, ',', '.') }}</td>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td colspan="7">ОБЩО / TOTAL:</td>
        <td>{{ number_format($totalSum, 2, ',', '.') }}</td>
        <td colspan="2"></td>
    </tr>
    </tbody>
</table>