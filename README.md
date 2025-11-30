# «На вырост» CRM (Symfony версия)

Мини-CRM для самых маленьких (с уклоном в ремонт некоего оборудования)  

Демо старой Codeigniter версии: https://opengluck.ru. 
Мой тестовый хостинг не поддерживает PHP8 поэтому Symfony версия не выложена.

Дока для разрабов живет в ```/docs```


[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
## Powered By

[![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Twig](https://img.shields.io/badge/Twig-1A1F1F?style=for-the-badge&logo=twig&logoColor=white)](https://twig.symfony.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![jQuery](https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white)](https://jquery.com/)

- Docker/Compose  
- PHP 8.4
- MySQL 8
- Composer  
- Symfony 7.3
- Doctrine ORM 3 
- FrankenPHP + Caddy
- Twig 
- Bootstrap 5 
- клиентская логика на JS/JQ 

## Использование
- стяните проект, поднимите докер, примените миграции
- войдите (admin:admin)
- создайте нужных пользователей
- создайте Клиента
- создайте его Оборудование
- создайте Заявку на ремонт этого Оборудования
- внутри заявки добавьте доходы-расходы по ней
- снимите предложенные демо-отчеты
- дайте обратную связь :)

## Структура
- стр входа
- заказчики
- оборудование
- заявки на ремонт
- платежи
- заготовка для наиболее востребованных отчетов

## Дополнительно
- **Изоляция данных по пользователям:**
созданные пользователем Клиенты, их Оборудование и Заявки видны только ему.

- В бесплатной версии нет разделения по ролям и логирования действий пользователей.
- В бесплатной версии нет группировки пользователей в организации.
- Верстка «Mobile First».
