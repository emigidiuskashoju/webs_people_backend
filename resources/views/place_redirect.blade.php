<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open in Webs</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; text-align: center; padding: 50px 20px; background: #f4f9f4; color: #333; }
        .btn { display: block; width: 100%; max-width: 300px; margin: 20px auto; padding: 15px; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 16px; }
        .btn-app { background: #2E7D32; color: white; }
        .btn-store { background: white; color: #2E7D32; border: 2px solid #2E7D32; }
        .logo { width: 100px; height: 100px; margin-bottom: 20px; border-radius: 20px; }
    </style>
</head>
<body>
    <img src="https://webzapp.org/assets/images/app_logo.png" class="logo" alt="Webs Logo">
    <h2>Open in Webs</h2>
    <p>Tap the button below to view this location.</p>
    
    <a href="webzapp://place/{{ $slug }}" class="btn btn-app">Open in App</a>
    <a href="https://github.com/emigidiuskashoju/webs_people/releases/latest" class="btn btn-store">Download Webs</a>

    <script>
        setTimeout(function() {
            window.location.href = "webzapp://place/{{ $slug }}";
        }, 500);
    </script>
</body>
</html>
