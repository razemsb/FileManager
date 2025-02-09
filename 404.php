<?php
$path = trim($_SERVER['REQUEST_URI'], '/');
$parts = explode('/', $path);
$folderName = $parts[0].'/'.$parts[1] ?? 'Главная'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка 404 - `<?= $folderName ?>` Страница не найдена</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <style>
        body {
            background-image: url("https://static.vecteezy.com/system/resources/previews/000/228/775/original/vector-topographic-map-design-in-red-color.jpg");
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #1e1e1e;
        }
        .error-container {
            text-align: center;
            background-color: #2d2d2d; 
            border-radius: 40px;
            width: 60%;
            padding: 30px;
            height: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        .error-code {
            font-size: 60px;
            font-weight: bold;
            color: #ff3333; 
        }
        .error-message {
            font-size: 24px;
            color: #d3d3d3;
        }
        .folder-name {
            font-size: 30px;
            color: #ff3333; 
            font-weight: bold;
        }
        a {
            color: #ff3333;
        }
        a:hover {
            color: #e60000;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container error-container">
        <h1 class="error-code mt-5">Ошибка 404🐸</h1>
        <p class="error-message">Страница <span class="folder-name"><?= $folderName ?></span> не найдена. Вернитесь назад</p>
        <a onclick="window.history.back();" class="btn btn-outline-danger mt-3"><i class="bi bi-house-door"></i> Назад</a>
    </div>
</body>
</html>
