<?php
$path = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../temp/logo.svg">
    <title>Ошибка 404 - Страница не найдена</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-light: rgba(0, 123, 255, 0.1);
            --primary-dark: rgba(0, 123, 255, 0.3);
            --text-dark: #2d2d3d;
            --text-light: #6c757d;
            --white: #fff;
            --white-transparent: rgba(255, 255, 255, 0.95);
            --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-medium: 0 4px 12px rgba(0, 123, 255, 0.15);
            --transition-fast: 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-bar {
            background: linear-gradient(135deg, #1e1e2d 0%, #2d2d3d 100%);
            padding: 5px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-medium);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            color: var(--white);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 2px;
            width: 100%;
            background: linear-gradient(90deg,
                    var(--primary-color) 0%,
                    #00ff88 25%,
                    #00ffff 50%,
                    #00ff88 75%,
                    var(--primary-color) 100%);
            background-size: 200% 100%;
            animation: loading 1s linear infinite;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1001;
            width: 0;
        }
        .nav-bar.loading::before {
            opacity: 1;
            width: var(--loading-progress, 0%);
        }
        .company-name {
            text-align: center;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            position: relative;
            overflow: hidden;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            background: linear-gradient(45deg, var(--white), #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            padding: 5px 0;
        }

        .error-container {
            background: var(--white-transparent);
            border-radius: 16px;
            padding: 40px;
            width: 90%;
            max-width: 600px;
            text-align: center;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--primary-light);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease-out;
            margin-top: 80px;
        }

        .error-code {
            font-size: 4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .error-message {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 30px;
        }

        .folder-name {
            font-weight: 600;
            color: var(--primary-color);
            word-break: break-all;
        }

        .back-button {
            background: var(--white-transparent);
            border: 1px solid var(--primary-light);
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 16px;
            color: var(--text-dark);
            cursor: pointer;
            transition: var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: var(--primary-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <nav class="nav-bar">
        <div class="company-name">EnigmaDevix | FileManager</div>
    </nav>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <p class="error-message">
            Страница <span class="folder-name"><?= htmlspecialchars($path) ?></span> не найдена
        </p>
        <button onclick="window.history.back();" class="back-button">
            <i class="bi bi-arrow-left"></i> Вернуться назад
        </button>
    </div>
</body>

</html>