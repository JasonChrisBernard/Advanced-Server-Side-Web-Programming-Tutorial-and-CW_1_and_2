<?php
if (!defined('BASEPATH') && !defined('AR_ALUMNI_BIDDING_SETUP_RUNNER')) {
    exit('No direct script access allowed');
}

$bidding_setup = [
    'timezone' => 'Asia/Colombo',

    'db_file' => __DIR__ . '/../data/ar_alumni.sqlite',

    'tables' => [

        'bids' => "
            CREATE TABLE IF NOT EXISTS bids (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                feature_date TEXT NOT NULL,
                bid_amount REAL NOT NULL CHECK(bid_amount > 0),
                status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'cancelled', 'won', 'lost')),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                UNIQUE(user_id, feature_date),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",

        'featured_alumni' => "
            CREATE TABLE IF NOT EXISTS featured_alumni (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                feature_date TEXT NOT NULL UNIQUE,
                winner_user_id INTEGER NOT NULL,
                winning_bid_id INTEGER NOT NULL,
                winning_amount REAL NOT NULL,
                selected_at TEXT NOT NULL,
                FOREIGN KEY (winner_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (winning_bid_id) REFERENCES bids(id) ON DELETE CASCADE
            );
        "
    ],

    'indexes' => [
        "CREATE INDEX IF NOT EXISTS idx_bids_user_id ON bids(user_id);",
        "CREATE INDEX IF NOT EXISTS idx_bids_feature_date ON bids(feature_date);",
        "CREATE INDEX IF NOT EXISTS idx_bids_status ON bids(status);",
        "CREATE INDEX IF NOT EXISTS idx_featured_alumni_date ON featured_alumni(feature_date);",
        "CREATE INDEX IF NOT EXISTS idx_featured_alumni_winner ON featured_alumni(winner_user_id);"
    ]
];