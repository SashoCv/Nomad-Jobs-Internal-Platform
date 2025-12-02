<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Password Reset</title>
    <meta name="description" content="Password Reset Email">
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
                            <img src="/public/images/Logo.webp" title="logo" alt="logo" style="height: 100px; width: 100%">
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
                                    <td style="padding: 0 35px;">
                                        <h1 style="color: #1e1e2d; font-weight: 500; margin: 0; font-size: 32px;">Password Reset Request</h1>
                                        <p style="color: #455056; font-size: 16px; line-height: 24px; margin-top: 20px;">Dear {{ $userName }},</p>
                                        <p style="color: #455056; font-size: 16px; line-height: 24px;">We received a request to reset your password for your Nomad Cloud account. Click the button below to set a new password:</p>
                                        <a href="{{ $resetUrl }}" style="display: inline-block; margin-top: 20px; padding: 14px 32px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: 500;">Reset Password</a>
                                        <p style="color: #455056; font-size: 14px; line-height: 24px; margin-top: 30px;">This link will expire in <strong>24 hours</strong>.</p>
                                        <p style="color: #455056; font-size: 14px; line-height: 24px;">If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                                        <p style="color: #999; font-size: 12px; line-height: 20px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">If the button above doesn't work, copy and paste this link into your browser:<br><a href="{{ $resetUrl }}" style="color: #4CAF50;">{{ $resetUrl }}</a></p>
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
                            <p style="font-size: 14px; color: rgba(69, 80, 86, 0.74); line-height: 18px; margin: 0;">&copy; <strong>www.nomadjobs.kg</strong></p>
                            <p style="font-size: 14px; color: rgba(69, 80, 86, 0.74); line-height: 18px; margin: 0;">&copy; <strong>https://www.nomadjobs.cloud/</strong></p>
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
