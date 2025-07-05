<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Уведомление за статус</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Уведомление за статус</h2>

            <p style="font-size: 16px; color: #333;">
                Уважаеми представители на <strong>{{ $data['companyName'] }}</strong>,
            </p>

            <p style="font-size: 16px; color: #333;">
                Уведомяваме Ви, че <strong>{{ $data['candidateName'] }}</strong>
                ({{ $data['jobPosition'] }}), към дата {{ $data['statusDate'] }},
                е със сменен статус –
                <span style="font-weight: bold; color: #27ae60;">
                    Писмо за ЕРПР.
                </span>
            </p>

            <p style="font-size: 16px; color: #333;">
                В удобно за Вас време, съобразно адреса на местоживеене в България на чужденеца/-те,
                можем да съдействаме при повторното му/й отиване в дирекция „Миграция“ и снемането на биометрични данни (пръстови отпечатъци) за получаване на карта.
            </p>

            <p style="font-size: 16px; color: #333;">
                Настоящият имейл Ви се изпраща от <strong style="color: #2980b9;">Nomad Partners</strong>
            </p>

            <p style="font-size: 16px; color: #333;">
                Ако имате въпроси или се нуждаете от допълнителна информация, моля не се колебайте да се свържете с нас.
            </p>

            <p style="font-size: 16px; color: #333;">
                С уважение,<br>
                <strong>Екипът на Nomad Partners</strong>
            </p>
        </td>
    </tr>
</table>
</body>
</html>
