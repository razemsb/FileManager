<?php
//-----------------------------------------//
// очистка буфера
if (ob_get_level()) {
    ob_end_clean();
}
//---------------------------------------//
// для вывода ошибок
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);
//---------------------------------------//
// файлы для хранения данных
$pinnedFile = __DIR__ . '/temp/pinned_folders.txt';
$recentFile = __DIR__ . '/temp/recent_folders.txt';
//-----------------------------------------//
// функция для получения папок из директории
$dir = __DIR__;
function getFolders($dir)
{
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
//---------------------------------------//
// получение данных о языках
function getFileLanguages($folderPath)
{
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
//---------------------------------------//
// функция чтения данных из файла
function readFromFile($file)
{
    return file_exists($file) ? array_filter(explode(PHP_EOL, file_get_contents($file))) : [];
}
//---------------------------------------//
// функция записи данных в файл
function writeToFile($file, $data)
{
    file_put_contents($file, implode(PHP_EOL, $data));
}
//---------------------------------------//
//закрепленные папки
$pinnedFolders = readFromFile($pinnedFile);
//---------------------------------------//
// недавно посещенные папки (максимум 8)
$recentFolders = readFromFile($recentFile);
//---------------------------------------//
// обработчик закрепления папок
if (isset($_POST['pin_folder'])) {
    $folderToPin = $_POST['pin_folder'];
    if (!in_array($folderToPin, $pinnedFolders)) {
        $pinnedFolders[] = $folderToPin;
        writeToFile($pinnedFile, $pinnedFolders);
    }
}
//---------------------------------------//
// обработчик открепления папок
if (isset($_POST['unpin_folder'])) {
    $folderToUnpin = $_POST['unpin_folder'];
    if (in_array($folderToUnpin, $pinnedFolders)) {
        $pinnedFolders = array_values(array_diff($pinnedFolders, [$folderToUnpin]));
        writeToFile($pinnedFile, $pinnedFolders);
    }
}
//---------------------------------------//
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
//---------------------------------------//
// обработчик открытия папки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open'])) {
    $folderToOpen = $_POST['open'];
    header('Location: ' . $folderToOpen);
    exit;
}
//---------------------------------------//
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
//---------------------------------------//
// функция для просмотра последнего редактирования в закрепленных/недавно посещаямых папок //
function getFolderLastModifiedTime($folderPinnedOrRecentPath)
{
    if (!is_dir($folderPinnedOrRecentPath)) {
        return "Не папка!";
    }

    $latestTime = 0;
    $files = scandir($folderPinnedOrRecentPath);

    foreach ($files as $file) {
        if ($file === "." || $file === "..")
            continue;

        $filePath = $folderPinnedOrRecentPath . DIRECTORY_SEPARATOR . $file;
        $fileTime = filemtime($filePath);

        if ($fileTime > $latestTime) {
            $latestTime = $fileTime;
        }
    }

    return date("d.m.Y В H:i:s", $latestTime);
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
    <link rel="shortcut icon" href="temp/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="temp/style.css">
    <script src="temp/script.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" defer></script>
    <style>
        h1, h2 ,h3 ,h4 ,h5 .h6 {
            color: white!important;
        }
        html {
            scroll-behavior: smooth;
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, background-image 0.3s ease-in-out;
            overflow-x: hidden;
        }
        body {
            background-image: url('temp/background.jpg');
            background-repeat: repeat;
            background-size: cover;
            color: #f8f9fa;
        }
        body #content {
            background-color: rgb(59, 59, 59);
            border: 1px solid rgb(0, 0, 0);
            outline: 5px solid rgb(78, 78, 78);
        }
        body.dark-theme #content {
            /*background-color: #212529;*/
            background-color: rgba(40, 33, 41, 0.54);
        }
        body.dark-theme {
            background-image: url('temp/background.jpg');
            background-repeat: repeat;
            background-size: cover;
            color: #f8f9fa;
            background-attachment: fixed;
        }
        .card {
            cursor: pointer;
            color: blue !important;
            background-color: grayscale !important;
            border: 2px solid rgb(206, 0, 221);
            height: 120px;
        }
        .btn.btn-outline-primary {
            border: 1px solid rgb(235, 175, 255) !important;
            color: #e0e0e0 !important;
        }
        .btn.btn-outline-primary:hover {
            background-color: rgb(206, 0, 221);
            border: 1px solid rgb(71, 0, 76) !important;
            color: black !important;
        }
        .btn.btn-outline-success {
            border: 1px solid rgb(0, 190, 73) !important;
            color: rgb(91, 255, 113);
        }
        .btn.btn-outline-success:hover {
            color: black !important;
        }
        i {
            color: rgb(255, 255, 255);
        }
        button.btn:hover i.bi.bi-pin-angle {
            color: rgb(91, 255, 113) !important;
        }
        /*
        button.btn:hover i.bi.bi-pin-angle-fill::after {
            content:url("");
        }
        button.btn:hover i.bi.bi-pin-angle-fill::before {
            content:url("temp/pin-angle-unpin.svg");
        }
        */
        button.btn:hover i.bi.bi-file-earmark-zip {
            color: rgb(209, 83, 255) !important;
        }
        button.btn:hover i.bi.bi-eye-fill {
            color: rgb(53, 228, 255) !important;
        }
        .card-title {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .text-last-mod {
            max-width: 150px;
            white-space: nowrap;
            font-size: 12px;
        }
        .btn.btn-unpin .bi.bi-pin-angle-fill {
            color: white;
            transition: color 0.3s ease;
        }
        .btn.btn-unpin .bi.bi-pin-angle-fill:hover {
            color: red;
        }
    </style>
</head>
<body class="dark-theme">
    <main>
        <div class="container mt-3 pt-4 pb-1 mb-3" id="content" style="border-radius: 20px;">
            <div class="d-flex align-items-center justify-content-center mb-4">
                <img src="temp/logo.svg" alt="Logo"
                    style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px;" class="shadow">
                <h1 class="m-0 pb-3">Enigma | Файловый Менеджер</h1>
            </div>
            <div class="row mb-1">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h3>Закрепленные Папки (<?= count($pinnedFolders); ?>)</h3>
                </div>
                <hr class="w-100 mt-3 mb-3">
                <div class="col-12">
                    <div class="row" id="pinnedFoldersContainer"
                        data-pinned-folders='<?php echo json_encode($pinnedFolders); ?>'>
                        <?php
                        foreach ($pinnedFolders as $folder): ?>
                            <?php
                            $folderPinnedOrRecentPath = __DIR__ . "/" . $folder;
                            $lastModified = getFolderLastModifiedTime($folderPinnedOrRecentPath);
                            ?>
                            <div class="col-md-3 mb-3 folder-item">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title m-0">
                                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    data-bs-title="<?= $folder ?>"><?= $folder ?></p>
                                            </h5>
                                            <div class="d-flex">
                                                <form method="POST" action="">
                                                    <button class="btn btn-unpin" type="submit" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" data-bs-title="Открепить папку?">
                                                        <i class="bi bi-pin-angle-fill" style=""></i>
                                                    </button>
                                                    <input type="hidden" name="unpin_folder" value="<?= $folder ?>">
                                                </form>
                                                <form method="POST" action="">
                                                    <button class="btn btn-link" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" data-bs-title="Перейти в папку?">
                                                        <i class="bi bi-eye-fill" style="color: white;"></i>
                                                    </button>
                                                    <input type="hidden" name="open" value="<?= $folder ?>">
                                                </form>
                                                <form method="POST" action="">
                                                    <button class="btn btn-link" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" data-bs-title="Скачать папку?">
                                                        <i class="bi bi-file-earmark-zip" style="color: white;"></i>
                                                    </button>
                                                    <input type="hidden" name="download_folder" value="<?= $folder ?>">
                                                </form>
                                            </div>
                                        </div>
                                        <hr class="text-white">
                                        <p class="text-white text-center text-last-mod">Последнее изменение:
                                            <?= $lastModified ?>
                                        </p>
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
                    <hr class="w-100">
                    <div class="row">
                        <?php foreach ($recentFolders as $folder): ?>
                            <?php
                            $folderPinnedOrRecentPath = __DIR__ . "/" . $folder;
                            $lastModified = getFolderLastModifiedTime($folderPinnedOrRecentPath);
                            ?>
                            <div class="col-md-3 mb-3 folder-item">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title m-0" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                data-bs-title="<?= $folder ?>">
                                                <?= $folder ?>
                                            </h5>
                                            <div class="d-flex">
                                                <form method="POST" action="">
                                                    <button class="btn" type="submit" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" data-bs-title="Закрепить папку?">
                                                        <i class="bi bi-pin-angle" style="color: white;"></i>
                                                    </button>
                                                    <input type="hidden" name="pin_folder" value="<?= $folder ?>">
                                                </form>
                                                <form method="POST" action="">
                                                    <button class="btn btn-link" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" data-bs-title="Перейти в папку?">
                                                        <i class="bi bi-eye-fill" style="color: white;"></i>
                                                    </button>
                                                    <input type="hidden" name="open" value="<?= $folder ?>">
                                                </form>
                                                <form method="POST" action="">
                                                    <button class="btn btn-link" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" data-bs-title="Скачать папку?">
                                                        <i class="bi bi-file-earmark-zip" style="color: white;"></i>
                                                    </button>
                                                    <input type="hidden" name="download_folder" value="<?= $folder ?>">
                                                </form>
                                            </div>
                                        </div>
                                        <hr class="text-white">
                                        <p class="text-white text-center text-last-mod">Последнее изменение:
                                            <?= $lastModified ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h3>Все Папки (<?= count($folders); ?>)</h3>
                <div class="d-flex align-items-center">
                    <div class="mb-4" style="flex-grow: 1; max-width: 350px;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Поиск папок..."
                            onkeyup="searchFolders()"
                            style="color: black!important; background-color:white!important;">
                    </div>
                    <?php if (count($folders) > 6): ?>
                        <button id="showAllFolders" class="btn btn-outline w-25 mb-3 border-0" style="z-index: 1000;">
                            <i class="bi bi-arrow-down-square"></i>
                        </button>
                        <button id="hideFolders" class="btn btn-outline w-25 mb-3 border-0"
                            style="z-index: 1000; display: none;">
                            <i class="bi bi-arrow-up-square"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div id="searchResults" class="row" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="row" id="foldersContainer" data-folders='<?php echo json_encode($folders); ?>'>
                    </div>
                </div>
            </div>
    </main>
</body>
</html>
