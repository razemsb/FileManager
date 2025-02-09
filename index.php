<?php
//-----------------------------------------//
// очистка буфера
if (ob_get_level()) {
    ob_end_clean();
}
// для вывода ошибок
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// файлы для хранения данных
$pinnedFile = __DIR__ . '/temp/pinned_folders.txt';
$recentFile = __DIR__ . '/temp/recent_folders.txt';
//-----------------------------------------//

// функция для получения папок из директории
$dir = __DIR__; 
function getFolders($dir) {
    $folders = [];
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($path)) {

                    $lastModified = date("Y-m-d в H:i:s", filemtime($path));
                    $folders[] = [
                        'name' => $entry,
                        'path' => $path,
                        'last_modified' => $lastModified,
                        'subfolders' => getFolders($path)
                    ];
                }
            }
        }
        closedir($handle);
    }
    return $folders;
}
// получение данных о языках
function getFileLanguages($folderPath) {
    $languages = [
        'HTACCESS' => ['.htaccess'],
        'PHP' => ['.php'],
        'JavaScript' => ['.js'],
        'HTML' => ['.html'],
        'CSS' => ['.css'],
        'Python' => ['.py'],
        'SQL' => ['.sql'],
        'JSON' => ['.json'],
        'XML' => ['.xml'],
        'IMG' => ['.webp', '.png', '.jpg', '.jpeg'],
        'TXT' => ['.txt'],
        'PDF' => ['.pdf'],
        'DOC' => ['.doc', '.docx'],
        'XLS' => ['.xls', '.xlsx'],
        'PPT' => ['.ppt', '.pptx'],
        'MP3' => ['.mp3'],
        'MP4' => ['.mp4'],
        'ZIP' => ['.zip'],
        'RAR' => ['.rar'],
        'GIF' => ['.gif'],
        'SVG' => ['.svg'],
    ];

    $languageCount = [];
    $files = scandir($folderPath);

    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath)) {
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            foreach ($languages as $language => $extensions) {
                if (in_array('.' . $fileExtension, $extensions)) {
                    if (!isset($languageCount[$language])) {
                        $languageCount[$language] = 0;
                    }
                    $languageCount[$language]++;
                }
            }
        } elseif (is_dir($filePath) && $file != '.' && $file != '..') {

            $languageCount = array_merge($languageCount, getFileLanguages($filePath));
        }
    }

    return $languageCount;
}
//---------------------------------------//
// получение данных о языках
if (isset($_GET['folder'])) {
    $folderPath = $_GET['folder'];
    $languageCount = getFileLanguages($folderPath);
    echo json_encode($languageCount);
    exit;
}

// функция чтения данных из файла
function readFromFile($file) {
    return file_exists($file) ? array_filter(explode(PHP_EOL, file_get_contents($file))) : [];
}

// функция записи данных в файл
function writeToFile($file, $data) {
    file_put_contents($file, implode(PHP_EOL, $data));
}

//закрепленные папки
$pinnedFolders = readFromFile($pinnedFile);

// недавно посещенные папки (максимум 8)
$recentFolders = readFromFile($recentFile);

// обработчик закрепления папок
if (isset($_POST['pin_folder'])) {
    $folderToPin = $_POST['pin_folder'];
    if (!in_array($folderToPin, $pinnedFolders)) {
        $pinnedFolders[] = $folderToPin;
        writeToFile($pinnedFile, $pinnedFolders);
    }
}

// обработчик открепления папок
if (isset($_POST['unpin_folder'])) {
    $folderToUnpin = $_POST['unpin_folder'];
    if (in_array($folderToUnpin, $pinnedFolders)) {
        $pinnedFolders = array_values(array_diff($pinnedFolders, [$folderToUnpin]));
        writeToFile($pinnedFile, $pinnedFolders);
    }
}

// обработчик недавно посещенных папок
if (isset($_POST['open'])) {
    $folderToVisit = $_POST['open'];
    if (!in_array($folderToVisit, $recentFolders)) {
        array_unshift($recentFolders, $folderToVisit);
        if (count($recentFolders) > 8) {
            array_pop($recentFolders);
        }
        writeToFile($recentFile, $recentFolders);
    }
}

// обработчик открытия папки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open'])) {
    $folderToOpen = $_POST['open'];
    header('Location: ' . $folderToOpen);
    exit;
}

// скачивание в .zip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_folder'])) {
    $folderToDownload = realpath($_POST['download_folder']);
    if (!$folderToDownload || !is_dir($folderToDownload)) {
        echo "Указанная папка не существует или недоступна.";
        exit;
    }
    $archiveName = basename($folderToDownload) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($archiveName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        echo "Ошибка создания архива.";
        exit;
    }
    $addFolderToZip = function ($folder, $zipArchive, $zipPath = '') use (&$addFolderToZip) {
        $files = scandir($folder);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $folder . DIRECTORY_SEPARATOR . $file;
            $relativePath = $zipPath . $file;

            if (is_file($filePath)) {
                $zipArchive->addFile($filePath, $relativePath);
            } elseif (is_dir($filePath)) {
                $zipArchive->addEmptyDir($relativePath);
                $addFolderToZip($filePath, $zipArchive, $relativePath . DIRECTORY_SEPARATOR);
            }
        }
    };
    $addFolderToZip($folderToDownload, $zip);
    $zip->close();
    if (!file_exists($archiveName) || filesize($archiveName) === 0) {
        echo "Ошибка создания архива.";
        exit;
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $archiveName . '"');
    header('Content-Length: ' . filesize($archiveName));
    readfile($archiveName);
    unlink($archiveName);
    exit;
}
// панки хой
// получение всех папок
$folders = getFolders(__DIR__);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enigma | Файловый Менеджер</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="temp/style.css">
    <style>
    html {
        transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, background-image 0.3s ease-in-out;
    }
    body {
     background-image: url('temp/background_light.jpg'); 
        background-repeat: repeat;
        background-size: auto; 
    }
    body #content {
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
    }
    body.dark-theme #content {
        background-color: #212529;
        border: 1px solid #343a40;
    }
    body.dark-theme {
        background-image: url('temp/background_dark.jpg'); 
        background-repeat: repeat;
        background-size: auto; 
        color: #f8f9fa;
    }
    </style>
