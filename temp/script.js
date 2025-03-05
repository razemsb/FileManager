document.addEventListener("DOMContentLoaded", () => {

    const foldersContainer = document.getElementById("foldersContainer");
    const showAllFolders = document.getElementById("showAllFolders");
    const hideFolders = document.getElementById("hideFolders");

    const allFolders = getFoldersData(foldersContainer);

    renderFolders(allFolders.slice(0, 8));

    function getFoldersData(container) {
        if (container) {
            const foldersData = container.getAttribute("data-folders");
            try {
                return JSON.parse(foldersData || "[]");
            } catch (e) {
                console.error("Ошибка при парсинге данных папок:", e);
                return [];
            }
        }
        return [];
    }
    function renderFolders(folders) {
        foldersContainer.innerHTML = "";
        folders.forEach((all_folder) => {
        /*
            const folderHTML = `
                <div class="col-md-3 mb-3 folder-item" data-folder="${all_folder.name}">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <p class="float-start">${all_folder.name.length > 15 ? all_folder.name.slice(0, 10) + "..." : all_folder.name}</p>
                                <i class="bi bi-folder2 float-end" style="font-size: 1.6rem;"></i>
                            </h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value=" ${all_folder.name}">
                                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-door-open float-start"></i>Перейти в папку</button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="pin_folder" value="${all_folder.name}">
                                <button class="btn btn-outline-success w-100 mt-2"><i class="bi bi-pin-angle float-start"></i>Закрепить</button>
                            </form>
                            <form action="" method="post"><input type="hidden" value="${all_folder.name}" name="download_folder"><button type="submit" class="btn btn-outline-warning w-100 h-50 mt-2"><i class="bi bi-file-earmark-zip float-start"></i>Скачать .zip</button></form>
                            <p class="card-text mt-3" style="font-size: 13px!important; color: white;">Последнее изменение: ${all_folder['last_modified']}</p>
                        </div>
                    </div>
                    <div class="languages-modal" id="languagesModal${all_folder.name}" 
                         style="display: none; position: absolute; padding: 10px; z-index: 1000;">
                        <div class="spinner-border text-primary" role="status" id="spinner${all_folder.name}">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <ul id="languagesList${all_folder.name}" class="list-group"></ul>
                    </div>
                </div>
            `;
            */
            const folderHTML = `
            <div class="col-md-3 mb-3 folder-item" data-folder="${all_folder.name}" id="dragcart"">
                <div class="card shadow-sm">
                    <div class="card-body">
                       <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title m-0">
                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${all_folder.name}">${all_folder.name.length > 10 ? all_folder.name.slice(0, 8) + "..." : all_folder.name}</p>
                            </h5>
                            <div class="d-flex">
                            <form method="POST" action="">
                                <button class="btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Закрепить папку?">
                                    <i class="bi bi-pin-angle"></i>
                                </button>
                                <input type="hidden" name="pin_folder" value="${all_folder.name}">
                            </form>
                            <form method="POST" action="">
                                <button class="btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Перейти в папку?">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <input type="hidden" name="open" value="${all_folder.name}">
                            </form>
                            <form method="POST" action="">
                                <button class="btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Скачать папку?">
                                    <i class="bi bi-file-earmark-zip"></i>
                                </button>
                                <input type="hidden" name="download_folder" value="${all_folder.name}">
                            </form>
                            </div>
                        </div>
                        <hr class="text-white">
                        <p class="card-text text-center text-white mt-3" style="font-size: 11px;">Последнее изменение: ${all_folder['last_modified']}</p>
                    </div>
                </div>
            </div>
            <div class="languages-modal" id="languagesModal${all_folder.name}" 
                         style="display: none; position: absolute; padding: 10px; z-index: 1000;">
                        <div class="spinner-border text-primary" role="status" id="spinner${all_folder.name}">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <ul id="languagesList${all_folder.name}" class="list-group"></ul>
                    </div>
            </div>
        `;
            foldersContainer.insertAdjacentHTML("beforeend", folderHTML);
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    });
        });
        
        attachFolderEvents();
        toggleFolderButtonsVisibility();
    }

function attachFolderEvents() {
    const folderItems = document.querySelectorAll(".folder-item");
    folderItems.forEach((item) => {
        const folder = item.getAttribute("data-folder");
        const modal = document.getElementById(`languagesModal${folder}`);
        const languagesList = document.getElementById(`languagesList${folder}`);
        const spinner = document.getElementById(`spinner${folder}`);
        item.addEventListener("mouseenter", () => {
            modal.style.display = "block";
            spinner.style.display = "block";
            loadLanguages(folder, languagesList, spinner);
        });
        item.addEventListener("mouseleave", () => {
            modal.style.display = "none";
        });
    });
}
function loadLanguages(folder, languagesList, spinner) {
    if (!folder) {
        console.error("Параметр folder не передан!");
        return;
    }
    fetch(`?folder=${folder}`)
        .then((response) => response.json())
        .then((languages) => {
            console.log("Данные языков для папки", folder, languages);
            languagesList.innerHTML = "";
            for (const [language, count] of Object.entries(languages)) {
                const listItem = document.createElement("li");
                listItem.classList.add("list-group-item");
                listItem.textContent = `${language}: ${count} ${getFileWord(count)}`;
                languagesList.appendChild(listItem);
            }
            spinner.style.display = "none";
        })
        .catch((error) => {
            console.error("Ошибка при загрузке данных:", error);
            spinner.style.display = "none";
        });
}

