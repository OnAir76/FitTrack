<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

/*
|--------------------------------------------------------------------------
| CORS - obsługa preflight
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
|--------------------------------------------------------------------------
| Pomocnicze funkcje
|--------------------------------------------------------------------------
*/

function input(): array
{
    $raw = file_get_contents('php://input');

    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function out(mixed $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

function requireField(array $data, string $field): mixed
{
    if (
        !array_key_exists($field, $data) ||
        $data[$field] === '' ||
        $data[$field] === null
    ) {
        out([
            'error' => "Brak wymaganego pola: {$field}"
        ], 422);
    }

    return $data[$field];
}

/*
|--------------------------------------------------------------------------
| Aplikacja
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Połączenie z bazą
    |--------------------------------------------------------------------------
    */

    $pdo = db();

    /*
    |--------------------------------------------------------------------------
    | Podstawowe informacje o żądaniu
    |--------------------------------------------------------------------------
    */

    $method = $_SERVER['REQUEST_METHOD'];

    $requestPath = parse_url(
        $_SERVER['REQUEST_URI'],
        PHP_URL_PATH
    );

    $path = trim(
        (string)$requestPath,
        '/'
    );

    /*
    |--------------------------------------------------------------------------
    | WAŻNE
    |--------------------------------------------------------------------------
    |
    | Aplikacja znajduje się tutaj:
    |
    | /trainingapp/
    |
    | Przykład:
    |
    | /trainingapp/api/health
    |
    | Najpierw usuwamy /trainingapp/
    |
    */

    $basePath = 'trainingapp';

    if ($path === $basePath) {

        $path = '';

    } elseif (
        str_starts_with(
            $path,
            $basePath . '/'
        )
    ) {

        $path = substr(
            $path,
            strlen($basePath) + 1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rozbijamy ścieżkę na elementy
    |--------------------------------------------------------------------------
    |
    | /trainingapp/api/exercises
    |
    | po usunięciu /trainingapp/:
    |
    | api/exercises
    |
    */

    $parts = array_values(
        array_filter(
            explode('/', $path)
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Usuwamy "api"
    |--------------------------------------------------------------------------
    |
    | api/exercises
    |
    | ↓
    |
    | exercises
    |
    */

    if (($parts[0] ?? '') === 'api') {
        array_shift($parts);
    }

    /*
    |--------------------------------------------------------------------------
    | Główny endpoint
    |--------------------------------------------------------------------------
    */

    $resource = $parts[0] ?? '';

    /*
    |--------------------------------------------------------------------------
    | HEALTH CHECK
    |--------------------------------------------------------------------------
    |
    | GET:
    |
    | /trainingapp/api/health
    |
    */

    if ($resource === 'health') {

        out([
            'ok' => true,
            'app' => APP_NAME,
            'php' => PHP_VERSION,
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EXERCISES
    |--------------------------------------------------------------------------
    */

    if ($resource === 'exercises') {

        /*
        |--------------------------------------------------------------------------
        | GET /api/exercises
        |--------------------------------------------------------------------------
        */

        if ($method === 'GET') {

            $q = trim(
                (string)($_GET['q'] ?? '')
            );

            if ($q !== '') {

                $stmt = $pdo->prepare(
                    "
                    SELECT
                        id,
                        name,
                        muscle_group,
                        exercise_type,
                        default_rest_seconds,
                        default_duration_seconds,
                        notes,
                        is_archived,
                        created_at,
                        updated_at
                    FROM exercises
                    WHERE is_archived = 0
                    AND (
                        name LIKE ?
                        OR muscle_group LIKE ?
                    )
                    ORDER BY name ASC
                    "
                );

                $search = '%' . $q . '%';

                $stmt->execute([
                    $search,
                    $search
                ]);

            } else {

                $stmt = $pdo->query(
                    "
                    SELECT
                        id,
                        name,
                        muscle_group,
                        exercise_type,
                        default_rest_seconds,
                        default_duration_seconds,
                        notes,
                        is_archived,
                        created_at,
                        updated_at
                    FROM exercises
                    WHERE is_archived = 0
                    ORDER BY name ASC
                    "
                );
            }

            out(
                $stmt->fetchAll()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | POST /api/exercises
        |--------------------------------------------------------------------------
        */

        if ($method === 'POST') {

            $data = input();

            $name = trim(
                (string)requireField(
                    $data,
                    'name'
                )
            );

            $muscleGroup = trim(
                (string)(
                    $data['muscle_group']
                    ?? 'Inne'
                )
            );

            $exerciseType =
                $data['exercise_type']
                ?? 'strength';

            $notes = trim(
                (string)(
                    $data['notes']
                    ?? ''
                )
            );

            $defaultRestSeconds = max(0, min(3600, (int)($data['default_rest_seconds'] ?? 90)));
            $defaultDurationSeconds = max(1, min(3600, (int)($data['default_duration_seconds'] ?? 45)));

            /*
            |--------------------------------------------------------------------------
            | Walidacja typu ćwiczenia
            |--------------------------------------------------------------------------
            */

            $allowedTypes = [
                'strength',
                'bodyweight',
                'time'
            ];

            if (
                !in_array(
                    $exerciseType,
                    $allowedTypes,
                    true
                )
            ) {

                out([
                    'error' =>
                        'Nieprawidłowy typ ćwiczenia.'
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | INSERT
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                "
                INSERT INTO exercises
                (
                    name,
                    muscle_group,
                    exercise_type,
                    default_rest_seconds,
                    default_duration_seconds,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?)
                "
            );

            $stmt->execute([
                $name,
                $muscleGroup,
                $exerciseType,
                $defaultRestSeconds,
                $defaultDurationSeconds,
                $notes
            ]);

            out([
                'id' =>
                    (int)$pdo->lastInsertId(),

                'message' =>
                    'Ćwiczenie zostało dodane.'
            ], 201);
        }

        /*
        |--------------------------------------------------------------------------
        | PUT /api/exercises/{id}
        |--------------------------------------------------------------------------
        */

        if (
            $method === 'PUT' &&
            isset($parts[1])
        ) {

            $id = (int)$parts[1];

            $data = input();

            $name = trim(
                (string)requireField(
                    $data,
                    'name'
                )
            );

            $muscleGroup = trim(
                (string)(
                    $data['muscle_group']
                    ?? 'Inne'
                )
            );

            $exerciseType =
                $data['exercise_type']
                ?? 'strength';

            $notes = trim(
                (string)(
                    $data['notes']
                    ?? ''
                )
            );

            $defaultRestSeconds = max(0, min(3600, (int)($data['default_rest_seconds'] ?? 90)));
            $defaultDurationSeconds = max(1, min(3600, (int)($data['default_duration_seconds'] ?? 45)));

            $allowedTypes = [
                'strength',
                'bodyweight',
                'time'
            ];

            if (
                !in_array(
                    $exerciseType,
                    $allowedTypes,
                    true
                )
            ) {

                out([
                    'error' =>
                        'Nieprawidłowy typ ćwiczenia.'
                ], 422);
            }

            $stmt = $pdo->prepare(
                "
                UPDATE exercises
                SET
                    name = ?,
                    muscle_group = ?,
                    exercise_type = ?,
                    default_rest_seconds = ?,
                    default_duration_seconds = ?,
                    notes = ?
                WHERE id = ?
                "
            );

            $stmt->execute([
                $name,
                $muscleGroup,
                $exerciseType,
                $defaultRestSeconds,
                $defaultDurationSeconds,
                $notes,
                $id
            ]);

            out([
                'message' =>
                    'Ćwiczenie zostało zaktualizowane.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/exercises/{id}
        |--------------------------------------------------------------------------
        |
        | Nie usuwamy fizycznie ćwiczenia.
        | Robimy archiwizację, żeby nie rozwalić historii.
        |
        */

        if (
            $method === 'DELETE' &&
            isset($parts[1])
        ) {

            $id = (int)$parts[1];

            $stmt = $pdo->prepare(
                "
                UPDATE exercises
                SET is_archived = 1
                WHERE id = ?
                "
            );

            $stmt->execute([
                $id
            ]);

            out([
                'message' =>
                    'Ćwiczenie zostało usunięte.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WORKOUT TEMPLATES
    |--------------------------------------------------------------------------
    */

    if ($resource === 'workout-templates') {

        /*
        |--------------------------------------------------------------------------
        | GET
        |--------------------------------------------------------------------------
        */

        if ($method === 'GET') {

            $stmt = $pdo->query(
                "
                SELECT *
                FROM workout_templates
                WHERE is_archived = 0
                ORDER BY name ASC
                "
            );

            $templates =
                $stmt->fetchAll();

            foreach (
                $templates as &$template
            ) {

                $exerciseStmt =
                    $pdo->prepare(
                        "
                        SELECT
                            wte.*,
                            e.name AS exercise_name,
                            e.muscle_group,
                            e.exercise_type
                        FROM workout_template_exercises wte
                        JOIN exercises e
                            ON e.id = wte.exercise_id
                        WHERE
                            wte.workout_template_id = ?
                        ORDER BY
                            wte.position ASC
                        "
                    );

                $exerciseStmt->execute([
                    $template['id']
                ]);

                $template['exercises'] =
                    $exerciseStmt->fetchAll();
            }

            unset($template);

            out($templates);
        }

        /*
        |--------------------------------------------------------------------------
        | POST
        |--------------------------------------------------------------------------
        */

        if ($method === 'POST') {

            $data = input();

            $name = trim(
                (string)requireField(
                    $data,
                    'name'
                )
            );

            $description = trim(
                (string)(
                    $data['description']
                    ?? ''
                )
            );

            $color =
                $data['color']
                ?? null;

            $exercises =
                $data['exercises']
                ?? [];

            $pdo->beginTransaction();

            try {

                /*
                |--------------------------------------------------------------------------
                | Tworzenie treningu
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    "
                    INSERT INTO workout_templates
                    (
                        name,
                        description,
                        color
                    )
                    VALUES (?, ?, ?)
                    "
                );

                $stmt->execute([
                    $name,
                    $description,
                    $color
                ]);

                $templateId =
                    (int)$pdo->lastInsertId();

                /*
                |--------------------------------------------------------------------------
                | Ćwiczenia treningu
                |--------------------------------------------------------------------------
                */

                foreach (
                    $exercises as $position => $exercise
                ) {

                    $stmt =
                        $pdo->prepare(
                            "
                            INSERT INTO workout_template_exercises
                            (
                                workout_template_id,
                                exercise_id,
                                position,
                                sets_count,
                                min_reps,
                                max_reps,
                                target_rir,
                                rest_seconds,
                                tempo,
                                notes
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                            "
                        );

                    $stmt->execute([
                        $templateId,

                        (int)(
                            $exercise['exercise_id']
                            ?? 0
                        ),

                        $position + 1,

                        (int)(
                            $exercise['sets_count']
                            ?? 3
                        ),

                        $exercise['min_reps']
                            ?? null,

                        $exercise['max_reps']
                            ?? null,

                        $exercise['target_rir']
                            ?? null,

                        (int)(
                            $exercise['rest_seconds']
                            ?? 90
                        ),

                        $exercise['tempo']
                            ?? null,

                        $exercise['notes']
                            ?? null
                    ]);
                }

                $pdo->commit();

                out([
                    'id' => $templateId,
                    'message' =>
                        'Trening został utworzony.'
                ], 201);

            } catch (Throwable $e) {

                $pdo->rollBack();

                throw $e;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PUT
        |--------------------------------------------------------------------------
        */

        if (
            $method === 'PUT' &&
            isset($parts[1])
        ) {

            $templateId =
                (int)$parts[1];

            $data = input();

            $name = trim(
                (string)requireField(
                    $data,
                    'name'
                )
            );

            $description = trim(
                (string)(
                    $data['description']
                    ?? ''
                )
            );

            $color =
                $data['color']
                ?? null;

            $exercises =
                $data['exercises']
                ?? [];

            $pdo->beginTransaction();

            try {

                /*
                |--------------------------------------------------------------------------
                | Aktualizacja treningu
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    "
                    UPDATE workout_templates
                    SET
                        name = ?,
                        description = ?,
                        color = ?
                    WHERE id = ?
                    "
                );

                $stmt->execute([
                    $name,
                    $description,
                    $color,
                    $templateId
                ]);

                /*
                |--------------------------------------------------------------------------
                | Usuwamy stare ćwiczenia
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    "
                    DELETE FROM workout_template_exercises
                    WHERE workout_template_id = ?
                    "
                );

                $stmt->execute([
                    $templateId
                ]);

                /*
                |--------------------------------------------------------------------------
                | Dodajemy aktualne ćwiczenia
                |--------------------------------------------------------------------------
                */

                foreach (
                    $exercises as $position => $exercise
                ) {

                    $stmt =
                        $pdo->prepare(
                            "
                            INSERT INTO workout_template_exercises
                            (
                                workout_template_id,
                                exercise_id,
                                position,
                                sets_count,
                                min_reps,
                                max_reps,
                                target_rir,
                                rest_seconds,
                                tempo,
                                notes
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                            "
                        );

                    $stmt->execute([
                        $templateId,

                        (int)(
                            $exercise['exercise_id']
                            ?? 0
                        ),

                        $position + 1,

                        (int)(
                            $exercise['sets_count']
                            ?? 3
                        ),

                        $exercise['min_reps']
                            ?? null,

                        $exercise['max_reps']
                            ?? null,

                        $exercise['target_rir']
                            ?? null,

                        (int)(
                            $exercise['rest_seconds']
                            ?? 90
                        ),

                        $exercise['tempo']
                            ?? null,

                        $exercise['notes']
                            ?? null
                    ]);
                }

                $pdo->commit();

                out([
                    'message' =>
                        'Trening został zaktualizowany.'
                ]);

            } catch (Throwable $e) {

                $pdo->rollBack();

                throw $e;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        if (
            $method === 'DELETE' &&
            isset($parts[1])
        ) {

            $templateId =
                (int)$parts[1];

            $stmt = $pdo->prepare(
                "
                UPDATE workout_templates
                SET is_archived = 1
                WHERE id = ?
                "
            );

            $stmt->execute([
                $templateId
            ]);

            out([
                'message' =>
                    'Trening został usunięty.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CALENDAR
    |--------------------------------------------------------------------------
    */

    if ($resource === 'calendar') {

        /*
        |--------------------------------------------------------------------------
        | GET
        |--------------------------------------------------------------------------
        */

        if ($method === 'GET') {

            $from =
                $_GET['from']
                ?? date('Y-m-01');

            $to =
                $_GET['to']
                ?? date('Y-m-t');

            $stmt = $pdo->prepare(
                "
                SELECT
                    sw.*,
                    wt.name AS workout_name
                FROM scheduled_workouts sw
                LEFT JOIN workout_templates wt
                    ON wt.id =
                       sw.workout_template_id
                WHERE
                    sw.scheduled_date
                    BETWEEN ? AND ?
                ORDER BY
                    sw.scheduled_date ASC
                "
            );

            $stmt->execute([
                $from,
                $to
            ]);

            out(
                $stmt->fetchAll()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | POST
        |--------------------------------------------------------------------------
        */

        if ($method === 'POST') {

            $data = input();

            $date =
                requireField(
                    $data,
                    'scheduled_date'
                );

            $status =
                $data['status']
                ?? 'planned';

            $templateId =
                !empty(
                    $data['workout_template_id']
                )
                ? (int)$data['workout_template_id']
                : null;

            $allowedStatuses = [
                'planned',
                'completed',
                'skipped',
                'rest'
            ];

            if (
                !in_array(
                    $status,
                    $allowedStatuses,
                    true
                )
            ) {

                out([
                    'error' =>
                        'Nieprawidłowy status.'
                ], 422);
            }

            $stmt = $pdo->prepare(
                "
                INSERT INTO scheduled_workouts
                (
                    workout_template_id,
                    scheduled_date,
                    status,
                    notes
                )
                VALUES (?, ?, ?, ?)

                ON DUPLICATE KEY UPDATE
                    workout_template_id =
                        VALUES(workout_template_id),

                    status =
                        VALUES(status),

                    notes =
                        VALUES(notes)
                "
            );

            $stmt->execute([
                $templateId,
                $date,
                $status,
                trim(
                    (string)(
                        $data['notes']
                        ?? ''
                    )
                )
            ]);

            out([
                'message' =>
                    'Kalendarz został zaktualizowany.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        if (
            $method === 'DELETE' &&
            isset($parts[1])
        ) {

            $stmt = $pdo->prepare(
                "
                DELETE FROM scheduled_workouts
                WHERE id = ?
                "
            );

            $stmt->execute([
                (int)$parts[1]
            ]);

            out([
                'message' =>
                    'Wpis został usunięty.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TODAY
    |--------------------------------------------------------------------------
    */

    if ($resource === 'today') {

        $date =
            $_GET['date']
            ?? date('Y-m-d');

        $stmt = $pdo->prepare(
            "
            SELECT
                sw.*,
                wt.name AS workout_name,
                wt.description AS workout_description
            FROM scheduled_workouts sw
            LEFT JOIN workout_templates wt
                ON wt.id =
                   sw.workout_template_id
            WHERE
                sw.scheduled_date = ?
            LIMIT 1
            "
        );

        $stmt->execute([
            $date
        ]);

        $row =
            $stmt->fetch();

        /*
        |--------------------------------------------------------------------------
        | Brak treningu
        |--------------------------------------------------------------------------
        */

        if (!$row) {

            out([
                'date' => $date,
                'status' => 'none',
                'workout' => null,
                'exercises' => []
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Ćwiczenia treningu
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $row['workout_template_id']
            )
        ) {

            $stmt =
                $pdo->prepare(
                    "
                    SELECT
                        wte.*,
                        e.name AS exercise_name,
                        e.muscle_group,
                        e.exercise_type
                    FROM workout_template_exercises wte
                    JOIN exercises e
                        ON e.id =
                           wte.exercise_id
                    WHERE
                        wte.workout_template_id = ?
                    ORDER BY
                        wte.position ASC
                    "
                );

            $stmt->execute([
                $row['workout_template_id']
            ]);

            $row['exercises'] =
                $stmt->fetchAll();

        } else {

            $row['exercises'] = [];
        }

        out($row);
    }

    /*
    |--------------------------------------------------------------------------
    | WORKOUT SESSIONS - aktywny trening, serie i historia
    |--------------------------------------------------------------------------
    */
    if ($resource === 'workout-sessions') {
        if ($method === 'GET' && !isset($parts[1])) {
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 30)));
            $stmt = $pdo->query("
                SELECT ws.*, COUNT(DISTINCT wse.id) AS exercise_count,
                    COUNT(wset.id) AS set_count
                FROM workout_sessions ws
                LEFT JOIN workout_session_exercises wse ON wse.workout_session_id = ws.id
                LEFT JOIN workout_sets wset ON wset.workout_session_exercise_id = wse.id
                GROUP BY ws.id
                ORDER BY ws.started_at DESC
                LIMIT " . $limit
            );
            out($stmt->fetchAll());
        }

        if ($method === 'POST' && !isset($parts[1])) {
            $data = input();
            $templateId = !empty($data['workout_template_id']) ? (int)$data['workout_template_id'] : null;
            $scheduledId = !empty($data['scheduled_workout_id']) ? (int)$data['scheduled_workout_id'] : null;
            $name = trim((string)($data['workout_name_snapshot'] ?? 'Trening'));
            if ($templateId === null && $scheduledId !== null) {
                $lookup = $pdo->prepare("SELECT workout_template_id FROM scheduled_workouts WHERE id = ?");
                $lookup->execute([$scheduledId]);
                $found = $lookup->fetch();
                if ($found && $found['workout_template_id']) $templateId = (int)$found['workout_template_id'];
            }
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO workout_sessions (scheduled_workout_id, workout_template_id, workout_name_snapshot, started_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$scheduledId, $templateId, $name]);
                $sessionId = (int)$pdo->lastInsertId();
                if ($templateId !== null) {
                    $stmt = $pdo->prepare("
                        INSERT INTO workout_session_exercises
                        (workout_session_id, exercise_id, exercise_name_snapshot, position, sets_target, min_reps_target, max_reps_target, target_rir, rest_seconds, tempo)
                        SELECT ?, e.id, e.name, wte.position, wte.sets_count, wte.min_reps, wte.max_reps, wte.target_rir, wte.rest_seconds, wte.tempo
                        FROM workout_template_exercises wte
                        JOIN exercises e ON e.id = wte.exercise_id
                        WHERE wte.workout_template_id = ?
                        ORDER BY wte.position
                    ");
                    $stmt->execute([$sessionId, $templateId]);
                }
                $pdo->commit();
                out(['id' => $sessionId, 'message' => 'Trening rozpoczęty.'], 201);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        if ($method === 'PUT' && isset($parts[1])) {
            $sessionId = (int)$parts[1];
            $stmt = $pdo->prepare("UPDATE workout_sessions SET ended_at = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()), notes = COALESCE(?, notes) WHERE id = ? AND ended_at IS NULL");
            $data = input();
            $stmt->execute([$data['notes'] ?? null, $sessionId]);
            $pdo->prepare("UPDATE scheduled_workouts SET status = 'completed' WHERE id = (SELECT scheduled_workout_id FROM workout_sessions WHERE id = ?)")->execute([$sessionId]);
            out(['message' => 'Trening zakończony.']);
        }

        if ($method === 'POST' && isset($parts[1]) && ($parts[2] ?? '') === 'sets') {
            $sessionId = (int)$parts[1];
            $data = input();
            $exerciseSessionId = (int)requireField($data, 'workout_session_exercise_id');
            $check = $pdo->prepare("SELECT id FROM workout_session_exercises WHERE id = ? AND workout_session_id = ?");
            $check->execute([$exerciseSessionId, $sessionId]);
            if (!$check->fetch()) out(['error' => 'Ćwiczenie nie należy do tego treningu.'], 422);
            $setNumber = max(1, (int)requireField($data, 'set_number'));
            $weight = max(0, (float)($data['weight_kg'] ?? 0));
            $reps = max(0, (int)requireField($data, 'reps'));
            $rir = isset($data['rir']) && $data['rir'] !== '' ? (float)$data['rir'] : null;
            $stmt = $pdo->prepare("INSERT INTO workout_sets (workout_session_exercise_id, set_number, weight_kg, reps, rir) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE weight_kg = VALUES(weight_kg), reps = VALUES(reps), rir = VALUES(rir), completed_at = NOW()");
            $stmt->execute([$exerciseSessionId, $setNumber, $weight, $reps, $rir]);
            out(['message' => 'Seria zapisana.'], 201);
        }

        if ($method === 'GET' && isset($parts[1])) {
            $sessionId = (int)$parts[1];
            $stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW())) AS elapsed_seconds FROM workout_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            if (!$session) out(['error' => 'Nie znaleziono treningu.'], 404);
            $stmt = $pdo->prepare("SELECT wse.*, COALESCE(e.muscle_group, '') AS muscle_group, COALESCE(e.exercise_type, 'strength') AS exercise_type FROM workout_session_exercises wse LEFT JOIN exercises e ON e.id = wse.exercise_id WHERE wse.workout_session_id = ? ORDER BY wse.position");
            $stmt->execute([$sessionId]);
            $session['exercises'] = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT wset.* FROM workout_sets wset JOIN workout_session_exercises wse ON wse.id = wset.workout_session_exercise_id WHERE wse.workout_session_id = ? ORDER BY wse.position, wset.set_number");
            $stmt->execute([$sessionId]);
            $session['sets'] = $stmt->fetchAll();

            // Ostatnie wyniki tego samego planu (dla podpowiedzi przy kolejnym treningu).
            foreach ($session['exercises'] as &$exercise) {
                $previousStmt = $pdo->prepare("
                    SELECT ws.id AS session_id, ws.started_at, wset.set_number, wset.weight_kg, wset.reps, wset.rir
                    FROM workout_sessions ws
                    JOIN workout_session_exercises prev_ex ON prev_ex.workout_session_id = ws.id
                    JOIN workout_sets wset ON wset.workout_session_exercise_id = prev_ex.id
                    WHERE prev_ex.exercise_id = ?
                      AND ws.id <> ?
                      AND ws.ended_at IS NOT NULL
                      AND ws.workout_template_id <=> ?
                      AND ws.started_at = (
                          SELECT MAX(ws2.started_at)
                          FROM workout_sessions ws2
                          JOIN workout_session_exercises pe2 ON pe2.workout_session_id = ws2.id
                          WHERE pe2.exercise_id = prev_ex.exercise_id
                            AND ws2.id <> ?
                            AND ws2.ended_at IS NOT NULL
                            AND ws2.workout_template_id <=> ?
                      )
                    ORDER BY wset.set_number
                ");
                $previousStmt->execute([$exercise['exercise_id'], $sessionId, $session['workout_template_id'], $sessionId, $session['workout_template_id']]);
                $exercise['previous_sets'] = $previousStmt->fetchAll();
            }
            unset($exercise);
            out($session);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NUTRITION
    |--------------------------------------------------------------------------
    */

    if ($resource === 'nutrition') {

        if ($method === 'GET') {
            $from = (string)($_GET['from'] ?? date('Y-m-01'));
            $to = (string)($_GET['to'] ?? date('Y-m-t'));
            $validFrom = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
            $validTo = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
            if (!$validFrom || !$validTo || $validFrom->format('Y-m-d') !== $from || $validTo->format('Y-m-d') !== $to || $from > $to) {
                out(['error' => 'Nieprawidłowy zakres dat.'], 422);
            }
            $stmt = $pdo->prepare("SELECT id, entry_date, calories, protein_g, carbs_g, fats_g, burned_calories, notes FROM nutrition_entries WHERE entry_date BETWEEN ? AND ? ORDER BY entry_date ASC");
            $stmt->execute([$from, $to]);
            out($stmt->fetchAll());
        }

        if ($method === 'POST') {
            $data = input();
            $date = (string)requireField($data, 'entry_date');
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                out(['error' => 'Nieprawidłowa data wpisu.'], 422);
            }
            $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw')))->format('Y-m-d');
            if ($date > $today) {
                out(['error' => 'Nie można zapisywać danych z przyszłych dat.'], 422);
            }

            $check = $pdo->prepare("SELECT id FROM nutrition_entries WHERE entry_date = ? LIMIT 1");
            $check->execute([$date]);
            $existingId = $check->fetchColumn();

            $values = [
                (int)($data['calories'] ?? 0),
                (float)($data['protein_g'] ?? 0),
                (float)($data['carbs_g'] ?? 0),
                (float)($data['fats_g'] ?? 0),
                isset($data['burned_calories']) && $data['burned_calories'] !== '' ? (int)$data['burned_calories'] : null,
                trim((string)($data['notes'] ?? ''))
            ];
            foreach (array_slice($values, 0, 5) as $value) {
                if ($value !== null && (!is_numeric($value) || $value < 0)) {
                    out(['error' => 'Wartości kalorii, makro i spalania nie mogą być ujemne.'], 422);
                }
            }

            if ($existingId !== false) {
                $stmt = $pdo->prepare("UPDATE nutrition_entries SET calories = ?, protein_g = ?, carbs_g = ?, fats_g = ?, steps = NULL, burned_calories = ?, notes = ? WHERE entry_date = ?");
                $stmt->execute([...$values, $date]);
                out(['message' => 'Wpis żywieniowy został zaktualizowany.', 'updated' => true, 'entry_date' => $date]);
            }

            $stmt = $pdo->prepare("INSERT INTO nutrition_entries (entry_date, calories, protein_g, carbs_g, fats_g, steps, burned_calories, notes) VALUES (?, ?, ?, ?, ?, NULL, ?, ?)");
            $stmt->execute([$date, ...$values]);
            out(['message' => 'Wpis żywieniowy został zapisany.', 'updated' => false, 'entry_date' => $date], 201);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MEASUREMENTS
    |--------------------------------------------------------------------------
    */

    if ($resource === 'measurements') {

        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM body_measurements ORDER BY measurement_date DESC");
            out($stmt->fetchAll());
        }

        if ($method === 'POST') {
            $data = input();
            $date = (string)requireField($data, 'measurement_date');
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                out(['error' => 'Nieprawidłowa data pomiaru.'], 422);
            }

            $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw')))->format('Y-m-d');
            $check = $pdo->prepare("SELECT * FROM body_measurements WHERE measurement_date = ?");
            $check->execute([$date]);
            $existing = $check->fetch();

            if ($date > $today) {
                out(['error' => 'Nie można zapisywać pomiarów z przyszłych dat.'], 422);
            }

            $circumferenceFields = ['waist_cm', 'chest_cm', 'arm_cm', 'thigh_cm'];
            $hasCircumference = false;
            foreach ($circumferenceFields as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    $hasCircumference = true;
                    break;
                }
            }
            if ($hasCircumference && (int)$parsedDate->format('N') !== 5) {
                out(['error' => 'Obwody można zapisywać wyłącznie w piątek.'], 422);
            }

            $weight = $data['weight_kg'] ?? ($existing['weight_kg'] ?? null);
            if ($weight === null || $weight === '' || !is_numeric($weight) || (float)$weight < 20 || (float)$weight > 400) {
                out(['error' => 'Masa ciała jest wymagana. Podaj prawidłową wartość w kg.'], 422);
            }

            $values = [];
            foreach ($circumferenceFields as $field) {
                $value = $data[$field] ?? ($existing[$field] ?? null);
                if ($value !== null && $value !== '' && (!is_numeric($value) || (float)$value <= 0)) {
                    out(['error' => 'Wartość obwodu musi być większa od zera.'], 422);
                }
                $values[$field] = ($value === '') ? null : $value;
            }

            $stmt = $pdo->prepare("
                INSERT INTO body_measurements
                (measurement_date, weight_kg, waist_cm, chest_cm, arm_cm, thigh_cm)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    weight_kg = VALUES(weight_kg),
                    waist_cm = VALUES(waist_cm),
                    chest_cm = VALUES(chest_cm),
                    arm_cm = VALUES(arm_cm),
                    thigh_cm = VALUES(thigh_cm)
            ");
            $stmt->execute([$date, $weight, $values['waist_cm'], $values['chest_cm'], $values['arm_cm'], $values['thigh_cm']]);
            out(['message' => 'Pomiar został zapisany.', 'measurement_date' => $date, 'updated' => (bool)$existing]);
        }
    }

    if ($resource === 'settings') {

        /*
        |--------------------------------------------------------------------------
        | GET
        |--------------------------------------------------------------------------
        */

        if ($method === 'GET') {

            $stmt = $pdo->query(
                "
                SELECT
                    setting_key,
                    setting_value
                FROM app_settings
                ORDER BY setting_key
                "
            );

            $rows =
                $stmt->fetchAll();

            $settings = [];

            foreach ($rows as $row) {

                $settings[
                    $row['setting_key']
                ] =
                    $row['setting_value'];
            }

            out($settings);
        }

        /*
        |--------------------------------------------------------------------------
        | POST
        |--------------------------------------------------------------------------
        */

        if ($method === 'POST') {

            $data = input();

            $stmt = $pdo->prepare(
                "
                INSERT INTO app_settings
                (
                    setting_key,
                    setting_value
                )
                VALUES (?, ?)

                ON DUPLICATE KEY UPDATE
                    setting_value =
                        VALUES(setting_value)
                "
            );

            foreach (
                $data as $key => $value
            ) {

                if (
                    is_array($value) ||
                    is_object($value)
                ) {

                    $value =
                        json_encode(
                            $value,
                            JSON_UNESCAPED_UNICODE
                        );
                }

                $stmt->execute([
                    $key,
                    (string)$value
                ]);
            }

            out([
                'message' =>
                    'Ustawienia zostały zapisane.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Nie znaleziono endpointu
    |--------------------------------------------------------------------------
    */

    out([
        'error' =>
            'Nie znaleziono endpointu.',
        'resource' =>
            $resource,
        'path' =>
            $path
    ], 404);

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Błąd serwera
    |--------------------------------------------------------------------------
    */

    out([
        'error' =>
            'Błąd serwera.',

        'details' =>
            $e->getMessage()
    ], 500);
}
