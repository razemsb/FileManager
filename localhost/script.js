function searchFolders() {
    var query = document.getElementById('searchInput').value;
    if (query.length > 0) {
        // показываем спиок результатов
        document.getElementById('searchResults').style.display = 'block';

        // отправляем запрос на сервер и обрабатываем результат
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'search_folders.php?query=' + encodeURIComponent(query), true);
        xhr.onload = function () {
            if (xhr.status == 200) {
                var results = JSON.parse(xhr.responseText);
                var resultList = document.getElementById('searchResults');
                resultList.innerHTML = '';

                // если есть результаты, отображаем их
                if (results.length > 0) {
                    results.forEach(function (folder) {
                        var li = document.createElement('li');
                        li.classList.add('list-group-item');
                        
                        // Добавляем имя папки
                        li.innerHTML = folder;

                        // создаем контейнер для кнопок, чтобы выровнять их справа
                        var buttonContainer = document.createElement('div');
                        buttonContainer.classList.add('d-flex', 'justify-content-end', 'mt-2');

                        // кнопка "Перейти в папку"
                        var goButton = document.createElement('button');
                        goButton.classList.add('btn', 'btn-outline-info', 'btn-sm');
                        goButton.innerHTML = 'Перейти';
                        goButton.onclick = function() {
                            window.location.href = folder;
                        };

                        // кнопка "Закрепить"
                        var pinButton = document.createElement('button');
                        pinButton.classList.add('btn', 'btn-outline-success', 'btn-sm', 'ms-2');
                        pinButton.innerHTML = 'Закрепить';
                        pinButton.onclick = function() {
                            // отправляем запрос на сервер для закрепления папки
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

                        // добавляем кнопки в контейнер
                        buttonContainer.appendChild(goButton);
                        buttonContainer.appendChild(pinButton);

                        // добавляем контейнер с кнопками в элемент списка
                        li.appendChild(buttonContainer);

                        // добавляем элемент в список
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
        // ксли поле пустое, скрываем список результатов
        document.getElementById('searchResults').style.display = 'none';
    }
}
document.addEventListener("DOMContentLoaded", () => {
    const themeToggleButton = document.getElementById("theme-toggle");
    const body = document.body;

    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") {
        body.classList.add("dark-theme");
        themeToggleButton.textContent = "☀️";
    }

    themeToggleButton.addEventListener("click", () => {
        body.classList.toggle("dark-theme");

        themeToggleButton.textContent = body.classList.contains("dark-theme") ? "☀️" : "🌙";
        
        localStorage.setItem("theme", body.classList.contains("dark-theme") ? "dark" : "light");
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const showAllButton = document.getElementById('showAllPinnedFolders');
    const hideButton = document.getElementById('hidePinnedFolders');
    const pinnedFoldersContainer = document.getElementById('pinnedFoldersContainer');
    
    if (showAllButton && pinnedFoldersContainer) {
        const allFolders = JSON.parse(pinnedFoldersContainer.getAttribute('data-pinned-folders'));
        const container = document.getElementById('pinnedFoldersContainer');

        showAllButton.addEventListener('click', function() {

            container.innerHTML = '';
            allFolders.forEach(folder => {
                const card = document.createElement('div');
                card.classList.add('col-md-4', 'mb-3');
                card.innerHTML = `
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">${folder} <img src="localhost/pinned.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value="${folder}">
                                <button type="submit" class="btn btn-primary w-100">Перейти в папку</button>
                            </form>
                            <form method="POST" action="" class="mt-2">
                                <input type="hidden" name="unpin_folder" value="${folder}">
                                <button type="submit" class="btn btn-outline-danger w-100">Открепить</button>
                            </form>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });

            showAllButton.style.display = 'none';
            hideButton.style.display = 'block';
        });

        hideButton.addEventListener('click', function() {
            container.innerHTML = '';

            const pinnedFoldersToShow = allFolders.slice(0, 3);
            pinnedFoldersToShow.forEach(folder => {
                const card = document.createElement('div');
                card.classList.add('col-md-4', 'mb-3');
                card.innerHTML = `
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">${folder} <img src="localhost/pinned.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="open" value="${folder}">
                                <button type="submit" class="btn btn-primary w-100">Перейти в папку</button>
                            </form>
                            <form method="POST" action="" class="mt-2">
                                <input type="hidden" name="unpin_folder" value="${folder}">
                                <button type="submit" class="btn btn-outline-danger w-100">Открепить</button>
                            </form>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });

            hideButton.style.display = 'none';
            showAllButton.style.display = 'block';
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const showAllButton = document.getElementById('showAllFolders');
    const hideButton = document.getElementById('hideFolders');
    const foldersContainer = document.getElementById('foldersContainer');
    const allFolders = JSON.parse(foldersContainer.getAttribute('data-folders'));

    showAllButton.addEventListener('click', function() {

        let allFoldersHtml = '';
        allFolders.forEach(function(folder) {
            allFoldersHtml += `
                <div class="col-md-4 mb-3 folder-item">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">${folder}<img src="localhost/folder.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
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
                </div>
            `;
        });
        foldersContainer.innerHTML = allFoldersHtml;

        showAllButton.style.display = 'none';
        hideButton.style.display = 'inline-block';
    });

    hideButton.addEventListener('click', function() {

        let firstSixFoldersHtml = '';
        for (let i = 0; i < 6; i++) {
            const folder = allFolders[i];
            firstSixFoldersHtml += `
                <div class="col-md-4 mb-3 folder-item">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">${folder}<img src="localhost/folder.svg" style="width: 30px; height: 30px; object-fit: cover; float: right"></h5>
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
                </div>
            `;
        }
        foldersContainer.innerHTML = firstSixFoldersHtml;

        showAllButton.style.display = 'inline-block';
        hideButton.style.display = 'none';
    });
});
