<?php
/*
|--------------------------------------------------------------------------
| AR Alumni SQLite Database Setup Config
|--------------------------------------------------------------------------
| This file stores the database path, table creation SQL, indexes, and demo users.
| It is loaded by the root setup_database.php runner file.
*/

if (!defined('BASEPATH') && !defined('AR_ALUMNI_SETUP_RUNNER')) {
    exit('No direct script access allowed');
}

$setup_database = [
    'timezone' => 'Asia/Colombo',

    'db_file' => __DIR__ . '/../data/ar_alumni.sqlite',

    'demo_password' => 'Password123!',

    'tables' => [

        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE COLLATE NOCASE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('developer', 'alumnus', 'student')),
                email_verified INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                last_login_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
        ",

        'auth_tokens' => "
            CREATE TABLE IF NOT EXISTS auth_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL,
                token_type TEXT NOT NULL CHECK(token_type IN ('email_verification', 'password_reset')),
                expires_at TEXT NOT NULL,
                used_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'email_outbox' => "
            CREATE TABLE IF NOT EXISTS email_outbox (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                to_email TEXT NOT NULL,
                subject TEXT NOT NULL,
                body TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                created_at TEXT NOT NULL
            );
        "
    ],

    'indexes' => [
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);",
        "CREATE INDEX IF NOT EXISTS idx_auth_tokens_user_id ON auth_tokens(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_auth_tokens_hash ON auth_tokens(token_hash);",
        "CREATE INDEX IF NOT EXISTS idx_email_outbox_created ON email_outbox(created_at);"
    ],

    'demo_users' => [
        [
            'full_name' => 'Demo Developer',
            'email' => 'developer@eastminster.ac.uk',
            'role' => 'developer',
            'email_verified' => 1
        ],
        [
            'full_name' => 'Demo Alumni',
            'email' => 'alumni@eastminster.ac.uk',
            'role' => 'alumnus',
            'email_verified' => 1
        ],
        [
            'full_name' => 'Unverified Alumni',
            'email' => 'unverified@eastminster.ac.uk',
            'role' => 'alumnus',
            'email_verified' => 0
        ],
        [
            'full_name' => 'Demo Student',
            'email' => 'student@eastminster.ac.uk',
            'role' => 'student',
            'email_verified' => 1
        ]
    ]
];