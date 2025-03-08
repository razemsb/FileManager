<?php 
$directory = ".";
$folders = array_filter(scandir($directory), function($folder) use ($directory) {
    return $folder !== "." && $folder !== ".." && is_dir($folder);
});

$pinnedFile = __DIR__ . '/temp/pinned_folders.txt';
$recentFile = __DIR__ . '/temp/recent_folders.txt';

if (!file_exists(__DIR__ . '/temp')) {
    mkdir(__DIR__ . '/temp', 0777, true);
}

foreach ([$pinnedFile, $recentFile] as $file) {
    if (!file_exists($file)) {
        touch($file);
        chmod($file, 0666);
    }
}

function readFromFile($file){
    if (!file_exists($file)) {
        touch($file);
        return [];
    }
    $content = file_get_contents($file);
    return $content ? array_filter(explode(PHP_EOL, $content)) : [];
}

function writeToFile($file, $data){
    $data = array_filter($data);
    $data = array_unique($data);
    file_put_contents($file, implode(PHP_EOL, $data));
}

$pinnedFolders = readFromFile($pinnedFile);
$recentFolders = readFromFile($recentFile);

if (isset($_POST['pinned'])) {
    $folderToPin = $_POST['pinned'];
    if (!in_array($folderToPin, $pinnedFolders)) {
        array_unshift($pinnedFolders, $folderToPin);
        if (count($pinnedFolders) > 30) {
            array_pop($pinnedFolders);
        }
        writeToFile($pinnedFile, array_unique($pinnedFolders));
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_POST['unpin_folder'])) {
    $folderToUnpin = $_POST['unpin_folder'];
    if (in_array($folderToUnpin, $pinnedFolders)) {
        $pinnedFolders = array_values(array_diff($pinnedFolders, [$folderToUnpin]));
        writeToFile($pinnedFile, $pinnedFolders);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_POST['open'])) {
    $folderToVisit = $_POST['open'];
    if (!in_array($folderToVisit, $recentFolders)) {
        array_unshift($recentFolders, $folderToVisit);
        if (count($recentFolders) > 30) {
            array_pop($recentFolders);
        }
        writeToFile($recentFile, $recentFolders);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open'])) {
    $folderToOpen = $_POST['open'];
    header('Location: ' . $folderToOpen);
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_download_folder'])) {
    $folderToDownload = realpath($_POST['ajax_download_folder']);
    if (!$folderToDownload || !is_dir($folderToDownload)) {
        echo json_encode(['status' => 'error', 'message' => 'Указанная папка не существует или недоступна.']);
        exit;
    }
    $archiveName = basename($folderToDownload) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($archiveName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка создания архива.']);
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
        echo json_encode(['status' => 'error', 'message' => 'Ошибка создания архива.']);
        exit;
    }
    
    echo json_encode([
        'status' => 'success', 
        'file' => $archiveName,
        'filename' => basename($archiveName)
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="temp/logo.svg"> 
    <link rel="stylesheet" href="temp/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="temp/script.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <title>File Manager</title>
    <style>
        .badge {
            margin-left: 8px;
            font-size: 12px;
            padding: 4px 8px;
            background-color: rgba(0, 123, 255, 0.2) !important;
            color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s ease;
        }
        .folder-filter.active .badge {
            background-color: #007bff !important;
            color: white !important;
        }
        .folder-filter {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .folder-filter i {
            margin-right: 8px;
        }
        .folder-card[data-pinned="true"] .bi-pin-angle-fill {
            color: #007bff;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .folder-card[data-pinned="true"] .bi-pin-angle-fill:hover {
            transform: rotate(45deg);
        }
        .no-results {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            width: 100%;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }
        .no-results-content {
            padding: 20px;
        }
        .no-results i {
            color: rgba(0, 0, 0, 0.5);
        }
        .no-results h3 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .no-results p {
            margin: 0;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="overlay" id="downloadOverlay">
        <div class="spinner-container">
            <div class="spinner-border spinner text-light" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <div class="spinner-text">Архивирование и скачивание папки...</div>
        </div>
    </div>
    <nav class="nav-bar">
        <div class="company-name fs-2">EnigmaDevix | FileManager</div>
        <div class="search-container">
            <input type="text" class="search-input" id="folderSearch" placeholder="Поиск папок...">
            <i class="bi bi-search search-icon"></i>
        </div>
    </nav>
    <div class="sidebar">
        <button data-type="pinned" class="folder-filter">
            <i class="bi bi-pin-angle"></i> Закрепленные
            <span class="badge bg-primary rounded-pill"><?= count($pinnedFolders) ?></span>
        </button>
        <button data-type="recent" class="folder-filter">
            <i class="bi bi-clock-history"></i> Недавние
            <span class="badge bg-primary rounded-pill"><?= count($recentFolders) ?></span>
        </button>
        <button data-type="all" class="folder-filter active">
            <i class="bi bi-folder"></i> Все папки
            <span class="badge bg-primary rounded-pill"><?= count($folders) ?></span>
        </button>
    </div>
    <div class="content" style="margin-top: 60px;">
        <div class="grid-container">    
            <?php if (count($folders) === 0): ?>
                <div class="no-results">
                    <div class="no-results-content">
                        <i class="bi bi-folder-x" style="font-size: 3rem; margin-bottom: 15px; color: black;"></i>
                        <h3>Папки не найдены</h3>
                        <p>В текущей директории нет доступных папок</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($folders as $folder) : ?>
                    <?php 
                        $lastModified = date("d.m.Y H:i", filemtime($folder));
                        $folderName = htmlspecialchars(strlen($folder) > 10 ? substr($folder, 0, 10) . "..." : $folder);
                        $isPinned = in_array($folder, $pinnedFolders);
                        $isRecent = in_array($folder, $recentFolders);
                    ?>
                    <div class='folder-card' 
                         data-pinned="<?= $isPinned ? 'true' : 'false' ?>"
                         data-recent="<?= $isRecent ? 'true' : 'false' ?>">
                        <div class='folder-header'>
                            <span class='folder-title' title="<?= $folder ?>"><?= $folderName ?></span>
                            <div class='folder-actions d-flex'>
                                <form method="POST" style="margin: 0; padding: 0;">
                                    <i class='bi bi-pin-angle<?= $isPinned ? '-fill' : '' ?>' onclick="this.parentElement.submit();" style="cursor: pointer;"></i>
                                    <?php if ($isPinned): ?>
                                        <input type="hidden" name="unpin_folder" value="<?= htmlspecialchars($folder) ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="pinned" value="<?= htmlspecialchars($folder) ?>">
                                    <?php endif; ?>
                                </form>
                                <form method="POST" style="margin: 0; padding: 0;">
                                    <i class="bi bi-eye" onclick="this.parentElement.submit();" style="cursor: pointer;"></i>
                                    <input type="hidden" name="open" value="<?= htmlspecialchars($folder) ?>">
                                </form>
                                <form method="POST" id="downloadForm-<?= htmlspecialchars($folder) ?>" style="margin: 0; padding: 0;">
                                    <i class='bi bi-download' onclick="showDownloadSpinner('<?= htmlspecialchars($folder) ?>')" style="cursor: pointer;"></i>
                                    <input type="hidden" name="download_folder" value="<?= htmlspecialchars($folder) ?>">
                                </form>
                            </div>
                        </div>
                        <div class='folder-footer'>Изменено: <?= $lastModified ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div id="noResultsMessage" class="no-results" style="display: none;">
                <div class="no-results-content">
                    <i class="bi bi-search" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h3>Ничего не найдено</h3>
                    <p>Попробуйте изменить параметры поиска</p>
                </div>
            </div>
        </div>  
    </div>
</body>
</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_temp_file'])) {
    $tempFile = $_POST['delete_temp_file'];
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    exit;
}
?>