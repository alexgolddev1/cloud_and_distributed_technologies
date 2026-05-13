Лабораторна робота №1
Створення веб сервісів у вигляді образів за допомогою Docker

Опис проєкту

У даному проєкті реалізовано REST-сервіс на мові програмування PHP, який працює у контейнерному середовищі Docker та виконує CRUD-операції з даними.

Сервіс працює з базою даних MySQL та використовує три зв’язані таблиці:

authors
publishers
books

Таблиці створюються автоматично при першому запуску програми.

Технології

У проєкті використано:

PHP
Docker
Docker Compose
MySQL
REST API

Структура проєкту

php-rest-docker-lab

docker-compose.yml
Dockerfile
composer.json

public/
index.php

src/
Database.php

tests/
ApiTest.php


Структура бази даних

authors
id
name

publishers
id
name

books
id
title
author_id
publisher_id

Зв’язки між таблицями:

authors (1) --- (N) books
publishers (1) --- (N) books


Запуск проєкту

1. Клонувати репозиторій

git clone https://github.com/USERNAME/lab1-docker-rest.git

2. Перейти у папку проєкту

cd lab1-docker-rest

3. Запустити контейнери

docker compose up --build


Після запуску створюються два контейнери:

PHP REST API
MySQL база даних
phpMyAdmin


REST API

Сервіс доступний за адресою:

http://localhost:8080


phpMyAdmin

Інтерфейс для перегляду бази даних доступний за адресою:

http://localhost:8081

Вхід:

Server: db
User: root
Password: root


Отримати список книг

GET /books


Отримати книгу

GET /books/{id}


Додати книгу

POST /books

Body JSON

{
 "title": "Docker Book",
 "author_id": 1,
 "publisher_id": 1
}


Оновити книгу

PUT /books/{id}


Видалити книгу

DELETE /books/{id}


Підключення до бази даних

Host: localhost
Port: 3306
User: root
Password: root
Database: lab


Збереження даних

Дані бази даних зберігаються у Docker volume:

db_data


Тестування

У проєкті присутній базовий Unit Test:

tests/ApiTest.php


Автор Михайлов Олександр

Лабораторна робота виконана в рамках дисципліни
"Хмарні та розподілені технології" 
