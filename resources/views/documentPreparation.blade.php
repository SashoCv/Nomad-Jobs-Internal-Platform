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
        <th>ID</th>
        <th>Candidate ID</th>
        <th>User ID</th>
        <th>Medical Certificate</th>
        <th>Date of Preparation</th>
        <th>Submission Date</th>
        <th>Authorization</th>
        <th>Residence Declaration</th>
        <th>Justification Authorization</th>
        <th>Declaration of Foreigners</th>
        <th>Notarial Deed</th>
        <th>Conditions Met Declaration</th>
        <th>Job Description</th>
        <th>Employment Contract</th>
    </tr>
    </thead>
    <tbody>
    @foreach($documentPreparation as $preparation)
        <tr>
            <td>{{ $preparation['id'] }}</td>
            <td>{{ $preparation['candidate_id'] }}</td>
            <td>{{ $preparation['user_id'] }}</td>
            <td>{{ $preparation['medicalCertificate'] }}</td>
            <td>{{ $preparation['dateOfPreparationOnDocument'] }}</td>
            <td>{{ $preparation['submissionDate'] }}</td>
            <td>{{ $preparation['authorization'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['residenceDeclaration'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['justificationAuthorization'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['declarationOfForeigners'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['notarialDeed'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['conditionsMetDeclaration'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['jobDescription'] ? 'da' : 'ne' }}</td>
            <td>{{ $preparation['employmentContract'] ? 'da' : 'ne' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
