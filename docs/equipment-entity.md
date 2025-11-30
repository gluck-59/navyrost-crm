# Сущность Equipment (Оборудование)

## Описание

Создана полная MVC-структура для управления оборудованием клиентов.

## Структура базы данных

### Таблица `equipment`

- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `customer_id` (INT, NOT NULL, FOREIGN KEY → customer.id)
- `name` (VARCHAR(255), NOT NULL) — название оборудования
- `mark` (VARCHAR(255), NULL) — марка оборудования
- `city` (VARCHAR(255), NULL) — город расположения
- `address` (VARCHAR(255), NULL) — адрес объекта
- `serial` (VARCHAR(255), NULL) — серийный номер
- `notes` (TEXT, NULL) — примечания

### Связи

- **ManyToOne**: Equipment → Customer
- **OneToMany**: Customer → Equipment (с каскадным удалением)
- При удалении Customer автоматически удаляется все его оборудование (`ON DELETE CASCADE`)

## Реализованные файлы

### Entity (Сущности)
- `/src/Entity/Equipment.php` — основная сущность
- `/src/Repository/EquipmentRepository.php` — репозиторий
- `/src/Entity/Customer.php` — добавлена связь с Equipment

### Controller (Контроллеры)
- `/src/Controller/EquipmentController.php` — CRUD операции:
  - `equipment_index` — список оборудования
  - `equipment_add` — создание нового оборудования
  - `equipment_show` — просмотр карточки оборудования
  - `equipment_edit` — редактирование оборудования
  - `equipment_delete` — удаление оборудования

### Form (Формы)
- `/src/Form/EquipmentType.php` — форма для создания/редактирования оборудования

### Templates (Шаблоны)
- `/templates/equipment/index.html.twig` — список оборудования
- `/templates/equipment/card.html.twig` — карточка оборудования
- `/templates/equipment/new.html.twig` — создание оборудования
- `/templates/equipment/edit.html.twig` — редактирование оборудования

### Migrations (Миграции)
- `/migrations/Version20251109145239.php` — создание таблицы equipment

## Права доступа

- **Обычный пользователь**: видит только оборудование своих клиентов
- **Администратор (ROLE_ADMIN)**: видит всё оборудование всех пользователей

## Навигация

Добавлен пункт меню "Оборудование" в главную навигацию (`/templates/base.html.twig`)

## Интеграция с клиентами

На карточке клиента (`/templates/customer/card.html.twig`) отображается:
- Список оборудования данного клиента
- Кнопка для быстрого добавления оборудования

## Маршруты

```
GET     /equipment           — список оборудования
GET/POST /equipment/add      — добавление оборудования
GET     /equipment/{id}      — просмотр оборудования
GET/POST /equipment/{id}/edit — редактирование оборудования
POST    /equipment/{id}      — удаление оборудования (с CSRF-защитой)
```

## Применение миграции

Миграция уже применена командой:
```bash
docker compose exec php bin/console doctrine:migrations:migrate
```

## Best Practices использованные в реализации

1. ✅ Каскадное удаление через `onDelete: 'CASCADE'` в атрибутах ORM
2. ✅ Правильная двусторонняя связь ManyToOne/OneToMany
3. ✅ CSRF-защита для удаления
4. ✅ Проверка прав доступа на всех действиях
5. ✅ Валидация форм с ограничениями длины полей
6. ✅ Фильтрация данных по правам пользователя
7. ✅ Использование Bootstrap-стилей для UI
8. ✅ Flash-сообщения для обратной связи с пользователем
