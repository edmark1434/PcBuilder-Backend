<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Password Reset</title>
</head>

<body style="margin: 0; padding: 0; background-color: #000000; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #000000; padding: 40px 0;">
        <tr>
            <td align="center">

                <table width="90%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #111111; border-radius: 12px; padding: 40px;">
                    <tr>
                        <td align="center" style="padding-bottom: 20px;">
                            <h1 style="color: #ffffff; font-size: 40px; margin: 0; font-weight: bold;">
                                AutoBuild PC
                            </h1>
                            <p style="color: #bbbbbb; font-size: 16px; margin-top: 10px;">
                                Your perfect PC—automatically built by AI.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="left">
                            <h2 style="color: #ffffff; font-size: 24px; margin-bottom: 10px;">
                                Password Reset Code
                            </h2>
                            <p style="color: #cccccc; font-size: 16px; line-height: 1.5;">
                                We received a request to reset your password. Use the code below to continue.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <div style="
                                font-size: 32px;
                                font-weight: bold;
                                padding: 20px 40px;
                                color: white;
                                background-color: #db2777;
                                border-radius: 10px;
                                letter-spacing: 6px;
                                display: inline-block;
                            ">
                                {{ $code }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td align="left">
                            <p style="color: #aaaaaa; font-size: 14px; line-height: 1.5;">
                                If you didn’t request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding-top: 30px;">
                            <p style="color: #555555; font-size: 12px;">
                                © {{ date('Y') }} AutoBuild PC. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
