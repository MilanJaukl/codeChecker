<?php
$database = new SQLite3(__DIR__ . '/code_reviews.db');

$database->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    repository TEXT,
    branch TEXT,
    commit_hash TEXT,
    commit_message TEXT,
    user_name TEXT,
    code TEXT,
    review TEXT,
    file_name TEXT,
    agent TEXT
)");
