-- FitTrack migration: default rest duration for exercises
-- Run this ONCE in phpMyAdmin on the existing FitTrack database.
ALTER TABLE exercises
    ADD COLUMN default_rest_seconds INT UNSIGNED NOT NULL DEFAULT 90
    AFTER exercise_type;
