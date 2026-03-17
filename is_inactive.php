<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status: Inactive</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .status-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
            text-align: center;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            background-color: #e0e0e0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
        }

        .status-icon svg {
            width: 40px;
            height: 40px;
            fill: none;
            stroke: #9e9e9e;
            stroke-width: 2;
        }

        h1 {
            color: #757575;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        p {
            color: #757575;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .status-badge {
            display: inline-block;
            background-color: #e0e0e0;
            color: #757575;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .action-button {
            background-color: #f5f5f5;
            color: #757575;
            border: 1px solid #e0e0e0;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-button:hover {
            background-color: #e0e0e0;
        }

        @media (max-width: 480px) {
            .status-card {
                padding: 20px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <main class="status-card" role="main">
        <div class="status-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path>
                <path d="M12 6.5c-.83 0-1.5.67-1.5 1.5v4c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V8c0-.83-.67-1.5-1.5-1.5z"></path>
                <circle cx="12" cy="16" r="1.5"></circle>
            </svg>
        </div>

        <div class="status-badge" role="status">Inactive</div>

        <h1>This account is currently inactive</h1>

        <p>The requested resource is not currently available. This may be due to account suspension, maintenance, or the service being temporarily disabled.</p>

        <button class="action-button">Contact Support</button>
    </main>
</body>

</html>