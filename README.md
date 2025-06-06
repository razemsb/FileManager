# 📁 **EnigmaDevix** FileManager

![Logo](./data/img/favicon.ico)

Современный файловый менеджер для управления папками с интуитивно понятным интерфейсом и мощными возможностями.

## 💻 Скриншоты
![screen-1](./data/img/screenshot-1.png)
![screen-2](./data/img/screenshot-2.png)

## ✨ Особенности

- **Удобный интерфейс** с адаптивным дизайном
- **Быстрый поиск** по названиям папок
- **Закрепление** часто используемых папок
- **История** недавно открытых папок
- **Подробная информация** о дате изменения
- **Полностью адаптивный** под все устройства

## 🛠 Технологии

![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Axios](https://img.shields.io/badge/Axios-5A29E4?style=for-the-badge&logo=axios&logoColor=white)

## 📦 Установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/razemsb/FileManager.git
```
Убедитесь, что у вас установлен PHP (версия 7.4 или выше)

Разместите файлы на вашем веб-сервере

Убедитесь, что папка data доступна для записи:

```bash
    chmod -R 755 data/
```
Для работы 404.php измените строчку в httpd.conf 

```httpd.conf
    ErrorDocument 404 /404.php
```

🚀 Возможности
Основные функции

    Просмотр всех доступных папок

    Фильтрация по категориям (Все/Закрепленные/Недавние)

    Быстрый поиск по названию

    Управление папками

    Закрепление важных папок для быстрого доступа

    Автоматическое сохранение истории недавних папок

    Пользовательский интерфейс

    Адаптивный дизайн для всех устройств

    Интуитивно понятное управление

    Подробные tooltips с информацией

    Визуальные подсказки для закрепленных и недавних папок

📄 Структура проекта
```
FileManager/
├── data/
│   ├── files/
│   ├── recent_folders.txt    # Временные файлы и данные
│   ├── pinned_folders.txt    # Временные файлы и данные
│   ├── api/   
│   ├── api.php               # PHP API для работы с файлами    
│   ├── app/
│   ├── Main.vue              # Основная логика приложения
│   ├── css/
│   ├── style.min.css         # Стили приложения
│   ├── js/                   
│   ├── dark-theme.js         # Для переключения темной/светлой темы
│   ├── vendors/
│   ├── bootstrap             # Зависимости
│   ├── axios                 # Зависимости
│   ├── vue                   # Зависимости
│   ├── popperjs              # Зависимости
│   ├── img/                  # Favicon
├── index.html                # Главная страница
└── README.md                 # Этот файл
```

🤝 Участие в разработке

Приветствуются любые вклады в проект! Если вы хотите внести свой вклад:

    Форкните репозиторий

    Создайте ветку с вашими изменениями (git checkout -b feature/AmazingFeature)

    Зафиксируйте изменения (git commit -m 'Add some AmazingFeature')

    Запушьте ветку (git push origin feature/AmazingFeature)

    Откройте Pull Request

📜 Лицензия

Распространяется под лицензией MIT. См. файл LICENSE для получения дополнительной информации.

**EnigmaDevix** FileManager © 2025 - Упрощаем управление файлами! 🚀
