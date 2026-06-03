<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Security Warning - Leaving Watxio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-warning: #f59e0b;
            --accent-warning-glow: rgba(245, 158, 11, 0.15);
            --btn-proceed-bg: #f59e0b;
            --btn-proceed-hover: #d97706;
            --btn-back-bg: rgba(255, 255, 255, 0.05);
            --btn-back-hover: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 40%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        .container {
            max-width: 520px;
            width: 100%;
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #3b82f6);
        }

        .icon-container {
            width: 80px;
            height: 80px;
            background: var(--accent-warning-glow);
            border: 2px solid rgba(245, 158, 11, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            animation: pulse 2s infinite;
        }

        .icon-container svg {
            width: 40px;
            height: 40px;
            color: var(--accent-warning);
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        .subtitle {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .url-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 32px;
            text-align: left;
            word-break: break-all;
            position: relative;
        }

        .url-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: block;
        }

        .url-text {
            font-family: monospace;
            font-size: 14px;
            color: var(--accent-warning);
            font-weight: 600;
            line-height: 1.4;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            padding: 16px 24px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .btn-proceed {
            background-color: var(--btn-proceed-bg);
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .btn-proceed:hover {
            background-color: var(--btn-proceed-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
        }

        .btn-proceed:active {
            transform: translateY(0);
        }

        .btn-back {
            background-color: var(--btn-back-bg);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn-back:hover {
            background-color: var(--btn-back-hover);
            color: #fff;
        }

        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .footer a {
            color: var(--accent-warning);
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.2);
            }
            70% {
                box-shadow: 0 0 0 12px rgba(245, 158, 11, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon-container">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            
            <h1 id="redirect-warning-title">Security Alert: Leaving Platform</h1>
            
            <p class="subtitle">
                You are being redirected to an external website. Watxio does not control or verify content on external servers.
            </p>
            
            <div class="url-box" id="target-url-container">
                <span class="url-label">Destination URL</span>
                <span class="url-text" id="target-url">{{ $url }}</span>
            </div>
            
            <div class="actions">
                <a href="{{ $url }}" id="btn-proceed" class="btn btn-proceed" rel="noopener noreferrer">
                    Proceed to Link
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
                
                <a href="javascript:window.close();" id="btn-back" class="btn btn-back">
                    Go Back / Safety
                </a>
            </div>
            
            <p class="footer">
                Did you detect a malicious link? Report it to <a href="mailto:abuse@watxio.com">abuse@watxio.com</a>.
            </p>
        </div>
    </div>
</body>
</html>
