<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - AquaFlow</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
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
        }
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo h1 {
            color: #10B981;
            font-size: 28px;
            font-weight: 700;
        }
        .order-info {
            background: #F3F4F6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .order-info h3 {
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .order-info .order-id {
            color: #6B7280;
            font-size: 12px;
            font-family: monospace;
        }
        .amount {
            text-align: center;
            margin-bottom: 24px;
        }
        .amount .label {
            color: #6B7280;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .amount .value {
            color: #111827;
            font-size: 36px;
            font-weight: 700;
        }
        .pay-btn {
            width: 100%;
            background: #10B981;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pay-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .pay-btn:disabled {
            background: #9CA3AF;
            cursor: not-allowed;
            transform: none;
        }
        .secure-badge {
            text-align: center;
            margin-top: 16px;
            color: #6B7280;
            font-size: 12px;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>AquaFlow</h1>
        </div>

        <div class="order-info">
            <h3>Order Details</h3>
            <p class="order-id">Order #{{ strtoupper(substr($order->id, 0, 8)) }}</p>
        </div>

        <div class="amount">
            <p class="label">Amount to Pay</p>
            <p class="value">₹{{ number_format($order->total_amount, 0) }}</p>
        </div>

        <button id="payBtn" class="pay-btn" onclick="startPayment()">
            Pay Now
        </button>

        <p class="secure-badge">🔒 Secured by Razorpay</p>
    </div>

    <script>
        function startPayment() {
            const btn = document.getElementById('payBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span>Processing...';

            const options = {
                key: "{{ $razorpay_key }}",
                amount: {{ $amount }},
                currency: "{{ $currency }}",
                name: "AquaFlow",
                description: "Order #{{ strtoupper(substr($order->id, 0, 8)) }}",
                order_id: "{{ $razorpay_order_id }}",
                prefill: {
                    name: "{{ $user->name }}",
                    email: "{{ $user->email ?? '' }}",
                    contact: "{{ $user->phone ?? '' }}"
                },
                theme: {
                    color: "#10B981"
                },
                handler: function(response) {
                    // Payment successful - submit to server
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/payment/success';

                    const csrfField = document.createElement('input');
                    csrfField.type = 'hidden';
                    csrfField.name = '_token';
                    csrfField.value = '{{ csrf_token() }}';
                    form.appendChild(csrfField);

                    ['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature'].forEach(key => {
                        const field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = key;
                        field.value = response[key];
                        form.appendChild(field);
                    });

                    document.body.appendChild(form);
                    form.submit();
                },
                modal: {
                    ondismiss: function() {
                        btn.disabled = false;
                        btn.innerHTML = 'Pay Now';
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function(response) {
                btn.disabled = false;
                btn.innerHTML = 'Pay Now';
                alert('Payment failed: ' + response.error.description);
            });
            rzp.open();
        }

        // Auto-start payment on page load
        window.onload = function() {
            setTimeout(startPayment, 500);
        };
    </script>
</body>
</html>
