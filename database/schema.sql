CREATE DATABASE IF NOT EXISTS fittrack
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fittrack;

CREATE TABLE exercises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    muscle_group VARCHAR(80) NULL,
    exercise_type ENUM('strength','bodyweight','time') NOT NULL DEFAULT 'strength',
    default_rest_seconds INT UNSIGNED NOT NULL DEFAULT 90,
    notes TEXT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(name), INDEX(muscle_group)
) ENGINE=InnoDB;

CREATE TABLE workout_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    color VARCHAR(30) NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE workout_template_exercises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_template_id INT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL,
    sets_count INT UNSIGNED NOT NULL DEFAULT 3,
    min_reps INT UNSIGNED NULL,
    max_reps INT UNSIGNED NULL,
    target_rir DECIMAL(3,1) NULL,
    rest_seconds INT UNSIGNED NOT NULL DEFAULT 90,
    tempo VARCHAR(20) NULL,
    notes TEXT NULL,
    FOREIGN KEY (workout_template_id) REFERENCES workout_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_template_position (workout_template_id, position)
) ENGINE=InnoDB;

CREATE TABLE scheduled_workouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_template_id INT UNSIGNED NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('planned','completed','skipped','rest') NOT NULL DEFAULT 'planned',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workout_template_id) REFERENCES workout_templates(id) ON DELETE SET NULL,
    UNIQUE KEY uq_scheduled_date (scheduled_date),
    INDEX(scheduled_date)
) ENGINE=InnoDB;

CREATE TABLE workout_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scheduled_workout_id INT UNSIGNED NULL,
    workout_template_id INT UNSIGNED NULL,
    workout_name_snapshot VARCHAR(150) NOT NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    duration_seconds INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scheduled_workout_id) REFERENCES scheduled_workouts(id) ON DELETE SET NULL,
    FOREIGN KEY (workout_template_id) REFERENCES workout_templates(id) ON DELETE SET NULL,
    INDEX(started_at)
) ENGINE=InnoDB;

CREATE TABLE workout_session_exercises (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_session_id BIGINT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NULL,
    exercise_name_snapshot VARCHAR(150) NOT NULL,
    position INT UNSIGNED NOT NULL,
    sets_target INT UNSIGNED NULL,
    min_reps_target INT UNSIGNED NULL,
    max_reps_target INT UNSIGNED NULL,
    target_rir DECIMAL(3,1) NULL,
    rest_seconds INT UNSIGNED NULL,
    tempo VARCHAR(20) NULL,
    FOREIGN KEY (workout_session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE workout_sets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_session_exercise_id BIGINT UNSIGNED NOT NULL,
    set_number INT UNSIGNED NOT NULL,
    weight_kg DECIMAL(7,2) NOT NULL DEFAULT 0,
    reps INT UNSIGNED NOT NULL,
    rir DECIMAL(3,1) NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workout_session_exercise_id) REFERENCES workout_session_exercises(id) ON DELETE CASCADE,
    UNIQUE KEY uq_set_number (workout_session_exercise_id, set_number)
) ENGINE=InnoDB;

CREATE TABLE nutrition_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    calories INT UNSIGNED NOT NULL DEFAULT 0,
    protein_g DECIMAL(7,2) NOT NULL DEFAULT 0,
    carbs_g DECIMAL(7,2) NOT NULL DEFAULT 0,
    fats_g DECIMAL(7,2) NOT NULL DEFAULT 0,
    steps INT UNSIGNED NULL,
    burned_calories INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nutrition_date (entry_date)
) ENGINE=InnoDB;

CREATE TABLE body_measurements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    measurement_date DATE NOT NULL,
    weight_kg DECIMAL(6,2) NULL,
    waist_cm DECIMAL(6,2) NULL,
    chest_cm DECIMAL(6,2) NULL,
    arm_cm DECIMAL(6,2) NULL,
    thigh_cm DECIMAL(6,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_measurement_date (measurement_date)
) ENGINE=InnoDB;

CREATE TABLE personal_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exercise_id INT UNSIGNED NOT NULL,
    best_weight_kg DECIMAL(7,2) NULL,
    best_reps INT UNSIGNED NULL,
    best_weight_date DATE NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE,
    UNIQUE KEY uq_pr_exercise (exercise_id)
) ENGINE=InnoDB;

CREATE TABLE app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO exercises (name, muscle_group, exercise_type) VALUES
('Przysiad ze sztangą','Nogi','strength'),
('Wyciskanie sztangi leżąc','Klatka','strength'),
('Wiosłowanie sztangą','Plecy','strength'),
('Romanian Deadlift','Nogi','strength'),
('OHP','Barki','strength'),
('Leg Press','Nogi','strength'),
('Wyciskanie hantli na skosie dodatnim','Klatka','strength'),
('Lat Pulldown','Plecy','strength'),
('Leg Curl','Nogi','strength'),
('Lateral Raise','Barki','strength'),
('Overhead Cable Triceps Extension','Triceps','strength'),
('Supinating Dumbbell Curl','Biceps','strength'),
('Leg Raise','Brzuch','bodyweight'),
('Hack Squat','Nogi','strength'),
('Flat Dumbbell Press','Klatka','strength'),
('Seated Cable Row','Plecy','strength'),
('Hip Thrust','Pośladki','strength'),
('Cable Triceps Extension','Triceps','strength'),
('Biceps Curl','Biceps','strength'),
('Cable Crunch','Brzuch','strength'),
('Plank','Brzuch','time');

INSERT INTO app_settings (setting_key, setting_value) VALUES
('profile_name','Krzysiek'),
('goal_weight_kg','90'),
('daily_calories','2500'),
('daily_protein_g','190'),
('daily_carbs_g','265'),
('daily_fats_g','70')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