</head>
<body>
<main>
<div class="container mt-3 pt-4 pb-1 mb-3" id="content" style="border-radius: 20px;">
<button id="theme-toggle" class="btn btn-light rounded-circle position-fixed top-0 end-0 m-3 shadow-sm">🌙</button>
<a href="http://localhost/phpMyAdmin/index.php?route=/&route=%2F" id="db" class="btn btn-light rounded-circle position-fixed end-0 m-3 shadow-sm text-decoration-none" target="_blank" rel="noopener noreferrer">БД</a>
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasExampleLabel">Меню</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
    <img src="temp/mita.jpeg" class="mx-auto d-block rounded-circle" style="margin-left: auto; margin-right: auto; width: 90%;">
    <p class="mt-2 text-center">by razemsb</p>
        <hr>
        <h6>Закрепленные папки</h6>
        <ul class="list-group list-group-flush mb-4">
            <?php if (count($pinnedFolders) > 0): ?>
                <?php foreach ($pinnedFolders as $folder): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo $folder; ?>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="open" value="<?php echo $folder; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Открыть</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item">Нет закрепленных папок</li>
            <?php endif; ?>
        </ul>
        <hr>
        <h6>Недавно посещенные папки</h6>
        <ul class="list-group list-group-flush">
            <?php if (count($recentFolders) > 0): ?>
                <?php foreach ($recentFolders as $folder): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo $folder; ?>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="open" value="<?php echo $folder; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Открыть</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item">Нет недавно посещенных папок</li>
            <?php endif; ?>
        </ul>
        <hr>
        <h2 class="text-center mt-2">Лента кринжа...</h2>
        <hr>
        <div class="align-items-center">
            <img src="temp/zhaba_cringe.jpg" class="mx-auto d-block" style="margin-left: auto; margin-right: auto; width: 90%;">
        </div>
        <hr>
        <video src="temp/vid.mp4" class="mx-auto d-block" style="margin-left: auto; margin-right: auto; width: 90%;" controls loop></video>
        <hr>
        <p class="text-center mt-2">жаба мудак</p>
    </div>
</div>
<button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><img src="temp/menu.svg" style="width: 30px; height: 30px; object-fit: cover;"></button>
<h1 class="text-center mb-4">Enigma | Файловый Менеджер</h1>

   <div class="mb-4">
    <input type="text" id="searchInput" class="form-control" placeholder="Поиск папок..." onkeyup="searchFolders()">
    <ul id="searchResults" class="list-group mt-2" style="display: none;"></ul>
   </div>
   <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Закрепленные Папки (<?= count($pinnedFolders); ?>)</h3>
    </div>
    <div class="col-12">
        <div class="row" id="pinnedFoldersContainer" data-pinned-folders='<?php echo json_encode($pinnedFolders); ?>'>
            <?php 
            foreach ($pinnedFolders as $folder): ?>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $folder; ?><img src="temp/pinned.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value="<?php echo $folder; ?>">
                                <button type="submit" class="btn btn-primary w-100">Перейти в папку</button>
                            </form>
                            <form method="POST" action="" class="mt-2">
                                <input type="hidden" name="unpin_folder" value="<?php echo $folder; ?>">
                                <button type="submit" class="btn btn-outline-danger w-100">Открепить</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
 <div class="row mb-4">
     <div class="col-12">
         <h3>Недавно посещенные (<?= count($recentFolders); ?> из 8)</h3>
         <div class="row">
             <?php foreach ($recentFolders as $folder): ?>
                 <div class="col-md-3 mb-3">
                     <div class="card shadow-sm">
                         <div class="card-body">
                             <h5 class="card-title"><?php echo $folder; ?><p class="float-end">☑️</p></h5>
                             <form method="POST" action="">
                                 <input type="hidden" name="open" value="<?php echo $folder; ?>">
                                 <button type="submit" class="btn btn-primary w-100">Перейти в папку</button>
                             </form>
                         </div>
                     </div>
                 </div>
             <?php endforeach; ?>
         </div>
     </div>
 </div>
<div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Все Папки (<?= count($folders); ?>)</h3>
        <div class="d-flex">
            <?php if (count($folders) > 6): ?>
                <button id="showAllFolders" class="btn btn-outline w-10 mb-3 border-0" style="z-index: 1000;">
                    <img src="temp/down.svg" style="width: 30px; height: 30px; object-fit: cover;">
                </button>
                <button id="hideFolders" class="btn btn-outline w-10 mb-3 border-0" style="z-index:1000;display: none;">
                    <img src="temp/up.svg" style="width: 30px; height: 30px; object-fit: cover;">
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
    <div class="col-12">
        <div class="row" id="foldersContainer" data-folders='<?php echo json_encode($folders); ?>'>
        </div>
    </div>
</div>
</div>
</main>
<script src="temp/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>