<?php
if (!defined('BASEPATH') && !defined('CW2_STAFF_AUTH_SETUP_RUNNER')) {
    exit('No direct script access allowed');
}

$staff_auth_setup = [
    'timezone' => 'Asia/Colombo',

    // Use the same SQLite file as CW1 so your existing SqliteDB library still works.
    'db_file' => __DIR__ . '/../data/ar_alumni.sqlite',

    'demo_password' => 'Password123!',

    'tables' => [

        'staff_users' => "
            CREATE TABLE IF NOT EXISTS staff_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE COLLATE NOCASE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'analyst' CHECK(role IN ('admin', 'analyst')),
                department TEXT,
                job_title TEXT,
                email_verified INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                last_login_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
        ",

        'staff_auth_tokens' => "
            CREATE TABLE IF NOT EXISTS staff_auth_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                staff_user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL,
                token_type TEXT NOT NULL CHECK(token_type IN ('email_verification', 'password_reset')),
                expires_at TEXT NOT NULL,
                used_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (staff_user_id) REFERENCES staff_users(id) ON DELETE CASCADE
            );
        ",

        'staff_email_outbox' => "
            CREATE TABLE IF NOT EXISTS staff_email_outbox (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                to_email TEXT NOT NULL,
                subject TEXT NOT NULL,
                body TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                created_at TEXT NOT NULL
            );
        ",

        'client_settings' => "
            CREATE TABLE IF NOT EXISTS client_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT,
                description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
        "
    ],

    'indexes' => [
        "CREATE INDEX IF NOT EXISTS idx_staff_users_email ON staff_users(email);",
        "CREATE INDEX IF NOT EXISTS idx_staff_tokens_user ON staff_auth_tokens(staff_user_id);",
        "CREATE INDEX IF NOT EXISTS idx_staff_tokens_hash ON staff_auth_tokens(token_hash);",
        "CREATE INDEX IF NOT EXISTS idx_staff_outbox_created ON staff_email_outbox(created_at);"
    ],

    'demo_staff_users' => [
        [
            'full_name' => 'University Admin',
            'email' => 'admin@eastminster.ac.uk',
            'role' => 'admin',
            'department' => 'Computer Science',
            'job_title' => 'University Analytics Administrator',
            'email_verified' => 1
        ],
        [
            'full_name' => 'Analytics Officer',
            'email' => 'analytics@eastminster.ac.uk',
            'role' => 'analyst',
            'department' => 'Academic Quality and Planning',
            'job_title' => 'Graduate Outcomes Analyst',
            'email_verified' => 1
        ]
    ],

    'default_settings' => [
        [
            'setting_key' => 'cw1_api_base_url',
            'setting_value' => 'http://localhost:8080/index.php/api/v1',
            'description' => 'Base URL of the CW1 Alumni Influencers API.'
        ],
        [
            'setting_key' => 'cw1_api_key',
            'setting_value' => '',
            'description' => 'Bearer API key generated from the CW1 developer dashboard.'
        ]
    ]
];