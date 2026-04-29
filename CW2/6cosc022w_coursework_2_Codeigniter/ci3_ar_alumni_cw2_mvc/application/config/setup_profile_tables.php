<?php
if (!defined('BASEPATH') && !defined('AR_ALUMNI_PROFILE_SETUP_RUNNER')) {
    exit('No direct script access allowed');
}

$profile_setup = [
    'timezone' => 'Asia/Colombo',

    'db_file' => __DIR__ . '/../data/ar_alumni.sqlite',

    'tables' => [

        'profiles' => "
            CREATE TABLE IF NOT EXISTS profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                personal_info TEXT,
                headline TEXT,
                biography TEXT,
                linkedin_url TEXT,
                profile_image_path TEXT,
                profile_completion_percent INTEGER NOT NULL DEFAULT 0,
                appearance_count_month INTEGER NOT NULL DEFAULT 0,
                appearance_month TEXT,
                is_public INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'profile_degrees' => "
            CREATE TABLE IF NOT EXISTS profile_degrees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                degree_name TEXT NOT NULL,
                university_name TEXT NOT NULL,
                official_url TEXT NOT NULL,
                completion_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'profile_certifications' => "
            CREATE TABLE IF NOT EXISTS profile_certifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                certification_name TEXT NOT NULL,
                provider_name TEXT NOT NULL,
                official_url TEXT NOT NULL,
                completion_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'profile_licences' => "
            CREATE TABLE IF NOT EXISTS profile_licences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                licence_name TEXT NOT NULL,
                awarding_body TEXT NOT NULL,
                official_url TEXT NOT NULL,
                completion_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'profile_courses' => "
            CREATE TABLE IF NOT EXISTS profile_courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                course_name TEXT NOT NULL,
                provider_name TEXT NOT NULL,
                official_url TEXT NOT NULL,
                completion_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'employment_history' => "
            CREATE TABLE IF NOT EXISTS employment_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                company_name TEXT NOT NULL,
                job_title TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT,
                is_current INTEGER NOT NULL DEFAULT 0,
                description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        "
    ],

    'indexes' => [
        "CREATE INDEX IF NOT EXISTS idx_profiles_user_id ON profiles(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_degrees_user_id ON profile_degrees(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_certifications_user_id ON profile_certifications(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_licences_user_id ON profile_licences(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_courses_user_id ON profile_courses(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_employment_user_id ON employment_history(user_id);"
    ],

    'alters' => [
        "ALTER TABLE profiles ADD COLUMN personal_info TEXT;"
    ]
];