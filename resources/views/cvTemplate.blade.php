<!DOCTYPE HTML>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            /* Use Arial font, which supports Cyrillic characters */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        td {
            border: 1px solid black;
        }

        td {
            padding: 10px;
        }

        h1 {
            text-align: center;
        }

        tr td:first-child {
            font-weight: bold;
            width: 30%;
        }

        tr td:last-child {
            width: 70%;
        }

        .descriptionTitle {
            font-size: 15px;
            text-decoration: underline;
        }

        .marginBottom {
            margin-bottom: 50px;
        }
    </style>
</head>
<body>
    <h1>СВ Информация</h1>
    <h2 class="descriptionTitle">ЛИЧНА ИНФОРМАЦИЯ</h2>
    <table class="marginBottom">
        <tr>
            <td>Име</td>
            <td>{{$candidate->fullNameCyrillic}}</td>
        </tr>
        <tr>
            <td>Пол</td>
            <td>{{$candidate->gender}}</td>
        </tr>
        <tr>
            <td>Телефон</td>
            <td>{{$candidate->phoneNumber}}</td>
        </tr>
        <tr>
            <td>E-mail</td>
            <td>{{$candidate->email}}</td>
        </tr>
        <tr>
            <td>Дата на раждане</td>
            <td>{{$candidate->birthday}}</td>
        </tr>
        <tr>
            <td>Националност</td>
            <td>{{$candidate->nationality}}</td>
        </tr>
        <tr>
            <td>Семеен статус</td>
            <td>{{$candidate->martialStatus}}</td>
        </tr>
    </table>

    @if(isset($candidate->experiences))
    <h2 class="descriptionTitle">ТРУДОВ СТАЖ</h2>
    @foreach($candidate->experiences as $experience)
    <table class="marginBottom">
        <tr>
            <td>Дати (от-до)</td>
            <td>{{$experience->start_date}} - {{$experience->end_date}}</td>
        </tr>
        <tr>
            <td>Име на Компания</td>
            <td>{{$experience->company_name}}</td>
        </tr>
        <tr>
            <td>Специалност</td>
            <td>{{$experience->position}}</td>
        </tr>
    </table>
    @endforeach
    @endif
    
    @if(isset($candidate->educations))
    <h2 class="descriptionTitle">ОБРАЗОВАНИЕ</h2>
    @foreach($candidate->educations as $education)
    <table class="marginBottom">
        <tr>
            <td>Дати (от-до)</td>
            <td>{{$education->start_date}} - {{$education->end_date}}</td>
        </tr>
        <tr>
            <td>Име на Училище</td>
            <td>{{$education->school_name}}</td>
        </tr>
        <tr>
            <td>Степен</td>
            <td>{{$education->degree}}</td>
        </tr>
        <tr>
            <td>област на изучаване</td>
            <td>{{$education->field_of_study}}</td>
        </tr>
    </table>
    @endforeach
    @endif
    <h2 class="descriptionTitle">УМЕНИЯ И КОМПЕТЕНЦИИ</h2>
    <textarea style="width: 100%;" name="" id="" cols="30" rows="10">{{$candidate->notes_for_cv}}</textarea>

</body>

</html>