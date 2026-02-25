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
        <th>Кандидат (Кирилица)</th>
        <th>Кандидат (Латиница)</th>
        <th>Компания</th>
        <th>Агент</th>
        <th>Тип услуга</th>
        <th>Ставка</th>
        <th>Дата</th>
        <th>Сума (EUR)</th>
        <th>Статус на фактура</th>
        <th>№ Фактура</th>
        <th>Бележки</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($invoices as $invoice)
        <tr>
            <td>{{ $invoice->id }}</td>
            <td>{{ $invoice->candidate->fullNameCyrillic ?? '' }}</td>
            <td>{{ $invoice->candidate->fullName ?? '' }}</td>
            <td>{{ $invoice->company->nameOfCompany ?? '' }}</td>
            <td>{{ ($invoice->agent->firstName ?? '') . ' ' . ($invoice->agent->lastName ?? '') }}</td>
            <td>{{ $invoice->serviceTypeName ?? 'N/A' }}</td>
            <td>{{ $invoice->statusName ?? '' }}</td>
            <td>{{ $invoice->statusDate ? \Carbon\Carbon::parse($invoice->statusDate)->format('d.m.Y') : '' }}</td>
            <td>{{ number_format($invoice->price, 2, ',', '.') }}</td>
            <td>{{ strtoupper(str_replace('_', ' ', $invoice->invoiceStatus)) }}</td>
            <td>{{ $invoice->invoice_number ?? '' }}</td>
            <td>{{ $invoice->notes ?? '' }}</td>
        </tr>
    @endforeach

    <!-- Summary Section -->
    <tr></tr>
    <tr>
        <td colspan="8">Фактурирано / Invoiced:</td>
        <td>{{ number_format($totalInvoiced, 2, ',', '.') }}</td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <td colspan="8">Нефактурирано / Not Invoiced:</td>
        <td>{{ number_format($totalNotInvoiced, 2, ',', '.') }}</td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <td colspan="8">Платено / Paid:</td>
        <td>{{ number_format($totalPaid, 2, ',', '.') }}</td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <td colspan="8">ОБЩО / TOTAL:</td>
        <td>{{ number_format($totalSum, 2, ',', '.') }}</td>
        <td colspan="3"></td>
    </tr>
    </tbody>
</table>
