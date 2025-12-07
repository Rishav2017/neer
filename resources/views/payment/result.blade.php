<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Payment Successful' : 'Payment Failed' }} - AquaFlow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: {{ $success ? 'linear-gradient(135deg, #10B981 0%, #059669 100%)' : 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)' }};
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
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
        }
        .icon.success {
            background: #D1FAE5;
            color: #10B981;
        }
        .icon.failed {
            background: #FEE2E2;
            color: #EF4444;
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
        .order-id {
            background: #F3F4F6;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: monospace;
            color: #374151;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #10B981;
            color: white;
        }
        .btn-primary:hover {
            background: #059669;
        }
        .btn-secondary {
            background: #F3F4F6;
            color: #374151;
            margin-top: 12px;
        }
        .btn-secondary:hover {
            background: #E5E7EB;
        }
        .auto-close {
            color: #9CA3AF;
            font-size: 12px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon {{ $success ? 'success' : 'failed' }}">
            {{ $success ? '✓' : '✕' }}
        </div>

        <h1>{{ $success ? 'Payment Successful!' : 'Payment Failed' }}</h1>
        <p class="message">{{ $message }}</p>

        @if($order_id)
        <div class="order-id">
            Order #{{ strtoupper(substr($order_id, 0, 8)) }}
        </div>
        @endif

        <p class="auto-close">You can close this window and return to the app.</p>
    </div>

    <script>
        // Deep link back to the app after a delay
        const orderId = "{{ $order_id }}";
        const success = {{ $success ? 'true' : 'false' }};

        // Try to communicate with the app
        if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify({
                type: success ? 'PAYMENT_SUCCESS' : 'PAYMENT_FAILED',
                orderId: orderId
            }));
        }

        // Auto-close after 3 seconds
        setTimeout(function() {
            // Try deep link
            const deepLink = success
                ? `aquaflow://order-success?orderId=${orderId}`
                : `aquaflow://payment-failed?orderId=${orderId}`;

            window.location.href = deepLink;
        }, 2000);
    </script>
</body>
</html>
