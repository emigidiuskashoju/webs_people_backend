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
        .preview-box { margin-top: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 12px; background: #fff; }
        .preview-header { display: flex; align-items: center; margin-bottom: 10px; }
        .preview-header img { width: 40px; height: 40px; border-radius: 8px; margin-right: 10px; }
        .preview-header h3 { margin: 0; font-size: 16px; color: #000; }
        .badge { color: #25D366; font-size: 18px; margin-left: 4px; }
        .preview-img { width: 100%; border-radius: 8px; margin-top: 10px; }
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

    <!-- Live Preview -->
    <div class="preview-box">
        <p style="margin-top:0; font-size:12px; color:#666; text-transform:uppercase; font-weight:bold;">Live Preview (How users see it)</p>
        <div class="preview-header">
            <img src="https://webzapp.org/assets/images/app_logo.png" alt="Logo" onerror="this.src='https://via.placeholder.com/40'">
            <h3>Webs <span class="badge">✔</span></h3>
        </div>
        <p style="font-size:14px; color:#333;" id="preview-body">Your message will appear here...</p>
        <img src="" class="preview-img" id="preview-image" style="display:none;">
    </div>
</div>

<script>
    // Live preview script
    document.querySelector('textarea[name="body"]').addEventListener('input', function(e) {
        document.getElementById('preview-body').innerText = e.target.value || 'Your message will appear here...';
    });
    
    document.querySelector('input[name="image_url"]').addEventListener('input', function(e) {
        const img = document.getElementById('preview-image');
        if (e.target.value) {
            img.src = e.target.value;
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
        }
    });
</script>

</body>
</html>