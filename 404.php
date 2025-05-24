<?php
$path = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="data/img/favicon.ico">
    <title>Ошибка 404 - Страница не найдена</title>
    <link rel="stylesheet" href="data/css/style.min.css">
    <link href="data/vendors/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="data/vendors/bootstrap/icons/bootstrap-icons.min.css">
    <script src="data/js/dark-theme.js" defer></script>
    <script src="data/vendors/bootstrap/js/bootstrap.min.js" defer></script>
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
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        .app-header {
            background: linear-gradient(135deg, #1e1e2d 0%, #2d2d3d 100%);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-medium);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            color: var(--white);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .app-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .error-page-container {
            display: flex;
            flex: 1;
            padding-top: 80px;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .error-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 40px;
        }

        .error-text {
            width: 45%;
            padding: 40px;
            background: var(--white-transparent);
            border-radius: 20px;
            box-shadow: var(--shadow-medium);
            backdrop-filter: blur(10px);
            border: 1px solid var(--primary-light);
            animation: slideInLeft 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .error-image {
            width: 50%;
            position: relative;
            animation: floatIn 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s both;
        }

        .error-image img {
            width: 100%;
            height: 100%;
            max-width: 600px;
            border-radius: 20px;
            transform: perspective(1000px) rotateY(-10deg);
            transition: transform 0.5s ease;
        }

        .error-image:hover img {
            transform: perspective(1000px) rotateY(0deg);
        }

        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            background: linear-gradient(45deg, var(--primary-color), #00c6ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-message {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .folder-name {
            font-weight: 600;
            color: var(--primary-color);
            word-break: break-all;
            background: var(--primary-light);
            padding: 2px 8px;
            border-radius: 6px;
        }

        .back-button {
            background: var(--primary-color);
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .back-button:hover {
            background: #0069d9;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .back-button:active {
            transform: translateY(1px);
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes floatIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .dark body {
            background: #1a1a2e;
        }

        .dark .error-text {
            background-color: rgba(30, 41, 59, 0.9);
            color: #e0e0e0;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .dark .error-message {
            color: #e0e0e0;
        }

        .dark .folder-name {
            color: #a3d4ff;
            background: rgba(0, 123, 255, 0.2);
        }

        @media (max-width: 992px) {
            .error-content {
                flex-direction: column;
                padding: 20px;
            }

            .error-text,
            .error-image {
                width: 100%;
                max-width: 100%;
            }

            .error-text {
                margin-bottom: 40px;
            }

            .error-image img {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="app-header">
        <div class="header-left">
            <button class="sidebar-toggle">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="app-title">
                <svg width="40px" height="40px" viewBox="0 0 1024 1024" style="border-radius: 10px; margin-top: 5px;"
                    fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1024 0H0V1024H1024V0Z" fill="#5D56C3" class="svg-elem-1"></path>
                    <rect x="96" y="97" width="832" height="825" rx="140" fill="#7D97FF" class="svg-elem-2"></rect>
                    <path
                        d="M606.244 862.089C676.465 840.886 710.277 821.623 771.5 783.952C707.948 807.494 673.5 817.952 598 810.152C550.5 805.244 521 787.452 480 767.802C434.164 743.295 424 741.175 397 725.952C367.616 703.521 349 692.452 333.6 681.752C333.6 681.752 253 635.39 187 628.252C121 621.114 98.5 628 96 628L94.5 769.728C96 813.728 103 846.5 139 883C170.141 914.574 208.5 924.5 260.5 921.728L446 922C504.599 894.862 540.271 879.928 606.244 862.089Z"
                        fill="#5866C3" class="svg-elem-3"></path>
                    <path opacity="0.6"
                        d="M336 941C336 816.736 235.264 716 111 716C70.6354 716 32.7533 726.629 0 745.241V1024H320.196C330.394 998.318 336 970.314 336 941Z"
                        fill="#1C5298" class="svg-elem-4"></path>
                    <path opacity="0.99"
                        d="M797.992 221.637C846.69 279.534 876.497 292.35 928 309C934.263 210.782 924.055 170.369 882 133C839.794 97.8894 810.334 93.6344 755 96.9998C757.337 145.671 767.492 173.597 797.992 221.637Z"
                        fill="#9066C4" class="svg-elem-5"></path>
                    <path opacity="0.3"
                        d="M101 350C250.669 350 372 230.012 372 82C372 53.3991 367.47 25.8446 359.08 0H0V330.768C29.7528 342.593 62.1171 349.349 96 349.955C97.663 349.985 99.3298 350 101 350Z"
                        fill="#1C2F60" class="svg-elem-6"></path>
                    <path
                        d="M688.35 696C774.836 656.561 827.645 606.471 928 474V602C877.654 683.836 850.5 729 775.5 783C705 809.5 647.5 821.5 567 804.5C504.5 786.5 397.5 726 397.5 726L688.5 725L688.35 696Z"
                        fill="#7D86F2" class="svg-elem-7"></path>
                    <path
                        d="M214 542C160 519 134.627 514.663 96 509V628C130.791 623.498 154.311 625.509 197 629.35C239.855 639.702 263.343 643.649 336 682V628C294.037 592.925 269.5 573 214 542Z"
                        fill="#7D86F2" class="svg-elem-8"></path>
                    <path d="M336.5 267L335.5 726H686.5L686 626.5L447.5 626V542H649.5V447H447.5V366H684.5V267H336.5Z"
                        fill="#D9D9D9" class="svg-elem-9"></path>
                    <path opacity="0.3"
                        d="M755 90C755 214.264 855.736 315 980 315C995.058 315 1009.77 313.521 1024 310.7V0H773.722C761.68 27.5608 755 58.0003 755 90Z"
                        fill="#5D96FF" class="svg-elem-10"></path>
                </svg>
                <span>FileManager</span>
            </h1>
        </div>
    </header>

    <div class="error-page-container">
        <div class="error-content">
            <div class="error-text">
                <h1 class="error-code">404</h1>
                <p class="error-message">
                    Упс! Страница <span class="folder-name"><?= htmlspecialchars($path) ?></span> не найдена
                </p>
                <button onclick="window.history.back();" class="back-button">
                    <i class="bi bi-arrow-left"></i> Вернуться назад
                </button>
            </div>

            <div class="error-image">
                <img src="data/img/404.png" alt="404 Мем" class="meme-image">
            </div>
        </div>
    </div>

    <script>
        // Добавляем небольшую задержку для плавного появления
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.opacity = 0;
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = 1;
            }, 100);
        });
    </script>
</body>

</html>