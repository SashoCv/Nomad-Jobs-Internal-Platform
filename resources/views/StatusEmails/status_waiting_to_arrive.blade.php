<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Уведомление за статус - Пристигане</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin:auto; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px; text-align: center;">
            {{-- Кандидат слика --}}
            @if(!empty($data['candidatePhoto']))
                <img src="{{ $data['candidatePhoto'] }}" alt="Снимка на {{ $data['candidateName'] }}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 2px solid #2980b9;">
            @endif
        </td>
    </tr>
    <tr>
        <td style="padding: 0 30px 30px 30px; color: #333;">
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Уведомление за статус</h2>

            <p style="font-size: 16px; margin-bottom: 15px;">
                Уважаеми представители на <strong>{{ $data['companyName'] }}</strong>,
            </p>

            <p style="font-size: 16px; margin-bottom: 15px;">
                Уведомяваме Ви, че кандидатът <strong>{{ $data['candidateName'] }}</strong>, на длъжност
                <strong>{{ $data['jobPosition'] }}</strong>, процедура <strong>{{ $data['contractType'] }}</strong>,
                за адрес <strong>{{ $data['companyAddress'] }}</strong> ще пристигне на дата
                <strong>{{ $data['arrivalDate'] ?? "" }}</strong> в <strong>{{ $data['arrivalTime'] ?? "" }}</strong> ч.
            </p>

            <p style="font-size: 16px; margin-bottom: 15px;">
                Наш сътрудник може да съдейства при посрещането му/я и служителят ще бъде доведен до
                работното си място или на друго, указано от вас, място.
            </p>

            <p style="font-size: 16px; margin-bottom: 15px;">
                В срок до 45 дни <strong>{{ $data['candidateName'] }}</strong> се регистрира в дирекция „Миграция“, МВР.
            </p>

            <p style="font-size: 16px; color: #2980b9; margin-bottom: 15px;">
                Настоящият имейл Ви се изпраща от <strong>Nomad Partners</strong>
            </p>

            <p style="font-size: 16px;">
                Ако имате въпроси или се нуждаете от допълнителна информация, моля не се колебайте да се свържете с нас.
            </p>

            <p style="font-size: 16px; margin-top: 30px;">
                С уважение,<br>
                <strong>Екипът на Nomad Partners</strong>
            </p>
        </td>
    </tr>
</table>
</body>
</html>
