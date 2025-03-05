<?php
session_start();

// функция поиска папок и получения даты изменения
function searchFolders($dir, $query) {
    $folders = [];
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_dir($dir . DIRECTORY_SEPARATOR . $entry) && stripos($entry, $query) !== false) {
                $lastModified = filemtime($dir . DIRECTORY_SEPARATOR . $entry); // дата изменения
                $folders[] = [
                    "name" => $entry,
                    "lastModified" => date("Y-m-d H:i:s", $lastModified) 
                ];
            }
        }
        closedir($handle);
    }
    return $folders;
}

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $folders = searchFolders(__DIR__, $query); // поиск папок
    echo json_encode($folders, JSON_UNESCAPED_UNICODE); // отправка в JSON
}
?>