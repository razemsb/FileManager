<?php
header('Content-Type: application/json; charset=utf-8');
define('BASE_DIR', __DIR__ . '/../..');
define('DATA_DIR', BASE_DIR . '/data/memory');
define('CATEGORIES_FILE', DATA_DIR . '/categories.json');

function readCategories()
{
    if (!file_exists(CATEGORIES_FILE))
        return [];
    $s = @file_get_contents(CATEGORIES_FILE);
    if ($s === false)
        return [];
    $d = json_decode($s, true);
    return is_array($d) ? $d : [];
}

function saveCategories($arr)
{
    if (!is_dir(DATA_DIR))
        @mkdir(DATA_DIR, 0755, true);
    $tmp = CATEGORIES_FILE . '.tmp';
    $w = @file_put_contents($tmp, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    if ($w === false)
        return false;
    return @rename($tmp, CATEGORIES_FILE);
}

function send($ok, $payload = [])
{
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true) ?: [];
    $action = ($method === 'GET') ? ($_GET['action'] ?? '') : ($input['action'] ?? '');

    if (!$action) {
        send(false, ['error' => 'action required']);
    }

    $cats = readCategories();

    switch ($action) {
        case 'getCategories':
            send(true, ['data' => $cats]);
            break;

        case 'createCategory':
            $name = trim($input['name'] ?? '');
            if ($name === '')
                send(false, ['error' => 'empty name']);
            if (mb_strlen($name) > 150)
                send(false, ['error' => 'name too long']);
            $name = preg_replace('/[\/\\\\]/u', '-', $name);
            $id = 'cat_' . uniqid();
            $new = ['id' => $id, 'name' => $name, 'folders' => []];
            $cats[] = $new;
            if (!saveCategories($cats))
                send(false, ['error' => 'cannot save']);
            send(true, ['category' => $new]);
            break;

        case 'deleteCategory':
            $id = $input['categoryId'] ?? '';
            $found = false;
            foreach ($cats as $k => $c) {
                if (($c['id'] ?? '') === $id) {
                    $found = true;
                    unset($cats[$k]);
                    break;
                }
            }
            if (!$found)
                send(false, ['error' => 'not found']);
            $cats = array_values($cats);
            if (!saveCategories($cats))
                send(false, ['error' => 'cannot save']);
            send(true);
            break;

        case 'renameCategory':
            $id = $input['categoryId'] ?? '';
            $newName = trim($input['newName'] ?? '');
            if ($newName === '')
                send(false, ['error' => 'empty name']);
            $newName = preg_replace('/[\/\\\\]/u', '-', $newName);
            $found = false;
            foreach ($cats as $k => $c) {
                if (($c['id'] ?? '') === $id) {
                    $cats[$k]['name'] = $newName;
                    $found = true;
                    break;
                }
            }
            if (!$found)
                send(false, ['error' => 'not found']);
            if (!saveCategories($cats))
                send(false, ['error' => 'cannot save']);
            send(true);
            break;

        case 'addFolder':
            $id = $input['categoryId'] ?? '';
            $folder = trim($input['folderName'] ?? '');
            if ($folder === '')
                send(false, ['error' => 'empty folder']);
            $found = false;
            foreach ($cats as $k => $c) {
                if (($c['id'] ?? '') === $id) {
                    if (!in_array($folder, $cats[$k]['folders'])) {
                        $cats[$k]['folders'][] = $folder;
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found)
                send(false, ['error' => 'category not found']);
            if (!saveCategories($cats))
                send(false, ['error' => 'cannot save']);
            send(true);
            break;

        case 'removeFolder':
            $id = $input['categoryId'] ?? '';
            $folder = trim($input['folderName'] ?? '');
            $found = false;
            foreach ($cats as $k => $c) {
                if (($c['id'] ?? '') === $id) {
                    $cats[$k]['folders'] = array_values(array_filter($cats[$k]['folders'], function ($f) use ($folder) {
                        return $f !== $folder;
                    }));
                    $found = true;
                    break;
                }
            }
            if (!$found)
                send(false, ['error' => 'category not found']);
            if (!saveCategories($cats))
                send(false, ['error' => 'cannot save']);
            send(true);
            break;

        default:
            send(false, ['error' => 'unknown action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
