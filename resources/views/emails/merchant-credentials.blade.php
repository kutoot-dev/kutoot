<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Kutoot Store Login Credentials</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #f26a1b 0%, #8e0038 100%); padding: 28px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; font-size: 14px; opacity: 0.9; }
        .body { padding: 28px 24px; }
        .body p { color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .credentials { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .credentials .row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; }
        .credentials .row + .row { border-top: 1px solid #e2e8f0; }
        .credentials .label { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .credentials .value { font-size: 16px; font-weight: 700; color: #0f172a; font-family: monospace; }
        .warning { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px 16px; margin: 20px 0; font-size: 13px; color: #92400e; }
        .footer { padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Kutoot!</h1>
            <p>Your store has been approved</p>
        </div>

        <div class="body">
            <p>Hello,</p>
            <p>Great news! Your store <strong>{{ $storeName }}</strong> has been approved on Kutoot. You can now login to your store dashboard using the credentials below.</p>

            <div class="credentials">
                <div class="row">
                    <span class="label">Username</span>
                    <span class="value">{{ $username }}</span>
                </div>
                <div class="row">
                    <span class="label">Password</span>
                    <span class="value">{{ $password }}</span>
                </div>
            </div>

            <div class="warning">
                ⚠️ <strong>Important:</strong> Please change your password after your first login. Do not share these credentials with anyone.
            </div>

            <p>If you have any questions, please contact our support team at <a href="mailto:support@kutoot.com" style="color: #f26a1b;">support@kutoot.com</a>.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Kutoot. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
