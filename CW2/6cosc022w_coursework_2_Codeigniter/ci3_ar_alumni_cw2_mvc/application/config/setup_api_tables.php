<?php
if (!defined('BASEPATH') && !defined('AR_ALUMNI_API_SETUP_RUNNER')) {
    exit('No direct script access allowed');
}

$api_setup = [
    'timezone' => 'Asia/Colombo',

    'db_file' => __DIR__ . '/../data/ar_alumni.sqlite',

    'tables' => [

        'api_keys' => "
            CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                developer_user_id INTEGER NOT NULL,
                key_name TEXT NOT NULL,
                key_prefix TEXT NOT NULL,
                key_hash TEXT NOT NULL UNIQUE,
                scopes TEXT NOT NULL DEFAULT 'read:alumni',
                revoked_at TEXT,
                last_used_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (developer_user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'api_usage_logs' => "
            CREATE TABLE IF NOT EXISTS api_usage_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                api_key_id INTEGER,
                endpoint TEXT NOT NULL,
                http_method TEXT NOT NULL,
                status_code INTEGER NOT NULL,
                ip_address TEXT,
                user_agent TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL
            );
        "
    ],

    'indexes' => [
        "CREATE INDEX IF NOT EXISTS idx_api_keys_developer ON api_keys(developer_user_id);",
        "CREATE INDEX IF NOT EXISTS idx_api_keys_hash ON api_keys(key_hash);",
        "CREATE INDEX IF NOT EXISTS idx_api_keys_revoked ON api_keys(revoked_at);",
        "CREATE INDEX IF NOT EXISTS idx_api_usage_key ON api_usage_logs(api_key_id);",
        "CREATE INDEX IF NOT EXISTS idx_api_usage_endpoint ON api_usage_logs(endpoint);",
        "CREATE INDEX IF NOT EXISTS idx_api_usage_created ON api_usage_logs(created_at);"
    ]
];