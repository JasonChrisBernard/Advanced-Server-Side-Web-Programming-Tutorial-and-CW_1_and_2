# Entity Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ AUTH_TOKENS : owns
    USERS ||--o| PROFILES : has
    USERS ||--o{ PROFILE_DEGREES : lists
    USERS ||--o{ PROFILE_CERTIFICATIONS : lists
    USERS ||--o{ PROFILE_LICENCES : lists
    USERS ||--o{ PROFILE_COURSES : lists
    USERS ||--o{ EMPLOYMENT_HISTORY : works
    USERS ||--o{ BIDS : places
    USERS ||--o{ API_KEYS : creates
    USERS ||--o{ FEATURED_ALUMNI : wins
    BIDS ||--o| FEATURED_ALUMNI : selected_by
    API_KEYS ||--o{ API_USAGE_LOGS : records

    USERS {
        int id PK
        string full_name
        string email
        string password_hash
        string role
        string created_at
    }

    PROFILES {
        int id PK
        int user_id FK
        string headline
        string industry_sector
        int profile_completion_percent
        int is_public
    }

    PROFILE_DEGREES {
        int id PK
        int user_id FK
        string degree_name
        string university_name
        string completion_date
    }

    PROFILE_CERTIFICATIONS {
        int id PK
        int user_id FK
        string certification_name
        string provider_name
        string completion_date
    }

    PROFILE_LICENCES {
        int id PK
        int user_id FK
        string licence_name
        string awarding_body
        string completion_date
    }

    PROFILE_COURSES {
        int id PK
        int user_id FK
        string course_name
        string provider_name
        string completion_date
    }

    EMPLOYMENT_HISTORY {
        int id PK
        int user_id FK
        string company_name
        string job_title
        int is_current
    }

    BIDS {
        int id PK
        int user_id FK
        string feature_date
        float bid_amount
        string status
    }

    FEATURED_ALUMNI {
        int id PK
        string feature_date
        int winner_user_id FK
        int winning_bid_id FK
        float winning_amount
    }

    API_KEYS {
        int id PK
        int developer_user_id FK
        string client_platform
        string key_prefix
        string key_hash
        string scopes
        string revoked_at
    }

    API_USAGE_LOGS {
        int id PK
        int api_key_id FK
        string endpoint
        string required_scope
        string access_result
        int status_code
    }
```

## Notes

- `api_keys.key_hash` stores the SHA-256 hash of the key, not the plaintext key.
- `api_usage_logs` captures permission checks, scope failures, rate-limit events, and successful requests.
- Analytics charts are generated from normalized profile, education, certification, course, and employment tables.
