<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - AquaFlow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #FEF3C7;
            color: #F59E0B;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
        }
        h1 {
            color: #111827;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .message {
            color: #6B7280;
            font-size: 16px;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            background: #F3F4F6;
            color: #374151;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #E5E7EB;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">!</div>
        <h1>Something Went Wrong</h1>
        <p class="message">{{ $message }}</p>
        <a href="javascript:window.close()" class="btn">Close Window</a>
    </div>
</body>
</html>
