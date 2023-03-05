# [RU]
# I4P-Core
## Что такое I4P-Core?
I4P-Core это мини-фре́ймворк, на стадиях АЛЬФА-ТЕСТИРОВАНИЯ, сделанный по заказу для ISP4Private, на его основе, можно легко и быстро создавать свои плагины для продуктов 4-го поколения ISPManager.

## Что включает в себя I4P-Core?
В состав данного плагина, входит:
- [Библиотека INIStorage](https://github.com/pimnik98/INIStorage) - для удобной работы с INI-файлами
- Центр управления модификациями (перезагрузка ПУ, тонкая настройка плагинов)

## На какие языки сейчас переведен I4P-Core?
- Англиский
- Русский

## Какой функционал сейчас имеется?
- Работа при помощи ООП
- Подготовка PHP для работы с ISPManager
- Проверка необходимых библиотек в составе
- Система логгирования
- Удобный XML-конструктор данных
- По мере необходимости функционал будет расширятся или в составе ядра, или как отдельные библиотеки

## Какие требование к серверу?
- Продукт 4-го поколения ISPManager (Учтите, что софт платный)
- Установлен на сервере PHP 5.3+

## Как подключиться к репозиторию I4P?
1. Авторизуйтесь администратором
2. Перейдите `Настройки сервера > Плагины > Источники`
3. Добавьте этот источник
> http://isp4private.ru/download/plugins/plugins.xml
4. Потом выберите и обновите источник

## Я хочу помочь проекту
Если вы желаете, помочь с переводом, то милости просим.
Локализация содержиться в двух файлах:
- Для панели управления: [etc/ispmgr_mod_i4p_core.xml](https://github.com/pimnik98/I4P-Core/blob/main/etc/ispmgr_mod_i4p_core.xml)
- Для самого плагина: [etc/isp4private/i4p_core.xml](https://github.com/pimnik98/I4P-Core/blob/main/etc/isp4private/i4p_core.ini)

Еще вы можете просто поддержать проект, поставив звезду нам в репозиторий.
Подписывайтесь на уведомления от репозитория, тогда вы сможете наблюдать за измениями в самом репозитории ISP4Private

Если вы используйте данный фреймворк, плагины основанные на нем и тому подобное, то только вы несете ответственность за порчу данных,вывод из строя оборудования и прочие поломки.

# [EN]
# I4P-Core
## What is I4P-Core?
I4P-Core is a mini-framework, at the ALPHA TESTING stages, made to order for ISP4Private, based on it, you can easily and quickly create your own plugins for 4th generation ISPmanager products.

## What does I4P-Core include?
This plugin includes:
- [INIStorage Library](https://github.com/pimnik98/INIStorage ) - for convenient work with INI files
- Modification Control Center (reboot of the PU, fine-tuning of plugins)

## What languages have I4P-Core been translated into now?
- English
- Russian

## What functionality is available now?
- Work with the help of OOP
- Preparing PHP to work with ISPmanager
- Checking the necessary libraries as part of
- Logging system
- Convenient XML data constructor
- As necessary, the functionality will be expanded either as part of the core, or as separate libraries

## What are the server requirements?
- Product of the 4th generation ISPmanager (Note that the software is paid)
- Installed on the PHP 5.3+


## How to connect to the repository?
1. Log in as an administrator
2. Go to `Server Settings > Plugins > Sources`
3. Add this source
> http://isp4private.ru/download/plugins/plugins.xml
4. Select and update source

## I want to help the project
If you wish to help with the translation, then you are welcome.
Localization is contained in two files:
- For the control panel: [etc/ispmgr_mod_i4p_core.xml](https://github.com/pimnik98/I4P-Core/blob/main/etc/ispmgr_mod_i4p_core.xml)
- For the plugin itself: [etc/isp4private/i4p_core.xml](https://github.com/pimnik98/I4P-Core/blob/main/etc/isp4private/i4p_core.ini)

You can also just support the project by putting a star in our repository.
Subscribe to notifications from the repository, then you will be able to observe changes in the ISP4Private repository itself

If you use this framework, plugins based on it and the like, then only you are responsible for data corruption, equipment failure and other breakdowns.
