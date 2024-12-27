<?php
session_start();

// функция запроса и поиска
function searchFolders($dir, $query) {
    $folders = [];
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_dir($dir . DIRECTORY_SEPARATOR . $entry) && stripos($entry, $query) !== false) {
                $folders[] = $entry;
            }
        }
        closedir($handle);
    }
    return $folders;
}

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $folders = searchFolders(__DIR__, $query); // папки из директории
    echo json_encode($folders); // отправка в json
}
?>
