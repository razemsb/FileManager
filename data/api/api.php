<?php
header('Content-Type: application/json; charset=utf-8');

class FileManagerAPI {
    private $baseDir;
    private $dataDir;
    private $foldersFile;
    private $categoriesFile;
    
    public function __construct() {
        $this->baseDir = realpath(__DIR__ . '/../..');
        $this->dataDir = $this->baseDir . '/data/memory';
        $this->foldersFile = $this->dataDir . '/folders.json';
        $this->categoriesFile = $this->dataDir . '/categories.json';
        
        $this->ensureDirectoryExists();
    }
    
    private function ensureDirectoryExists() {
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
     
    private function loadFoldersData() {
        if (!file_exists($this->foldersFile)) {
            return ['pinned' => [], 'recent' => []];
        }
        
        $json = file_get_contents($this->foldersFile);
        $data = json_decode($json, true);
        
        return [
            'pinned' => $data['pinned'] ?? [],
            'recent' => $data['recent'] ?? []
        ];
    }
    
    private function saveFoldersData($data) {
        file_put_contents($this->foldersFile, 
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function loadCategories() {
        if (!file_exists($this->categoriesFile)) {
            return [];
        }
        
        $json = file_get_contents($this->categoriesFile);
        $data = json_decode($json, true);
        
        return is_array($data) ? $data : [];
    }
    
    private function saveCategories($data) {
        $tempFile = $this->categoriesFile . '.tmp';
        file_put_contents($tempFile, 
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tempFile, $this->categoriesFile);
    }
    
    private function getFoldersList() {
        $excludedFolders = ['data', '.git', '.vscode'];
        
        $folders = array_filter(scandir($this->baseDir), function ($item) use ($excludedFolders) {
            $fullPath = $this->baseDir . '/' . $item;
            return $item !== '.' && 
                   $item !== '..' && 
                   !in_array($item, $excludedFolders) && 
                   $item && 
                   is_dir($fullPath);
        });
        
        return array_values($folders);
    }
    
    private function validateFolderPath($folderName) {
        if (empty($folderName)) {
            throw new Exception("Имя папки не указано");
        }
        
        $folderPath = realpath($this->baseDir . DIRECTORY_SEPARATOR . $folderName);
        
        if ($folderPath === false || strpos($folderPath, $this->baseDir) !== 0) {
            throw new Exception("Недопустимый путь к папке");
        }
        
        return $folderPath;
    }
    
    private function sendResponse($success, $data = []) {
        echo json_encode(array_merge(['success' => $success], $data));
        exit;
    }
      
    public function getFolders() {
        $folders = $this->getFoldersList();
        $foldersData = $this->loadFoldersData();
        $pinned = $foldersData['pinned'] ?? [];
        $recent = $foldersData['recent'] ?? [];
        
        $result = array_map(function ($folder) use ($pinned, $recent) {
            return [
                'name' => $folder,
                'modified' => filemtime($this->baseDir . '/' . $folder),
                'isPinned' => in_array($folder, $pinned),
                'isRecent' => in_array($folder, $recent)
            ];
        }, $folders);
        
        $this->sendResponse(true, ['data' => $result]);
    }
    
    public function downloadFolder($folderName) {
        if (!extension_loaded('zip')) {
            throw new Exception("ZipArchive не доступен");
        }
        
        $folderPath = $this->validateFolderPath($folderName);
        
        if (!is_dir($folderPath)) {
            throw new Exception("Папка '$folderName' не существует");
        }
        
        $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
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
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $folderName . '.zip"');
        header('Content-Length: ' . filesize($tempZip));
        readfile($tempZip);
        unlink($tempZip);
        exit;
    }

    public function createFolder($folderName) {
        $folderName = trim($folderName);
        
        if (empty($folderName)) {
            throw new Exception("Имя папки не может быть пустым");
        }
        
        if (mb_strlen($folderName) > 255) {
            throw new Exception("Имя папки слишком длинное");
        }
        
        $folderName = preg_replace('/[\/\\\\:*?"<>|]/u', '-', $folderName);
        $folderName = trim($folderName, '. ');
        
        if (empty($folderName)) {
            throw new Exception("Недопустимое имя папки");
        }
        
        $folderPath = $this->baseDir . DIRECTORY_SEPARATOR . $folderName;
        
        if (file_exists($folderPath)) {
            throw new Exception("Папка '$folderName' уже существует");
        }
        
        $excludedFolders = ['data', '.git', '.vscode'];
        if (in_array($folderName, $excludedFolders)) {
            throw new Exception("Недопустимое имя папки");
        }
        
        if (!mkdir($folderPath, 0755, true)) {
            throw new Exception("Не удалось создать папку '$folderName'");
        }
        
        $this->sendResponse(true, [
            'message' => 'Папка успешно создана',
            'folder' => $folderName
        ]);
    }
    
    public function createTestFolders($count, $prefix) {
        $count = min(max(1, (int)$count), 50);
        $createdFolders = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $folderName = $prefix . '_' . uniqid() . '_' . $i;
            $folderPath = $this->baseDir . '/' . $folderName;
            
            if (!file_exists($folderPath) && mkdir($folderPath, 0755, true)) {
                $createdFolders[] = $folderName;
                
                $fileCount = rand(1, 3);
                for ($j = 1; $j <= $fileCount; $j++) {
                    $fileName = $folderPath . '/test_file_' . $j . '.txt';
                    file_put_contents($fileName, "Тестовое содержимое файла $j\nСоздано: " . date('Y-m-d H:i:s'));
                }
            }
        }
        
        $this->sendResponse(true, [
            'message' => 'Создано папок: ' . count($createdFolders),
            'folders' => $createdFolders
        ]);
    }
    
    public function togglePin($folder, $pinned) {
        $data = $this->loadFoldersData();
        $pinnedFolders = $data['pinned'] ?? [];
        
        if ($pinned) {
            $pinnedFolders = array_diff($pinnedFolders, [$folder]);
        } else {
            array_unshift($pinnedFolders, $folder);
            $pinnedFolders = array_slice($pinnedFolders, 0, 30);
        }
        
        $data['pinned'] = array_values($pinnedFolders);
        $this->saveFoldersData($data);
        
        $this->sendResponse(true);
    }
    
    public function addRecent($folder) {
        $data = $this->loadFoldersData();
        $recentFolders = $data['recent'] ?? [];
        
        if (!in_array($folder, $recentFolders)) {
            array_unshift($recentFolders, $folder);
            $recentFolders = array_slice($recentFolders, 0, 30);
            $data['recent'] = array_values($recentFolders);
            $this->saveFoldersData($data);
        }
        
        $this->sendResponse(true);
    }
    
    public function deleteFolder($folder) {
        $folderPath = $this->validateFolderPath($folder);
        
        if (!is_dir($folderPath)) {
            throw new Exception("Папка '$folder' не существует");
        }
        
        $this->deleteDirectory($folderPath);
        
        $data = $this->loadFoldersData();
        $data['pinned'] = array_values(array_diff($data['pinned'] ?? [], [$folder]));
        $data['recent'] = array_values(array_diff($data['recent'] ?? [], [$folder]));
        $this->saveFoldersData($data);
        
        $this->sendResponse(true);
    }
    
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    
    public function getCategories() {
        $categories = $this->loadCategories();
        $this->sendResponse(true, ['data' => $categories]);
    }
    
    public function createCategory($name) {
        $name = trim($name);
        if (empty($name)) {
            throw new Exception("Имя категории не может быть пустым");
        }
        
        if (mb_strlen($name) > 150) {
            throw new Exception("Имя категории слишком длинное");
        }
        
        $name = preg_replace('/[\/\\\\]/u', '-', $name);
        $categories = $this->loadCategories();
        
        $newCategory = [
            'id' => 'cat_' . uniqid(),
            'name' => $name,
            'folders' => []
        ];
        
        $categories[] = $newCategory;
        $this->saveCategories($categories);
        
        $this->sendResponse(true, ['category' => $newCategory]);
    }
    
    public function deleteCategory($categoryId) {
        $categories = $this->loadCategories();
        $found = false;
        
        foreach ($categories as $key => $category) {
            if (($category['id'] ?? '') === $categoryId) {
                unset($categories[$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Категория не найдена");
        }
        
        $this->saveCategories(array_values($categories));
        $this->sendResponse(true);
    }
    
    public function renameCategory($categoryId, $newName) {
        $newName = trim($newName);
        if (empty($newName)) {
            throw new Exception("Имя категории не может быть пустым");
        }
        
        $newName = preg_replace('/[\/\\\\]/u', '-', $newName);
        $categories = $this->loadCategories();
        $found = false;
        
        foreach ($categories as $key => $category) {
            if (($category['id'] ?? '') === $categoryId) {
                $categories[$key]['name'] = $newName;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Категория не найдена");
        }
        
        $this->saveCategories($categories);
        $this->sendResponse(true);
    }
    
    public function addFolderToCategory($categoryId, $folderName) {
        $folderName = trim($folderName);
        if (empty($folderName)) {
            throw new Exception("Имя папки не может быть пустым");
        }
        
        $categories = $this->loadCategories();
        $found = false;
        
        foreach ($categories as $key => $category) {
            if (($category['id'] ?? '') === $categoryId) {
                if (!in_array($folderName, $categories[$key]['folders'])) {
                    $categories[$key]['folders'][] = $folderName;
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Категория не найдена");
        }
        
        $this->saveCategories($categories);
        $this->sendResponse(true);
    }
    
    public function removeFolderFromCategory($categoryId, $folderName) {
        $folderName = trim($folderName);
        $categories = $this->loadCategories();
        $found = false;
        
        foreach ($categories as $key => $category) {
            if (($category['id'] ?? '') === $categoryId) {
                $categories[$key]['folders'] = array_values(array_filter(
                    $categories[$key]['folders'],
                    function ($f) use ($folderName) {
                        return $f !== $folderName;
                    }
                ));
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Категория не найдена");
        }
        
        $this->saveCategories($categories);
        $this->sendResponse(true);
    }
    
    // запросы

    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $action = $_GET['action'] ?? '';
            
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $action = $input['action'] ?? $action;
            }
            
            if (empty($action)) {
                throw new Exception("Не указано действие (action)");
            }
            
            if ($method === 'GET') {
                switch ($action) {
                    case 'getFolders':
                        $this->getFolders();
                        break;
                    case 'getCategories':
                        $this->getCategories();
                        break;
                    case 'download':
                        $folder = $_GET['folder'] ?? '';
                        $this->downloadFolder($folder);
                        break;
                    default:
                        throw new Exception("Неизвестное действие: $action");
                }
            }
            
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                
                switch ($action) {
                    case 'createFolder':
                        $folderName = $input['folderName'] ?? '';
                        $this->createFolder($folderName);
                        break;
                    case 'createFolders':
                        $count = $input['count'] ?? 5;
                        $prefix = $input['prefix'] ?? 'test_folder';
                        $this->createTestFolders($count, $prefix);
                        break;
                    case 'togglePin':
                        $folder = $input['folder'] ?? '';
                        $pinned = $input['pinned'] ?? false;
                        $this->togglePin($folder, $pinned);
                        break;
                    case 'addRecent':
                        $folder = $input['folder'] ?? '';
                        $this->addRecent($folder);
                        break;
                    case 'deleteFolder':
                        $folder = $input['folder'] ?? '';
                        $this->deleteFolder($folder);
                        break;
                    case 'createCategory':
                        $name = $input['name'] ?? '';
                        $this->createCategory($name);
                        break;
                    case 'deleteCategory':
                        $categoryId = $input['categoryId'] ?? '';
                        $this->deleteCategory($categoryId);
                        break;
                    case 'renameCategory':
                        $categoryId = $input['categoryId'] ?? '';
                        $newName = $input['newName'] ?? '';
                        $this->renameCategory($categoryId, $newName);
                        break;
                    case 'addFolder':
                        $categoryId = $input['categoryId'] ?? '';
                        $folderName = $input['folderName'] ?? '';
                        $this->addFolderToCategory($categoryId, $folderName);
                        break;
                    case 'removeFolder':
                        $categoryId = $input['categoryId'] ?? '';
                        $folderName = $input['folderName'] ?? '';
                        $this->removeFolderFromCategory($categoryId, $folderName);
                        break;
                    default:
                        throw new Exception("Неизвестное действие: $action");
                }
            }
            
            throw new Exception("Метод не поддерживается");
            
        } catch (Exception $e) {
            http_response_code(400);
            $this->sendResponse(false, ['error' => $e->getMessage()]);
        }
    }
}

$api = new FileManagerAPI();
$api->handleRequest();