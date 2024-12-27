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

                        // создаем контейнер для кнопок
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