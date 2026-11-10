<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Webs Verification</title>
</head>

<body style="margin:0; padding:0; background-color:#eef2f0; font-family:Arial, Helvetica, sans-serif; color:#1f2a24;">

    <!-- Preheader: shows as preview text in the inbox, then hidden -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#eef2f0;">
        Your Webs verification code is {{ $code }}. It expires in 10 minutes.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f0; padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.06);">

                    <!-- Header band -->
                    <tr>
                        <td style="background-color:#16803c; padding:28px 30px; text-align:center;">
                            <div style="font-size:26px; font-weight:bold; color:#ffffff; letter-spacing:0.5px;">
                                Webs
                            </div>
                            <div style="font-size:13px; color:#d7f0e0; margin-top:4px;">
                                Private messaging &amp; live location
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:34px 30px 10px 30px;">
                            <p style="margin:0 0 14px 0; font-size:16px;">
                                Hello {{ $name }},
                            </p>
                            <p style="margin:0 0 4px 0; font-size:15px; color:#44524a; line-height:1.5;">
                                Use this code to finish setting up your Webs account.
                            </p>
                        </td>
                    </tr>

                    <!-- Code box -->
                    <tr>
                        <td style="padding:6px 30px 0 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f8f3; border:1px solid #cfe8d8; border-radius:12px;">
                                <tr>
                                    <td style="padding:22px 16px; text-align:center;">
                                        <div style="font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#5a6b61; margin-bottom:10px;">
                                            Your verification code
                                        </div>
                                        <div style="font-size:38px; font-weight:bold; letter-spacing:12px; color:#16803c; font-family:'Courier New', Courier, monospace;">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:12px 2px 0 2px; font-size:12px; color:#8a968f; text-align:center;">
                                Tip: tap and hold the code above to copy it.
                            </p>
                        </td>
                    </tr>

                    <!-- Meta -->
                    <tr>
                        <td style="padding:22px 30px 0 30px;">
                            <p style="margin:0 0 10px 0; font-size:14px; color:#44524a;">
                                This code expires in <strong style="color:#16803c;">10 minutes</strong>.
                            </p>
                            <p style="margin:0; font-size:13px; color:#8a968f; line-height:1.5;">
                                If you did not try to create a Webs account, you can safely ignore this email &mdash; no account will be created.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:24px 30px 0 30px;">
                            <div style="border-top:1px solid #e7ece9;"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:16px 30px 30px 30px; text-align:center;">
                            <div style="font-size:14px; font-weight:bold; color:#16803c;">Webs</div>
                            <div style="font-size:12px; color:#9aa69e; margin-top:4px;">
                                This is an automated message. Please do not reply.
                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
