document.addEventListener("DOMContentLoaded", () => {
    const themeToggleButton = document.getElementById("theme-toggle");
    const body = document.body;

    initializeTheme();
    setupThemeToggle();

    const foldersContainer = document.getElementById("foldersContainer");
    const pinnedFoldersContainer = document.getElementById("pinnedFoldersContainer");
    const showAllFolders = document.getElementById("showAllFolders");
    const hideFolders = document.getElementById("hideFolders");

    const allFolders = getFoldersData(foldersContainer);

    renderFolders(allFolders.slice(0, 6));

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
            return JSON.parse(foldersData || "[]");
        }
        return [];
    }

    function renderFolders(folders) {
        foldersContainer.innerHTML = "";
        folders.forEach((folder) => {
            const folderHTML = `
                <div class="col-md-4 mb-3 folder-item" data-folder="${folder}">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                ${folder}
                                <img src="localhost/folder.svg" style="width: 30px; height: 30px; object-fit: cover; float: right">
                            </h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value="${folder}">
                                <button type="submit" class="btn btn-outline-primary w-100">Перейти в папку</button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="pin_folder" value="${folder}">
                                <button class="btn btn-outline-success w-100 mt-2">Закрепить</button>
                            </form>
                        </div>
                    </div>
                    <div class="languages-modal" id="languagesModal${folder}" 
                         style="display: none; position: absolute; padding: 10px; z-index: 1000;">
                        <div class="spinner-border text-primary" role="status" id="spinner${folder}">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <ul id="languagesList${folder}" class="list-group"></ul>
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
        fetch(`?folder=${folder}`)
            .then((response) => response.json())
            .then((languages) => {
                languagesList.innerHTML = "";
                for (const [language, count] of Object.entries(languages)) {
                    const listItem = document.createElement("li");
                    listItem.classList.add("list-group-item");
                    listItem.textContent = `${language}: ${count} файлов`;
                    languagesList.appendChild(listItem);
                }
                spinner.style.display = "none";
            })
            .catch((error) => {
                console.error("Ошибка загрузки языков:", error);
                spinner.style.display = "none";
            });
    }

    showAllFolders.addEventListener("click", () => {
        renderFolders(allFolders);
        showAllFolders.style.display = "none"; 
        hideFolders.style.display = "block"; 
    });

    hideFolders.addEventListener("click", () => {
        renderFolders(allFolders.slice(0, 6));
        showAllFolders.style.display = "block";
        hideFolders.style.display = "none"; 
    });

    function toggleFolderButtonsVisibility() {
        if (allFolders.length > 6) {
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
