<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document Preparation Table</title>
</head>
<body>

<table border="1" cellspacing="0" cellpadding="5">
    <thead>
    <tr>
        <th>Име на комания</th>
        <th>Име на кандидат</th>
        <th>Документи, подготвяни от:</th>
        <th>CR/ Diploma/ Medical Certificate</th>
        <th>Пълномощно</th>
        <th>Декл. за жилище</th>
        <th>Обосновка+обява</th>
        <th>Декларация чужденци и средночисленост</th>
        <th>Декларация спазени условия</th>
        <th>Трудов договор</th>
        <th>Длъжностна характеристика</th>
        <th>Нотариален акт/Договор за наем на обект на работа/Категоризация</th>
        <th>Входящ номер в Миграция</th>
        <th>Контакти на фирмата</th>
        <th>Дата на изготвяне на документите</th>
        <th>Дата на подаване на документите</th>
    </tr>
    </thead>
    <tbody>
    @foreach($documentPreparation as $preparation)
        <?php
          $candidate = \App\Models\Candidate::find($preparation['candidate_id']);
          $candidateFullName = $candidate->fullName;
          $candidateDossierNumber = $candidate->dossierNumber;
          $user = \App\Models\User::find($preparation['user_id']);
          $userFullName = $user->firstName . ' ' . $user->lastName;
          $company = \App\Models\Company::find($candidate->company_id);
          $companyName = $company->nameOfCompany;
          $companyEmail = $company->email;
        ?>
        <tr>
            <td>{{ $companyName }}</td>
            <td>{{ $candidateFullName }}</td>
            <td>{{ $userFullName }}</td>
            <td>{{ $preparation['medicalCertificate'] }}</td>
            <td>{{ $preparation['authorization'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['residenceDeclaration'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['justificationAuthorization'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['declarationOfForeigners'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['conditionsMetDeclaration'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['employmentContract'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['jobDescription'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['notarialDeed'] ? 'da' : 'ne' }}</td>
            <td>{{ $candidateDossierNumber }}</td>
            <td>{{ $companyEmail }}</td>
            <td>{{ $preparation['dateOfPreparationOnDocument'] }}</td>
            <td>{{ $preparation['submissionDate'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
