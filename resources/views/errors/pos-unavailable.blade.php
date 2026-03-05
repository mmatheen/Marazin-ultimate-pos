<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Unavailable</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
        .error-container { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 560px; margin: 0 auto; }
        .error-icon { color: #dc3545; font-size: 48px; margin-bottom: 20px; }
        .error-title { color: #dc3545; font-size: 24px; margin-bottom: 15px; }
        .error-message { color: #666; margin-bottom: 20px; line-height: 1.5; }
        .back-button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; }
        .back-button:hover { background-color: #0056b3; color: white; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">POS Unavailable</h1>
        <p class="error-message">{{ $message ?? 'The POS page could not be loaded.' }}</p>
        <a href="{{ url('/dashboard') }}" class="back-button">Back to Dashboard</a>
    </div>
</body>
</html>
