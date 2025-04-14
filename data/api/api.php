<?php
header('Content-Type: application/json');
define('BASE_DIR', __DIR__ . '/../..');
define('DATA_DIR', BASE_DIR . '/data/files');

function getFoldersList() {
    $folders = array_filter(scandir(BASE_DIR), function($item) {
        return $item !== '.' && $item !== '..' && is_dir(BASE_DIR . '/' . $item);
    });
    return array_values($folders);
}

function getPinnedFolders() {
    $file = DATA_DIR . '/pinned_folders.txt';
    if (!file_exists($file)) return [];
    return array_filter(explode("\n", file_get_contents($file)));
}

function getRecentFolders() {
    $file = DATA_DIR . '/recent_folders.txt';
    if (!file_exists($file)) return [];
    return array_filter(explode("\n", file_get_contents($file)));
}

function savePinnedFolders($folders) {
    $file = DATA_DIR . '/pinned_folders.txt';
    file_put_contents($file, implode("\n", $folders));
}

function saveRecentFolders($folders) {
    $file = DATA_DIR . '/recent_folders.txt';
    file_put_contents($file, implode("\n", $folders));
}

function downloadFolder($folderName) {
    $folderPath = realpath(BASE_DIR . DIRECTORY_SEPARATOR . $folderName);
    
    if (!is_dir($folderPath)) {
        throw new Exception("Папка не существует");
    }

    $zip = new ZipArchive();
    $zipFilename = DATA_DIR . DIRECTORY_SEPARATOR . $folderName . '.zip';
    
    if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception("Не удалось создать архив");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = ltrim(
                str_replace($folderPath, '', $filePath),
                DIRECTORY_SEPARATOR
            );
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $zip->addFile($filePath, $folderName . '/' . $relativePath);
        }
    }

    $zip->close();

    if (!file_exists($zipFilename)) {
        throw new Exception("Ошибка создания архива");
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zipFilename) . '"');
    header('Content-Length: ' . filesize($zipFilename));
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    readfile($zipFilename);
    
    unlink($zipFilename);
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

            $result = array_map(function($folder) use ($pinned, $recent) {
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
    }

    throw new Exception('Неизвестный запрос');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}