<?php
// для вывода ошибок
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// файлы для хранения данных
$pinnedFile = 'localhost/pinned_folders.txt';
$recentFile = 'localhost/recent_folders.txt';

// функция для получения папок из директории
function getFolders($dir) {
    $folders = [];
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                $folders[] = $entry;
            }
        }
        closedir($handle);
    }
    return $folders;
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

// недавно посещенные папки (максимум 5)
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
        if (count($recentFolders) > 6) {
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
    <link rel="stylesheet" href="localhost/style.css">
</head>
<body>
<div class="container mt-5">
<button id="theme-toggle" class="btn btn-light rounded-circle position-fixed top-0 end-0 m-3 shadow-sm">🌙</button>
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasExampleLabel">Меню</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <h6>Закрепленные папки</h6>
        <ul class="list-group list-group-flush mb-4">
            <?php if (count($pinnedFolders) > 0): ?>
                <?php foreach ($pinnedFolders as $folder): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo $folder; ?>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="open" value="<?php echo $folder; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success">Перейти</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item">Нет закрепленных папок</li>
            <?php endif; ?>
        </ul>

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
    </div>
</div>
<button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><img src="localhost/menu.svg" style="width: 30px; height: 30px; object-fit: cover;"></button>
<h1 class="text-center mb-4">Enigma | Файловый Менеджер</h1>

   <div class="mb-4">
    <input type="text" id="searchInput" class="form-control" placeholder="Поиск папок..." onkeyup="searchFolders()">
    <ul id="searchResults" class="list-group mt-2" style="display: none;"></ul>
   </div>
   <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Закрепленные Папки (<?= count($pinnedFolders); ?>)</h3>
        <div class="d-flex">
            <?php if (count($pinnedFolders) > 3): ?>
                <button id="showAllPinnedFolders" class="btn btn-outline w-10 mb-3">
                    <img src="localhost/down.svg" style="width: 30px; height: 30px; object-fit: cover;">
                </button>
                <button id="hidePinnedFolders" class="btn btn-outlinew-10 mb-3" style="display: none;">
                    <img src="localhost/up.svg" style="width: 30px; height: 30px; object-fit: cover;">
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12">
        <div class="row" id="pinnedFoldersContainer" data-pinned-folders='<?php echo json_encode($pinnedFolders); ?>'>
            <?php 
            $pinnedFoldersToShow = array_slice($pinnedFolders, 0, 3);
            foreach ($pinnedFoldersToShow as $folder): ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $folder; ?><img src="localhost/pinned.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
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
         <h3>Недавно посещенные (<?= count($recentFolders); ?> из 6)</h3>
         <div class="row">
             <?php foreach ($recentFolders as $folder): ?>
                 <div class="col-md-4 mb-3">
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

 <div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Все Папки (<?= count($folders); ?>)</h3>
        <div class="d-flex">
            <?php if (count($folders) > 6): ?>
                <button id="showAllFolders" class="btn btn-outline w-10 mb-3">
                    <img src="localhost/down.svg" style="width: 30px; height: 30px; object-fit: cover;">
                </button>
                <button id="hideFolders" class="btn btn-outline w-10 mb-3" style="display: none;">
                    <img src="localhost/up.svg" style="width: 30px; height: 30px; object-fit: cover;">
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12">
        <div class="row" id="foldersContainer" data-folders='<?php echo json_encode($folders); ?>'>
            <?php 
            $foldersToShow = array_slice($folders, 0, 6);
            foreach ($foldersToShow as $folder): ?>
                <div class="col-md-4 mb-3 folder-item">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $folder; ?><img src="localhost/folder.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value="<?php echo $folder; ?>">
                                <button type="submit" class="btn btn-outline-primary w-100">Перейти в папку</button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="pin_folder" value="<?php echo $folder; ?>">
                                <button class="btn btn-outline-success w-100 mt-2">Закрепить</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</div>
    <script src="localhost/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
