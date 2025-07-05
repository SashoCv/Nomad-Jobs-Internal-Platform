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
                Бихме искали да Ви уведомим, че <strong>{{ $data['candidateName'] }}</strong>
                ({{ $data['jobPosition'] }}), към дата {{ $data['statusDate'] }},
                е със сменен статус –
                <span style="font-weight: bold; color: #27ae60;">
                    получил разрешение.
                </span>
            </p>

            <p style="font-size: 16px; color: #333;">
                В законоустановения срок от 20 дни и след повторното съгласие от страна на кандидата
                за започване на работа, се уговаря конкретна среща с дата и час в съответното
                посолство на Република България. Подават се документи за издаване на виза,
                към които се прибавя медицинска застраховка, за която ще получите фактура,
                ако сте заявили подобна услуга от нас.
            </p>

            <p style="font-size: 16px; color: #333;">
                Настоящият имейл Ви се изпраща от <strong style="color: #2980b9;">Nomad Partners</strong>
            </p>

            <p style="font-size: 16px; color: #333;">
                Ако имате въпроси или се нуждаете от допълнителна информация,
                моля не се колебайте да се свържете с нас.
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