function getFileWord(count) {
    if (count === 1) {
        return 'файл';
    } else if (count === 2 || count === 3 || count === 4) {
        return 'файла';
    } else {
        return 'файлов';
    }
}

    showAllFolders.addEventListener("click", () => {
        renderFolders(allFolders);
        showAllFolders.style.display = "none"; 
        hideFolders.style.display = "block"; 
    });

    hideFolders.addEventListener("click", () => {
        renderFolders(allFolders.slice(0, 8));
        showAllFolders.style.display = "block";
        hideFolders.style.display = "none"; 
    });

    function toggleFolderButtonsVisibility() {
        if (allFolders.length > 12) {
            showAllFolders.style.display = "block"; 
            hideFolders.style.display = "none";
        } else {
            showAllFolders.style.display = "none"; 
            hideFolders.style.display = "none";
        }
    }
});
/*
function searchFolders() {
    var searchInput = document.getElementById('searchInput');
    var query = searchInput.value;
    var resultList = document.getElementById('searchResults');
    var foldersContainer = document.getElementById('foldersContainer');
    if (query.length > 0) {
        foldersContainer.style.display = 'none';
        resultList.style.display = 'flex';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'search_folders.php?query=' + encodeURIComponent(query), true);
        xhr.onload = function () {
            if (xhr.status == 200) {
                var results = JSON.parse(xhr.responseText);
                resultList.innerHTML = '';
                if (results.length > 0) {
                    searchInput.classList.remove('invalid-search'); 
                    results.forEach(function (folder) {
                        const folderHTML = `
                            <div class="col-md-3 mb-3 folder-item" data-folder="${folder.name}">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <p class="float-start">${folder.name.length > 15 ? folder.name.slice(0, 10) + "..." : folder.name}</p>
                                            <i class="bi bi-folder2 float-end"></i>
                                        </h5>
                                        <form method="POST" action="">
                                            <input type="hidden" name="open" value="${folder.name}">
                                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-door-open float-start"></i>Перейти в папку</button>
                                        </form>
                                        <form method="POST" action="">
                                            <input type="hidden" name="pin_folder" value="${folder.name}">
                                            <button class="btn btn-outline-success w-100 mt-2"><i class="bi bi-pin-angle float-start"></i>Закрепить</button>
                                        </form>
                                        <form action="" method="post">
                                            <input type="hidden" value="${folder.name}" name="download_folder">
                                            <button type="submit" class="btn btn-outline-warning w-100 h-50 mt-2"><i class="bi bi-file-earmark-zip float-start"></i>Скачать .zip</button>
                                        </form>
                                        <p class="card-text mt-3" style="font-size: 13px!important; color: white;">
                                            Последнее изменение: ${folder.lastModified}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                        resultList.insertAdjacentHTML("beforeend", folderHTML);
                    });
                } else {
                    searchInput.classList.add('invalid-search');
                    resultList.innerHTML = '<div class="col-12 text-center mb-3">Нет результатов</div>';
                }
            }
        };
        xhr.send();
    } else {
        searchInput.classList.remove('invalid-search');
        resultList.style.display = 'none';
        foldersContainer.style.display = 'flex'; 
    }
}
*/
function searchFolders() {
    var query = document.getElementById('searchInput').value;
    if (query.length > 0) {
        document.getElementById('foldersContainer').style.display = 'none';
        document.getElementById('searchResults').style.display = 'flex';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'search_folders.php?query=' + encodeURIComponent(query), true);
        xhr.onload = function () {
            if (xhr.status == 200) {
                var results = JSON.parse(xhr.responseText);
                var resultList = document.getElementById('searchResults');
                resultList.innerHTML = '';

                if (results.length > 0) {
                    results.forEach(function (folderObj) {
                        const folderName = folderObj.name;
                        const lastModified = folderObj.lastModified;
                        const folderHTML = `
                            <div class="col-md-3 mb-3 folder-item" data-folder="${folderName}">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                       <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title m-0">
                                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${folderName}">${folderName.length > 10 ? folderName.slice(0, 8) + "..." : folderName}</p>
                                            </h5>
                                            <div class="d-flex">
                                            <form method="POST" action="">
                                                <button class="btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Закрепить папку?">
                                                    <i class="bi bi-pin-angle" style="color: white!important;"></i>
                                                </button>
                                                <input type="hidden" name="pin_folder" value="${folderName}">
                                            </form>
                                            <form method="POST" action="">
                                                <button class="btn btn-link" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Перейти в папку?">
                                                    <i class="bi bi-eye-fill" style="color: white!important;"></i>
                                                </button>
                                                <input type="hidden" name="open" value="${folderName}">
                                            </form>
                                            <form method="POST" action="">
                                                <button class="btn btn-link" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Скачать папку?">
                                                    <i class="bi bi-file-earmark-zip" style="color: white!important;"></i>
                                                </button>
                                                <input type="hidden" name="download_folder" value="${folderName}">
                                            </form>
                                            </div>
                                        </div>
                                        <hr class="text-white">
                                        <p class="card-text text-center text-white mt-3" style="font-size: 11px;">Последнее изменение: ${lastModified}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        resultList.insertAdjacentHTML("beforeend", folderHTML);
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                    });
                } else {
                    resultList.innerHTML = '<div class="col-12 text-center mb-3 text-danger">Нет результатов</div>';
                    searchInput.classList.add('invalid-search');
                }
            }
        };
        xhr.send();
    } else {
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('foldersContainer').style.display = 'flex';
        searchInput.classList.remove('invalid-search');
    }
}
const input = document.getElementById("searchInput");
input.addEventListener("input", () => {
    input.focus();
    window.scrollBy({ top: 120, behavior: "smooth" }); 
});
