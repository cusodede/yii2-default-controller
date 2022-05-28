# yii2-default-controller

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/cusodede/yii2-default-controller/CI%20with%20PostgreSQL)

Компонент, расширяющий функционал и удобство использования web-контроллеров Yii2.

Изначально компонент создавался для быстрого прототипирования однообразных CRUD, но оказался слишком удобной заменой обычных контроллеров.

# Установка через Composer

Добавьте

```
{
	"type": "vcs",
	"url": "https://github.com/cusodede/yii2-default-controller"
}
```

В список репозиториев файла `composer.json`, затем запустите

```
php composer.phar require cusodede/yii2-default-controller "^1.0.0"
```

или добавьте

```
"cusodede/yii2-default-controller": "^1.0.0"
```

В секцию `require` файла `composer.json`.

# Запуск локальных тестов

Скопируйте`tests/.env.example` в `tests/.env`, изменив в нём настройки согласно вашему локальному окружению. Затем выполните команду `php vendor/bin/codecept run`.
