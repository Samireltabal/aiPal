<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — aiPal</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: ui-sans-serif, system-ui, sans-serif; }
        body { background: #111827; color: #f9fafb; display: flex; align-items: center; justify-content: center; }
        .card { text-align: center; max-width: 340px; padding: 2rem; }
        .icon { font-size: 3rem; margin-bottom: 1.5rem; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { font-size: 0.9rem; color: #9ca3af; line-height: 1.6; }
        button {
            margin-top: 1.5rem;
            padding: 0.6rem 1.4rem;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
        }
        button:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📡</div>
        <h1>You're offline</h1>
        <p>aiPal needs a connection to chat and sync. Check your network and try again.</p>
        <button onclick="window.location.reload()">Try again</button>
    </div>
</body>
</html>
