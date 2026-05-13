<?php

declare(strict_types=1);

require __DIR__ . '/../src/Database.php';

header('Content-Type: application/json');

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody(): array
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || $rawBody === '') {
        respond(400, ['message' => 'request body is required']);
    }

    $data = json_decode($rawBody, true);

    if (!is_array($data)) {
        respond(400, ['message' => 'invalid json body']);
    }

    foreach (['title', 'author_id', 'publisher_id'] as $field) {
        if (!array_key_exists($field, $data)) {
            respond(400, ['message' => "missing field: {$field}"]);
        }
    }

    if (!is_string($data['title']) || trim($data['title']) === '') {
        respond(400, ['message' => 'title must be a non-empty string']);
    }

    if (!is_numeric($data['author_id']) || !is_numeric($data['publisher_id'])) {
        respond(400, ['message' => 'author_id and publisher_id must be numeric']);
    }

    return [
        'title' => trim($data['title']),
        'author_id' => (int) $data['author_id'],
        'publisher_id' => (int) $data['publisher_id'],
    ];
}

function ensureEntityExists(PDO $db, string $table, int $id): void
{
    $allowedTables = ['authors', 'publishers'];

    if (!in_array($table, $allowedTables, true)) {
        respond(500, ['message' => 'invalid relation check']);
    }

    $stmt = $db->prepare("SELECT id FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->fetch() === false) {
        respond(400, ['message' => "{$table} record with id {$id} does not exist"]);
    }
}

try {
    $db = Database::connect();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $uri = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

    if (($uri[0] ?? '') === 'api') {
        array_shift($uri);
    }

    if (($uri[0] ?? '') === 'status') {
        respond(200, ['status' => 'ok']);
    }

    if (($uri[0] ?? '') !== 'books') {
        respond(404, ['message' => 'endpoint not found']);
    }

    $id = isset($uri[1]) ? (int) $uri[1] : null;

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                $stmt = $db->prepare('SELECT * FROM books WHERE id = ?');
                $stmt->execute([$id]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($book === false) {
                    respond(404, ['message' => 'book not found']);
                }

                respond(200, $book);
            }

            $stmt = $db->query('SELECT * FROM books');
            respond(200, $stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'POST':
            $data = readJsonBody();
            ensureEntityExists($db, 'authors', $data['author_id']);
            ensureEntityExists($db, 'publishers', $data['publisher_id']);

            $stmt = $db->prepare(
                'INSERT INTO books(title, author_id, publisher_id) VALUES(?, ?, ?)'
            );
            $stmt->execute([
                $data['title'],
                $data['author_id'],
                $data['publisher_id'],
            ]);

            respond(201, ['status' => 'created', 'id' => (int) $db->lastInsertId()]);

        case 'PUT':
            if ($id === null) {
                respond(400, ['message' => 'book id is required']);
            }

            $data = readJsonBody();
            ensureEntityExists($db, 'authors', $data['author_id']);
            ensureEntityExists($db, 'publishers', $data['publisher_id']);

            $stmt = $db->prepare(
                'UPDATE books SET title = ?, author_id = ?, publisher_id = ? WHERE id = ?'
            );
            $stmt->execute([
                $data['title'],
                $data['author_id'],
                $data['publisher_id'],
                $id,
            ]);

            if ($stmt->rowCount() === 0) {
                respond(404, ['message' => 'book not found']);
            }

            respond(200, ['status' => 'updated']);

        case 'DELETE':
            if ($id === null) {
                respond(400, ['message' => 'book id is required']);
            }

            $stmt = $db->prepare('DELETE FROM books WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                respond(404, ['message' => 'book not found']);
            }

            respond(200, ['status' => 'deleted']);

        default:
            respond(405, ['message' => 'method not allowed']);
    }
} catch (PDOException $exception) {
    respond(500, ['message' => 'database error', 'details' => $exception->getMessage()]);
} catch (Throwable $exception) {
    respond(500, ['message' => 'internal server error', 'details' => $exception->getMessage()]);
}
