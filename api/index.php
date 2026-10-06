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
                    notes
                )
                VALUES (?, ?, ?, ?)
                "
            );

            $stmt->execute([
                $name,
                $muscleGroup,
                $exerciseType,
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
                    notes = ?
                WHERE id = ?
                "
            );

            $stmt->execute([
                $name,
                $muscleGroup,
                $exerciseType,
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
    | NUTRITION
    |--------------------------------------------------------------------------
    */

    if ($resource === 'nutrition') {

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
                SELECT *
                FROM nutrition_entries
                WHERE
                    entry_date
                    BETWEEN ? AND ?
                ORDER BY
                    entry_date ASC
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
                    'entry_date'
                );

            $stmt = $pdo->prepare(
                "
                INSERT INTO nutrition_entries
                (
                    entry_date,
                    calories,
                    protein_g,
                    carbs_g,
                    fats_g,
                    steps,
                    burned_calories,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)

                ON DUPLICATE KEY UPDATE
                    calories =
                        VALUES(calories),

                    protein_g =
                        VALUES(protein_g),

                    carbs_g =
                        VALUES(carbs_g),

                    fats_g =
                        VALUES(fats_g),

                    steps =
                        VALUES(steps),

                    burned_calories =
                        VALUES(burned_calories),

                    notes =
                        VALUES(notes)
                "
            );

            $stmt->execute([
                $date,

                (int)(
                    $data['calories']
                    ?? 0
                ),

                (float)(
                    $data['protein_g']
                    ?? 0
                ),

                (float)(
                    $data['carbs_g']
                    ?? 0
                ),

                (float)(
                    $data['fats_g']
                    ?? 0
                ),

                isset($data['steps'])
                    ? (int)$data['steps']
                    : null,

                isset(
                    $data['burned_calories']
                )
                    ? (int)$data['burned_calories']
                    : null,

                $data['notes']
                    ?? null
            ]);

            out([
                'message' =>
                    'Wpis żywieniowy został zapisany.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MEASUREMENTS
    |--------------------------------------------------------------------------
    */

    if ($resource === 'measurements') {

        /*
        |--------------------------------------------------------------------------
        | GET
        |--------------------------------------------------------------------------
        */

        if ($method === 'GET') {

            $stmt = $pdo->query(
                "
                SELECT *
                FROM body_measurements
                ORDER BY
                    measurement_date DESC
                "
            );

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
                    'measurement_date'
                );

            $stmt = $pdo->prepare(
                "
                INSERT INTO body_measurements
                (
                    measurement_date,
                    weight_kg,
                    waist_cm,
                    chest_cm,
                    arm_cm,
                    thigh_cm
                )
                VALUES (?, ?, ?, ?, ?, ?)

                ON DUPLICATE KEY UPDATE

                    weight_kg =
                        VALUES(weight_kg),

                    waist_cm =
                        VALUES(waist_cm),

                    chest_cm =
                        VALUES(chest_cm),

                    arm_cm =
                        VALUES(arm_cm),

                    thigh_cm =
                        VALUES(thigh_cm)
                "
            );

            $stmt->execute([
                $date,

                $data['weight_kg']
                    ?? null,

                $data['waist_cm']
                    ?? null,

                $data['chest_cm']
                    ?? null,

                $data['arm_cm']
                    ?? null,

                $data['thigh_cm']
                    ?? null
            ]);

            out([
                'message' =>
                    'Pomiar został zapisany.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */

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
