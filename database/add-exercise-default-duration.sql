-- FitTrack migration: default duration for timed exercises
-- Run this ONCE in phpMyAdmin on the existing FitTrack database.
-- Do not run again if the column already exists.
ALTER TABLE exercises
    ADD COLUMN default_duration_seconds INT UNSIGNED NOT NULL DEFAULT 45
    AFTER default_rest_seconds;
