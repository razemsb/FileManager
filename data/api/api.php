<?php
header('Content-Type: application/json');
define('BASE_DIR', __DIR__ . '/../..');
define('DATA_DIR', BASE_DIR . '/data/files');

function getFoldersList()
{
    $excludedFolders = ['data', '.git', '.vscode'];

    $folders = array_filter(scandir(BASE_DIR), function ($item) use ($excludedFolders) {
        return $item !== '.' && $item !== '..' && !in_array($item, $excludedFolders) && $item && is_dir(BASE_DIR . '/' . $item);
    });
    return array_values($folders);
}

function getPinnedFolders()
{
    $file = DATA_DIR . '/pinned_folders.txt';
    if (!file_exists($file))
        return [];
    return array_filter(explode("\n", file_get_contents($file)));
}

function getRecentFolders()
{
    $file = DATA_DIR . '/recent_folders.txt';
    if (!file_exists($file))
        return [];
    return array_filter(explode("\n", file_get_contents($file)));
}

function savePinnedFolders($folders)
{
    $file = DATA_DIR . '/pinned_folders.txt';
    file_put_contents($file, implode("\n", $folders));
}

function saveRecentFolders($folders)
{
    $file = DATA_DIR . '/recent_folders.txt';
    file_put_contents($file, implode("\n", $folders));
}

function downloadFolder($folderName)
{

    if (!extension_loaded('zip')) {
        throw new Exception("ZipArchive error");
    }

    if (empty($folderName)) {
        throw new Exception("Имя папки не указано");
    }

    $basePath = realpath(BASE_DIR);
    $folderPath = realpath($basePath . DIRECTORY_SEPARATOR . $folderName);

    if ($folderPath === false || strpos($folderPath, $basePath) !== 0) {
        throw new Exception("Недопустимый путь к папке");
    }

    if (!is_dir($folderPath)) {
        throw new Exception("Папка '$folderName' не существует или недоступна");
    }

    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    if ($tempZip === false) {
        throw new Exception("Ошибка создания временного файла");
    }

    $zip = new ZipArchive();
    if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        unlink($tempZip);
        throw new Exception("Не удалось создать ZIP-архив");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($folderPath) + 1);

        if ($file->isDir()) {
            $zip->addEmptyDir($folderName . '/' . $relativePath);
        } else {
            $zip->addFile($filePath, $folderName . '/' . $relativePath);
        }
    }

    if (!$zip->close()) {
        unlink($tempZip);
        throw new Exception("Ошибка при закрытии архива");
    }

    if (!file_exists($tempZip) || filesize($tempZip) === 0) {
        unlink($tempZip);
        throw new Exception("Создан пустой архив");
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $folderName . '.zip"');
    header('Content-Length: ' . filesize($tempZip));
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    while (ob_get_level()) {
        ob_end_clean();
    }

    readfile($tempZip);
    unlink($tempZip);
    exit;
}
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        if ($action === 'getFolders') {
            $folders = getFoldersList();
            $pinned = getPinnedFolders();
            $recent = getRecentFolders();

            $result = array_map(function ($folder) use ($pinned, $recent) {
                return [
                    'name' => $folder,
                    'modified' => filemtime(BASE_DIR . '/' . $folder),
                    'isPinned' => in_array($folder, $pinned),
                    'isRecent' => in_array($folder, $recent)
                ];
            }, $folders);

            echo json_encode(['success' => true, 'data' => $result]);
            exit;
        }

        if ($action === 'download') {
            $folder = $_GET['folder'] ?? '';
            downloadFolder($folder);
            exit;
        }

    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'togglePin') {
            $folder = $input['folder'] ?? '';
            $pinned = $input['pinned'] ?? false;

            $pinnedFolders = getPinnedFolders();

            if ($pinned) {
                $pinnedFolders = array_diff($pinnedFolders, [$folder]);
            } else {
                array_unshift($pinnedFolders, $folder);
                $pinnedFolders = array_slice($pinnedFolders, 0, 30);
            }

            savePinnedFolders($pinnedFolders);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'addRecent') {
            $folder = $input['folder'] ?? '';
            $recentFolders = getRecentFolders();

            if (!in_array($folder, $recentFolders)) {
                array_unshift($recentFolders, $folder);
                $recentFolders = array_slice($recentFolders, 0, 30);
                saveRecentFolders($recentFolders);
            }

            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'deleteFolder') {
            $folder = $input['folder'] ?? '';

            if (empty($folder)) {
                throw new Exception("Имя папки не указано");
            }

            $basePath = realpath(BASE_DIR);
            $folderPath = realpath($basePath . DIRECTORY_SEPARATOR . $folder);

            // Проверка безопасности
            if ($folderPath === false || strpos($folderPath, $basePath) !== 0) {
                throw new Exception("Недопустимый путь к папке");
            }

            if (!is_dir($folderPath)) {
                throw new Exception("Папка '$folder' не существует");
            }

            function deleteDirectory($dir)
            {
                if (!file_exists($dir))
                    return true;

                if (!is_dir($dir)) {
                    return unlink($dir);
                }

                foreach (scandir($dir) as $item) {
                    if ($item == '.' || $item == '..')
                        continue;

                    if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                        return false;
                    }
                }

                return rmdir($dir);
            }

            if (deleteDirectory($folderPath)) {
                $pinnedFolders = getPinnedFolders();
                $pinnedFolders = array_diff($pinnedFolders, [$folder]);
                savePinnedFolders($pinnedFolders);
                $recentFolders = getRecentFolders();
                $recentFolders = array_diff($recentFolders, [$folder]);
                saveRecentFolders($recentFolders);
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Не удалось удалить папку");
            }
            exit;
        }

        throw new Exception('Неизвестный запрос');
    }

    throw new Exception('Неизвестный запрос');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}