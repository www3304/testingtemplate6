<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .maintenance-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 600px;
            padding: 40px;
            text-align: center;
        }

        .icon-container {
            margin-bottom: 30px;
        }

        .icon-container svg {
            width: 100px;
            height: 100px;
            fill: none;
            stroke: #ff9800;
            stroke-width: 1.5;
        }

        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .subtitle {
            color: #ff9800;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 20px;
        }

        p {
            color: #555;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .time-container {
            background-color: #fff8e1;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
        }

        .time-label {
            font-weight: 600;
            color: #ff9800;
            margin-bottom: 5px;
        }

        .progress-container {
            background-color: #eeeeee;
            height: 10px;
            border-radius: 5px;
            margin: 30px 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 65%;
            background-color: #ff9800;
            border-radius: 5px;
        }

        .contact-info {
            font-size: 15px;
            color: #666;
            margin-top: 20px;
        }

        .contact-info a {
            color: #ff9800;
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .maintenance-container {
                padding: 25px;
            }

            h1 {
                font-size: 24px;
            }

            .icon-container svg {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>

<body>
    <main class="maintenance-container" role="main">
        <div class="icon-container" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
        </div>

        <h1>We're currently under maintenance</h1>
        <div class="subtitle">Our site is getting a tune-up</div>

        <p>We're performing scheduled maintenance to improve your experience. Our team is working hard to get everything back up and running as quickly as possible.</p>

        <div class="time-container">
            <div class="time-label">Estimated completion time:</div>
            <div id="completion-time">Today at 5:00 PM EST</div>
        </div>

        <div class="progress-container" role="progressbar" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar"></div>
        </div>

        <p>Thank you for your patience while we improve our services. We apologize for any inconvenience this may cause.</p>

        <div class="contact-info">
            For urgent inquiries, please contact us at <a href="mailto:support@example.com">support@example.com</a>
        </div>
    </main>
</body>

</html>