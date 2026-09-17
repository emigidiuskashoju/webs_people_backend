<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Webs People Verification
    </title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background: #f4f7f5;
        font-family: Arial, sans-serif;
    "
>
    <div
        style="
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
        "
    >
        <h1
            style="
                color: #16803c;
                margin-bottom: 24px;
            "
        >
            Webs People
        </h1>

        <p>
            Hello {{ $name }},
        </p>

        <p>
            Use the verification code below
            to complete your Webs People registration.
        </p>

        <div
            style="
                margin: 30px 0;
                padding: 20px;
                background: #f1f8f3;
                border-radius: 10px;
                text-align: center;
            "
        >
            <div
                style="
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 8px;
                    color: #16803c;
                "
            >
                {{ $code }}
            </div>
        </div>

        <p>
            This code expires in
            <strong>10 minutes</strong>.
        </p>

        <p>
            If you did not request this code,
            you can safely ignore this email.
        </p>

        <p
            style="
                margin-top: 35px;
                color: #777;
            "
        >
            Webs People
        </p>
    </div>
</body>
</html>