<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webs Admin - Announcements</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #2E7D32; margin-top: 0; }
        input, textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        textarea { height: 120px; resize: vertical; }
        button { background: #2E7D32; color: white; border: none; padding: 14px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background: #1b5e20; }
        .success { background: #e8f5e9; color: #2E7D32; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .error { color: #d32f2f; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Publish Update to App</h1>
    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif
    <form action="{{ url('/admin/announcements') }}" method="POST">
        @csrf
        <label>Announcement Title</label>
        <input type="text" name="title" placeholder="e.g. New Features Added!" required>
        <label>Message Body</label>
        <textarea name="body" placeholder="Type the message users will see..." required></textarea>
        <label>Image URL (Optional)</label>
        <input type="url" name="image_url" placeholder="https://webzapp.org/assets/image.jpg">
        <button type="submit">Publish Announcement</button>
    </form>
</div>
</body>
</html>
