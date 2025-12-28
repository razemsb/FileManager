<?php
header('Content-Type: application/json; charset=utf-8');

class FolderService
{
    private $baseDir;
    private $dataDir;
    private $foldersFile;
    private $categoriesFile;

    public function __construct()
    {
        $this->baseDir = realpath(__DIR__ . '/../..');
        $this->dataDir = $this->baseDir . '/data/memory';
        $this->foldersFile = $this->dataDir . '/folders.json';
        $this->categoriesFile = $this->dataDir . '/categories.json';

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    public function createMultipleFolders($prefix, $count)
    {
        $created = [];
        $basePath = __DIR__ . '/../../';

        for ($i = 1; $i <= $count; $i++) {
            $folderName = $prefix . '_' . $i;
            $fullPath = $basePath . $folderName;

            if (!is_dir($fullPath)) {
                if (mkdir($fullPath, 0777, true)) {
                    $created[] = $folderName;
                }
            }
        }

        return [
            'success' => true,
            'created' => $created,
            'message' => "Создано папок: " . count($created)
        ];
    }

    public function addToRecent($name)
    {
        $meta = $this->loadJson($this->foldersFile, ['pinned' => [], 'recent' => []]);

        $recent = array_diff($meta['recent'], [$name]);
        array_unshift($recent, $name);

        $meta['recent'] = array_values(array_slice($recent, 0, 250));

        file_put_contents($this->foldersFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function togglePin($name)
    {
        $meta = $this->loadJson($this->foldersFile, ['pinned' => [], 'recent' => []]);

        if (in_array($name, $meta['pinned'])) {
            $meta['pinned'] = array_values(array_diff($meta['pinned'], [$name]));
        } else {
            array_unshift($meta['pinned'], $name);
            $meta['pinned'] = array_slice($meta['pinned'], 0, 100);
        }

        file_put_contents($this->foldersFile, json_encode($meta, JSON_UNESCAPED_UNICODE));
        return ['is_pinned' => in_array($name, $meta['pinned'])];
    }

    public function getFolders()
    {
        $excluded = ['data', '.git', '.vscode'];
        $meta = $this->loadJson($this->foldersFile, ['pinned' => [], 'recent' => []]);

        $items = array_filter(scandir($this->baseDir), function ($item) use ($excluded) {
            return !in_array($item, $excluded) && $item[0] !== '.' && is_dir($this->baseDir . '/' . $item);
        });

        return array_values(array_map(function ($name) use ($meta) {
            $path = $this->baseDir . '/' . $name;
            return [
                'name' => $name,
                'modified_at' => filemtime($path),
                'is_pinned' => in_array($name, $meta['pinned']),
                'is_recent' => in_array($name, $meta['recent'])
            ];
        }, $items));
    }

    public function createFolder($name)
    {
        $name = $this->sanitizeName($name);
        $path = $this->baseDir . DIRECTORY_SEPARATOR . $name;

        if (file_exists($path)) throw new Exception("Folder exists", 409);
        if (!mkdir($path, 0755, true)) throw new Exception("Creation failed", 500);

        return ['name' => $name];
    }

    public function deleteFolder($name)
    {
        $path = $this->validatePath($name);
        $this->recursiveDelete($path);

        $meta = $this->loadJson($this->foldersFile, ['pinned' => [], 'recent' => []]);
        $meta['pinned'] = array_values(array_diff($meta['pinned'], [$name]));
        $meta['recent'] = array_values(array_diff($meta['recent'], [$name]));
        file_put_contents($this->foldersFile, json_encode($meta));
    }

    public function getCategories()
    {
        return $this->loadJson($this->categoriesFile, []);
    }

    public function createCategory($name)
    {
        $name = trim($name);
        if (empty($name)) {
            throw new Exception("Имя категории не может быть пустым", 400);
        }

        $dir = dirname($this->categoriesFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $categories = [];
        if (file_exists($this->categoriesFile)) {
            $content = file_get_contents($this->categoriesFile);
            $categories = json_decode($content, true);
            if (!is_array($categories)) {
                $categories = [];
            }
        }

        $newCategory = [
            'id' => uniqid('cat_'),
            'name' => htmlspecialchars($name),
            'folders' => []
        ];

        $categories[] = $newCategory;

        $jsonResult = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($jsonResult === false) {
            throw new Exception("Ошибка кодирования JSON: " . json_last_error_msg(), 500);
        }

        $bytes = file_put_contents($this->categoriesFile, $jsonResult);

        if ($bytes === false) {
            throw new Exception("Не удалось записать файл. Проверьте права на папку: " . $dir, 500);
        }

        return $newCategory;
    }

    public function updateCategory($id, $data)
    {
        $categories = $this->getCategories();
        foreach ($categories as &$cat) {
            if ($cat['id'] === $id) {
                if (isset($data['name'])) $cat['name'] = $this->sanitizeName($data['name']);
                if (isset($data['folders'])) $cat['folders'] = array_values(array_unique($data['folders']));
                file_put_contents($this->categoriesFile, json_encode($categories));
                return $cat;
            }
        }
        throw new Exception("Not found", 404);
    }

    public function downloadZip($name)
    {
        $path = $this->validatePath($name);
        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();

        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new Exception("Zip failed", 500);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $fPath = $file->getRealPath();
            $rel = substr($fPath, strlen($path) + 1);
            $file->isDir() ? $zip->addEmptyDir($name . '/' . $rel) : $zip->addFile($fPath, $name . '/' . $rel);
        }
        $zip->close();
        return $tmp;
    }

    private function validatePath($name)
    {
        $path = realpath($this->baseDir . DIRECTORY_SEPARATOR . $name);
        if (!$path || strpos($path, $this->baseDir) !== 0 || !is_dir($path)) throw new Exception("Invalid path", 403);
        return $path;
    }

    private function sanitizeName($name)
    {
        $name = preg_replace('/[\/\\\\:*?"<>|]/u', '-', trim($name));
        if (empty($name)) throw new Exception("Invalid name", 400);
        return $name;
    }

    private function loadJson($file, $default)
    {
        return file_exists($file) ? json_decode(file_get_contents($file), true) : $default;
    }

    private function recursiveDelete($dir)
    {
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        return rmdir($dir);
    }
}

class CategoryService
{
    private $categoriesFile;

    public function __construct($dataDir)
    {
        $this->categoriesFile = $dataDir . '/categories.json';
    }

    public function getCategories()
    {
        return $this->loadJson([]);
    }

    public function createCategory($name)
    {
        if (empty($name)) throw new Exception("Name required", 400);

        $categories = $this->loadJson([]);
        $newId = count($categories) > 0 ? max(array_column($categories, 'id')) + 1 : 1;

        $newCategory = [
            'id' => $newId,
            'name' => htmlspecialchars($name),
            'folders' => []
        ];

        $categories[] = $newCategory;
        $this->saveJson($categories);
        return $newCategory;
    }

    public function addFolderToCategory($catId, $folderName)
    {
        $categories = $this->loadJson([]);
        $found = false;

        foreach ($categories as &$cat) {
            if ($cat['id'] == $catId) {
                if (!in_array($folderName, $cat['folders'])) {
                    $cat['folders'][] = $folderName;
                }
                $found = true;
                break;
            }
        }

        if (!$found) throw new Exception("Category not found", 404);

        $this->saveJson($categories);
        return ['success' => true];
    }

    public function removeFolderFromCategory($catId, $folderName)
    {
        $categories = $this->loadJson([]);
        $found = false;

        foreach ($categories as &$cat) {
            if ($cat['id'] == $catId) {
                $cat['folders'] = array_values(array_diff($cat['folders'], [$folderName]));
                $found = true;
                break;
            }
        }

        if (!$found) throw new Exception("Category not found", 404);

        $this->saveJson($categories);
        return ['success' => true];
    }

    public function deleteCategory($catId)
    {
        $categories = $this->loadJson([]);
        $initialCount = count($categories);

        $categories = array_values(array_filter($categories, function ($cat) use ($catId) {
            return $cat['id'] != $catId;
        }));

        if (count($categories) === $initialCount) {
            throw new Exception("Category not found", 404);
        }

        $this->saveJson($categories);
        return ['success' => true];
    }

    public function updateCategory($catId, $data)
    {
        $categories = $this->loadJson([]);
        foreach ($categories as &$cat) {
            if ($cat['id'] == $catId) {
                if (isset($data['name'])) $cat['name'] = htmlspecialchars($data['name']);
                $this->saveJson($categories);
                return $cat;
            }
        }
        throw new Exception("Category not found", 404);
    }

    private function loadJson($default)
    {
        if (!file_exists($this->categoriesFile)) return $default;
        return json_decode(file_get_contents($this->categoriesFile), true) ?: $default;
    }

    private function saveJson($data)
    {
        file_put_contents($this->categoriesFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

class ApiHandler
{
    private $service;
    private $categoryService;

    public function __construct()
    {
        $dataDir = __DIR__ . '/../memory';

        $this->service = new FolderService();
        $this->categoryService = new CategoryService($dataDir);
    }

    public function handle()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $scriptBase = dirname($_SERVER['SCRIPT_NAME']);
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim(substr($requestUri, strlen($scriptBase)), '/');
        $uri = explode('/', $path);

        $resource = $uri[0] ?? null;
        $id = $uri[1] ?? null;
        $action = $uri[2] ?? null;

        try {
            if ($resource === 'folders') {
                if ($method === 'POST') {
                    $input = json_decode(file_get_contents('php://input'), true);

                    if ($action === 'open') {
                        $this->service->addToRecent($id);
                        $this->send(['success' => true]);
                    }
                    if ($action === 'pin') {
                        $this->send($this->service->togglePin($id));
                    }

                    if (isset($input['action']) && $input['action'] === 'createFolders') {
                        $prefix = $input['prefix'] ?? 'test_folder';
                        $count = (int)($input['count'] ?? 5);
                        $this->send($this->service->createMultipleFolders($prefix, $count));
                    }
                }
                $this->handleFolders($method, $id);
            }

            if ($resource === 'categories') {
                $this->handleCategories($method, $id);
            }

            throw new Exception("Resource not found", 404);
        } catch (Exception $e) {
            $code = (int)$e->getCode() ?: 400;
            if ($code < 100 || $code > 599) $code = 400;
            http_response_code($code);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    private function handleFolders($method, $id)
    {
        $scriptBase = dirname($_SERVER['SCRIPT_NAME']);
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim(substr($requestUri, strlen($scriptBase)), '/');
        $segments = explode('/', $path);

        $action = $segments[2] ?? null;

        if ($method === 'POST' && $action === 'open') {
            if (!$id) throw new Exception("Folder name required", 400);
            $this->service->addToRecent($id);
            $this->send(['message' => 'Recent updated']);
        }

        switch ($method) {
            case 'GET':
                if ($id === 'download') {
                    $name = $_GET['name'] ?? '';
                    $tmp = $this->service->downloadZip($name);
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $name . '.zip"');
                    readfile($tmp);
                    unlink($tmp);
                    exit;
                }
                $this->send($this->service->getFolders());
                break;
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $this->send($this->service->createFolder($input['name'] ?? ''), 201);
                break;
            case 'DELETE':
                if (!$id) throw new Exception("Name required", 400);
                $this->service->deleteFolder($id);
                $this->send(null, 204);
                break;
            default:
                throw new Exception("Method $method not allowed", 405);
        }
    }

    private function handleCategories($method, $id)
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = dirname($_SERVER['SCRIPT_NAME']);
        $path = trim(substr($requestUri, strlen($base)), '/');
        $uri = explode('/', $path);
        $subResource = $uri[2] ?? null;
        $folderName = $uri[3] ?? null;

        switch ($method) {
            case 'GET':
                $this->send($this->categoryService->getCategories());
                break;

            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true) ?: [];

                if ($id && $subResource === 'folders') {
                    $fName = $input['folderName'] ?? $folderName;
                    if (!$fName) throw new Exception("Folder name required", 400);
                    $this->send($this->categoryService->addFolderToCategory($id, $fName));
                }

                $this->send($this->categoryService->createCategory($input['name'] ?? ''), 201);
                break;

            case 'PATCH':
                if (!$id) throw new Exception("Category ID required", 400);

                $input = json_decode(file_get_contents('php://input'), true);

                $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $segments = explode('/', trim($requestUri, '/'));
                $action = end($segments);

                if ($action === 'rename') {
                    $newName = $input['newName'] ?? '';
                    $catIdFromBag = $input['categoryId'] ?? $id; 

                    if (empty($newName)) {
                        throw new Exception("New name is required", 400);
                    }

                    $updatedCategory = $this->categoryService->updateCategory($catIdFromBag, ['name' => $newName]);

                    $this->send([
                        'success' => true,
                        'id' => $updatedCategory['id'],
                        'name' => $updatedCategory['name']
                    ]);
                }
                break;

            case 'DELETE':
                if (!$id) throw new Exception("Category ID required", 400);

                if ($subResource === 'folders' && $folderName) {
                    $this->send($this->categoryService->removeFolderFromCategory($id, $folderName));
                }

                $this->send($this->categoryService->deleteCategory($id), 200);
                break;

            default:
                throw new Exception("Method not allowed", 405);
        }
    }

    private function send($data, $code = 200)
    {
        http_response_code($code);
        $response = [];

        if ($code >= 400) {
            $response = [
                'error' => [
                    'message' => $data,
                    'status' => $code
                ]
            ];
        } else {
            $response = ['data' => $data];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new ApiHandler();
$api->handle();
