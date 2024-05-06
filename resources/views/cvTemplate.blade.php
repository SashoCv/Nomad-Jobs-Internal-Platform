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
        <tr>
            <td>Религия</td>
            <td></td>
        </tr>
    </table>

    <h2 class="descriptionTitle">ТРУДОВ СТАЖ</h2>
    <table class="marginBottom">
        <tr>
            <td>Дати (от-до)</td>
            <td></td>
        </tr>
        <tr>
            <td>Име и вид на обучаващата или образователната организация</td>
            <td></td>
        </tr>
        <tr>
            <td>Специалност</td>
            <td></td>
        </tr>
        <tr>
            <td>Придобитата квалификация</td>
            <td></td>
        </tr>
    </table>

    <h2 class="descriptionTitle">ОБРАЗОВАНИЕ И ОБУЧЕНИЕ</h2>
    <table class="marginBottom">
        <tr>
            <td>Дати (от-до)</td>
            <td></td>
        </tr>
        <tr>
            <td>Име и адрес на работодателя</td>
            <td></td>
        </tr>
        <tr>
            <td>Заемана длъжност</td>
            <td></td>
        </tr>
        <tr>
            <td>Основни дейности и отговорности</td>
            <td></td>
        </tr>
    </table>

    <h2 class="descriptionTitle">УМЕНИЯ И КОМПЕТЕНЦИИ</h2>
    <textarea style="width: 100%;" name="" id="" cols="30" rows="10"></textarea>

</body>

</html>