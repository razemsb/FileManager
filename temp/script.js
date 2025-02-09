document.addEventListener("DOMContentLoaded", () => {
    const themeToggleButton = document.getElementById("theme-toggle");
    const body = document.body;

    initializeTheme();
    setupThemeToggle();

    const foldersContainer = document.getElementById("foldersContainer");
    const showAllFolders = document.getElementById("showAllFolders");
    const hideFolders = document.getElementById("hideFolders");

    const allFolders = getFoldersData(foldersContainer);

    renderFolders(allFolders.slice(0, 8));

    function initializeTheme() {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            body.classList.add("dark-theme");
            themeToggleButton.textContent = "☀️";
        }
    }

    function setupThemeToggle() {
        themeToggleButton.addEventListener("click", () => {
            body.classList.toggle("dark-theme");
            themeToggleButton.textContent = body.classList.contains("dark-theme") ? "☀️" : "🌙";
            const currentTheme = body.classList.contains("dark-theme") ? "dark" : "light";
            localStorage.setItem("theme", currentTheme);
        });
    }

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
            const folderHTML = `
                <div class="col-md-3 mb-3 folder-item" data-folder="${all_folder.name}">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <p class="float-start">${all_folder.name.length > 15 ? all_folder.name.slice(0, 10) + "..." : all_folder.name}</p>
                                <img src="temp/folder.svg" style="width: 30px; height: 30px; object-fit: cover; float: right">
                            </h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value=" ${all_folder.name}">
                                <button type="submit" class="btn btn-outline-primary w-100">Перейти в папку</button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="pin_folder" value="${all_folder.name}">
                                <button class="btn btn-outline-success w-100 mt-2">Закрепить</button>
                            </form>
                            <form action="" method="post"><input type="hidden" value="${all_folder.name}" name="download_folder"><button type="submit" class="btn btn-outline-warning w-100 h-50 mt-2">Скачать .zip</button></form>
                            <p class="card-text mt-3" style="font-size: 13px!important;">Последнее изменение: ${all_folder['last_modified']}</p>
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
        if (allFolders.length > 8) {
            showAllFolders.style.display = "block"; 
            hideFolders.style.display = "none";
        } else {
            showAllFolders.style.display = "none"; 
            hideFolders.style.display = "none";
        }
    }
});
function searchFolders() {
    var query = document.getElementById('searchInput').value;
    if (query.length > 0) {
        document.getElementById('searchResults').style.display = 'block';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'search_folders.php?query=' + encodeURIComponent(query), true);
        xhr.onload = function () {
            if (xhr.status == 200) {
                var results = JSON.parse(xhr.responseText);
                var resultList = document.getElementById('searchResults');
                resultList.innerHTML = '';
                if (results.length > 0) {
                    results.forEach(function (folder) {
                        var li = document.createElement('li');
                        li.classList.add('list-group-item');
                        li.innerHTML = folder;

                        var buttonContainer = document.createElement('div');
                        buttonContainer.classList.add('d-flex', 'justify-content-end', 'mt-2');

                        var goButton = document.createElement('button');
                        goButton.classList.add('btn', 'btn-outline-info', 'btn-sm');
                        goButton.innerHTML = 'Перейти';
                        goButton.onclick = function() {
                            window.location.href = folder;
                        };

                        var pinButton = document.createElement('button');
                        pinButton.classList.add('btn', 'btn-outline-success', 'btn-sm', 'ms-2');
                        pinButton.innerHTML = 'Закрепить';
                        pinButton.onclick = function() {
                            var xhrPin = new XMLHttpRequest();
                            xhrPin.open('POST', '', true);
                            xhrPin.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhrPin.onload = function() {
                                if (xhrPin.status == 200) {
                                    alert('Папка закреплена!');
                                }
                            };
                            xhrPin.send('pin_folder=' + encodeURIComponent(folder));
                        };

                        buttonContainer.appendChild(goButton);
                        buttonContainer.appendChild(pinButton);
                        li.appendChild(buttonContainer);
                        resultList.appendChild(li);
                    });
                } else {
                    var li = document.createElement('li');
                    li.classList.add('list-group-item');
                    li.innerHTML = 'Нет результатов';
                    resultList.appendChild(li);
                }
            }
        };
        xhr.send();
    } else {
        document.getElementById('searchResults').style.display = 'none';
    }
}