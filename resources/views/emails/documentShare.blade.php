<!DOCTYPE html>
<html lang="bg">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>{{ $subjectLine }}</title>
    <style type="text/css">
        a:hover {
            text-decoration: underline !important;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8" style="font-family: 'Open Sans', sans-serif;">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width: 670px; margin: 0 auto;" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height: 80px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">
                            <img src="{{ config('app.url') }}/images/Logo.webp" title="Nomad Cloud" alt="Nomad Cloud Logo" style="height: 80px; width: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="height: 20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0" style="max-width: 670px; background: #fff; border-radius: 3px; text-align: center; box-shadow: 0 6px 18px 0 rgba(0,0,0,.06);">
                                <tr>
                                    <td style="height: 40px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 35px; text-align: left;">
                                        <h1 style="color: #1e1e2d; font-weight: 500; margin: 0; font-size: 24px; text-align: center;">{{ $subjectLine }}</h1>
                                        <div style="color: #455056; font-size: 16px; line-height: 24px; margin-top: 20px;">
                                            {!! nl2br(e($bodyMessage)) !!}
                                        </div>
                                        <p style="color: #455056; font-size: 14px; line-height: 24px; margin-top: 30px;">
                                            Моля, намерете споделените документи като прикачени файлове към този имейл.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height: 40px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="height: 20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">
                            <p style="font-size: 14px; color: rgba(69, 80, 86, 0.74); line-height: 18px; margin: 0;">&copy; {{ date('Y') }} <strong>Nomad Cloud</strong></p>
                            <p style="font-size: 12px; color: rgba(69, 80, 86, 0.5); line-height: 18px; margin: 5px 0 0 0;">{{ config('app.frontend_url') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height: 80px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
