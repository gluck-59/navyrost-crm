# Сущность Customer (Клиенты)

## Описание

`Customer` представляет клиента или филиал компании. Поддерживается иерархия филиалов (родитель → дочерние клиенты) и привязка к пользователю-создателю. Клиент может иметь список оборудования (`Equipment`).

## Структура данных

### Поля таблицы `customer`

- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `creator_id` (INT, NOT NULL, foreign key → `user.id`)
- `parent_id` (INT, NULLABLE, foreign key → `customer.id`, `ON DELETE CASCADE`)
- `name` (VARCHAR(64), NOT NULL)
- `data` (VARCHAR(255), NULL)

### Связи Doctrine

- **ManyToOne**: `Customer.creator` → `User`
- **ManyToOne (self)**: `Customer.parent` → `Customer`
- **OneToMany (self)**: `Customer.children` → `Customer`
- **OneToMany**: `Customer.equipment` → `Equipment` (каскадное удаление, orphanRemoval)

## Реализованные файлы

- `src/Entity/Customer.php` — описание сущности и связей
- `src/Repository/CustomerRepository.php` — репозиторий (по умолчанию)
- `src/Controller/CustomerController.php` — CRUD-контроллер c проверкой доступа
- `src/Form/CustomerType.php` — форма создания/редактирования клиента
- `templates/customer/index.html.twig` — список клиентов (с макросом отображения дерева)
- `templates/customer/new.html.twig` — форма создания клиента
- `templates/customer/edit.html.twig` — форма редактирования клиента
- `templates/customer/card.html.twig` — карточка клиента с оборудованием

## Контроллер `CustomerController`

- `customer_index` (`GET /customer`) — список клиентов
  - Администратор (`ROLE_ADMIN`) видит всех клиентов
  - Обычный пользователь видит только своих (по `creator`)
- `customer_add` (`GET|POST /customer/add`) — создание нового клиента
  - Устанавливает текущего пользователя в качестве `creator`
- `customer_show` (`GET /customer/{id}`) — карточка клиента
  - Администратор видит доп. информацию о создателе
  - Доступ ограничен создателем клиента или администратором
- `customer_edit` (`GET|POST /customer/{id}/edit`) — редактирование
  - Доступ только администратору или создателю
- `customer_delete` (`POST /customer/{id}`) — удаление клиента
  - Защищено CSRF-токеном `delete_customer_{id}`
  - При удалении срабатывает каскадное удаление дочерних клиентов и оборудования

## Форма `CustomerType`

- Поля: `name`, `data`, `parent`
- Валидация: `NotBlank`, `Length`
- Поле `parent` отображает клиентов в selectpicker'е
  - Для не-администраторов список ограничен клиентами текущего пользователя
  - При редактировании исключается текущий клиент из списка

## Представления (Twig)

- `index` строит дерево клиентов (родитель → дети) рекурсивным макросом
- `card`
  - Показывает основные поля клиента
  - Отображает список оборудования клиента с кнопкой "Добавить оборудование"
- `new` и `edit`
  - Используют bootstrap-формы с selectpicker'ом для выбора родителя

## Управление доступом

- Для всех действий, кроме публичных страниц, проверяется авторизация
- Администратор имеет полный доступ
- Обычный пользователь может управлять только собственными клиентами

## Дополнительно

- При создании/редактировании выводятся `flash`-сообщения (`success`/`error`)
- Шаблоны используют классы Bootstrap и selectpicker (`data-live-search`)
- В шапке сайта (`templates/base.html.twig`) раздел "Клиенты" ведёт на `customer_index`
