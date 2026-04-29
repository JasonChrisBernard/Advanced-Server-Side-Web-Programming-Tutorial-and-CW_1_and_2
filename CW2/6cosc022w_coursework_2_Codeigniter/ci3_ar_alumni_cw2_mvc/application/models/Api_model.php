<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function platformScopes($clientPlatform)
    {
        $platforms = [
            'analytics_dashboard' => [
                'label' => 'University Analytics Dashboard',
                'scopes' => ['read:alumni', 'read:analytics']
            ],
            'mobile_ar_app' => [
                'label' => 'Mobile AR App',
                'scopes' => ['read:alumni_of_day']
            ]
        ];

        return isset($platforms[$clientPlatform]) ? $platforms[$clientPlatform] : null;
    }

    public function getPlatformOptions()
    {
        return [
            'analytics_dashboard' => 'University Analytics Dashboard',
            'mobile_ar_app' => 'Mobile AR App'
        ];
    }

    public function createApiKey($developerUserId, $keyName, $clientPlatform, $description = '')
    {
        $platform = $this->platformScopes($clientPlatform);

        if (!$platform) {
            return false;
        }

        $plainKey = 'ak_' . bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $plainKey);
        $keyPrefix = substr($plainKey, 0, 12);
        $scopes = implode(',', $platform['scopes']);

        $sql = "
            INSERT INTO api_keys
            (
                developer_user_id,
                key_name,
                client_platform,
                key_prefix,
                key_hash,
                scopes,
                description,
                revoked_at,
                last_used_at,
                created_at
            )
            VALUES
            (
                :developer_user_id,
                :key_name,
                :client_platform,
                :key_prefix,
                :key_hash,
                :scopes,
                :description,
                NULL,
                NULL,
                :created_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':developer_user_id' => (int) $developerUserId,
            ':key_name' => trim($keyName),
            ':client_platform' => $clientPlatform,
            ':key_prefix' => $keyPrefix,
            ':key_hash' => $keyHash,
            ':scopes' => $scopes,
            ':description' => trim($description),
            ':created_at' => $this->now()
        ]);

        return [
            'id' => $this->sqlitedb->lastInsertId(),
            'plain_key' => $plainKey,
            'key_prefix' => $keyPrefix,
            'scopes' => $scopes,
            'client_platform' => $clientPlatform
        ];
    }

    public function getApiKeys($developerUserId)
    {
        $sql = "
            SELECT 
                k.*,
                COUNT(l.id) AS total_requests,
                MAX(l.created_at) AS last_request_at
            FROM api_keys k
            LEFT JOIN api_usage_logs l ON l.api_key_id = k.id
            WHERE k.developer_user_id = :developer_user_id
            GROUP BY k.id
            ORDER BY k.created_at DESC
        ";

        return $this->sqlitedb->query($sql, [
            ':developer_user_id' => (int) $developerUserId
        ])->fetchAll();
    }

    public function getApiKey($keyId, $developerUserId)
    {
        $sql = "
            SELECT *
            FROM api_keys
            WHERE id = :id
            AND developer_user_id = :developer_user_id
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':id' => (int) $keyId,
            ':developer_user_id' => (int) $developerUserId
        ])->fetch();
    }

    public function getApiKeyWithStats($keyId, $developerUserId)
    {
        $sql = "
            SELECT 
                k.*,
                COUNT(l.id) AS total_requests,
                MAX(l.created_at) AS last_request_at
            FROM api_keys k
            LEFT JOIN api_usage_logs l ON l.api_key_id = k.id
            WHERE k.id = :id
            AND k.developer_user_id = :developer_user_id
            GROUP BY k.id
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':id' => (int) $keyId,
            ':developer_user_id' => (int) $developerUserId
        ])->fetch();
    }

    public function revokeApiKey($keyId, $developerUserId)
    {
        $sql = "
            UPDATE api_keys
            SET revoked_at = :revoked_at
            WHERE id = :id
            AND developer_user_id = :developer_user_id
            AND revoked_at IS NULL
        ";

        $this->sqlitedb->query($sql, [
            ':revoked_at' => $this->now(),
            ':id' => (int) $keyId,
            ':developer_user_id' => (int) $developerUserId
        ]);
    }

    public function verifyBearerToken($plainToken)
    {
        $keyHash = hash('sha256', $plainToken);

        $sql = "
            SELECT *
            FROM api_keys
            WHERE key_hash = :key_hash
            AND revoked_at IS NULL
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':key_hash' => $keyHash
        ])->fetch();
    }

    public function updateLastUsed($apiKeyId)
    {
        $sql = "
            UPDATE api_keys
            SET last_used_at = :last_used_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':last_used_at' => $this->now(),
            ':id' => (int) $apiKeyId
        ]);
    }

    public function hasScope($apiKey, $requiredScope)
    {
        $scopes = array_map('trim', explode(',', $apiKey['scopes']));

        return in_array($requiredScope, $scopes);
    }

    public function logUsage($apiKeyId, $endpoint, $method, $requiredScope, $accessResult, $statusCode, $ipAddress, $userAgent)
    {
        $sql = "
            INSERT INTO api_usage_logs
            (
                api_key_id,
                endpoint,
                http_method,
                required_scope,
                access_result,
                status_code,
                ip_address,
                user_agent,
                created_at
            )
            VALUES
            (
                :api_key_id,
                :endpoint,
                :http_method,
                :required_scope,
                :access_result,
                :status_code,
                :ip_address,
                :user_agent,
                :created_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':api_key_id' => $apiKeyId ? (int) $apiKeyId : null,
            ':endpoint' => $endpoint,
            ':http_method' => $method,
            ':required_scope' => $requiredScope,
            ':access_result' => $accessResult,
            ':status_code' => (int) $statusCode,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':created_at' => $this->now()
        ]);
    }

    public function getUsageLogs($developerUserId, $keyId, $limit = 100)
    {
        $limit = (int) $limit;

        $sql = "
            SELECT 
                l.*,
                k.key_name,
                k.client_platform,
                k.key_prefix,
                k.scopes
            FROM api_usage_logs l
            INNER JOIN api_keys k ON k.id = l.api_key_id
            WHERE k.developer_user_id = :developer_user_id
            AND k.id = :key_id
            ORDER BY l.created_at DESC
            LIMIT $limit
        ";

        return $this->sqlitedb->query($sql, [
            ':developer_user_id' => (int) $developerUserId,
            ':key_id' => (int) $keyId
        ])->fetchAll();
    }

    public function countRecentUsage($apiKeyId, $ipAddress, $windowStart)
    {
        if ($apiKeyId) {
            $sql = "
                SELECT COUNT(*) AS total
                FROM api_usage_logs
                WHERE api_key_id = :api_key_id
                AND created_at >= :window_start
            ";

            $row = $this->sqlitedb->query($sql, [
                ':api_key_id' => (int) $apiKeyId,
                ':window_start' => $windowStart
            ])->fetch();
        } else {
            $sql = "
                SELECT COUNT(*) AS total
                FROM api_usage_logs
                WHERE ip_address = :ip_address
                AND created_at >= :window_start
            ";

            $row = $this->sqlitedb->query($sql, [
                ':ip_address' => $ipAddress,
                ':window_start' => $windowStart
            ])->fetch();
        }

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    public function getAlumniOfDay()
    {
        return $this->getFeaturedByDate(date('Y-m-d'));
    }

    public function getFeaturedByDate($featureDate)
    {
        $sql = "
            SELECT 
                fa.feature_date,
                fa.selected_at,
                u.id AS alumni_id,
                u.full_name,
                p.headline,
                p.biography,
                p.linkedin_url,
                p.profile_image_path,
                p.profile_completion_percent
            FROM featured_alumni fa
            INNER JOIN users u ON u.id = fa.winner_user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE fa.feature_date = :feature_date
            LIMIT 1
        ";

        $featured = $this->sqlitedb->query($sql, [
            ':feature_date' => $featureDate
        ])->fetch();

        if (!$featured) {
            return false;
        }

        $featured['profile_image_url'] = !empty($featured['profile_image_path'])
            ? base_url($featured['profile_image_path'])
            : null;

        return $featured;
    }

    public function getPublicAlumniProfile($id)
    {
        $sql = "
            SELECT
                u.id AS alumni_id,
                u.full_name,
                u.email,
                p.personal_info,
                p.headline,
                p.biography,
                p.linkedin_url,
                p.industry_sector,
                p.profile_image_path,
                p.profile_completion_percent
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :id
            AND u.role = 'alumnus'
            AND COALESCE(p.is_public, 1) = 1
            LIMIT 1
        ";

        $profile = $this->sqlitedb->query($sql, [
            ':id' => (int) $id
        ])->fetch();

        if (!$profile) {
            return false;
        }

        $profile['profile_image_url'] = !empty($profile['profile_image_path'])
            ? base_url($profile['profile_image_path'])
            : null;

        $profile['sections'] = [
            'degrees' => $this->getProfileSection('profile_degrees', $id),
            'certifications' => $this->getProfileSection('profile_certifications', $id),
            'licences' => $this->getProfileSection('profile_licences', $id),
            'courses' => $this->getProfileSection('profile_courses', $id),
            'employment_history' => $this->getProfileSection('employment_history', $id)
        ];

        return $profile;
    }

    private function getProfileSection($table, $userId)
    {
        $sql = "
            SELECT *
            FROM $table
            WHERE user_id = :user_id
            ORDER BY id DESC
        ";

        return $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ])->fetchAll();
    }

    public function getAnalyticsSummary()
    {
        $totalAlumni = $this->countRows("SELECT COUNT(*) AS total FROM users WHERE role = 'alumnus'");
        $totalCurrentJobs = $this->countRows("SELECT COUNT(DISTINCT user_id) AS total FROM employment_history WHERE is_current = 1");

        return [
            'total_alumni' => $totalAlumni,
            'total_programmes' => $this->countRows("SELECT COUNT(DISTINCT degree_name) AS total FROM profile_degrees"),
            'total_industries' => $this->countRows("SELECT COUNT(DISTINCT industry_sector) AS total FROM profiles WHERE industry_sector IS NOT NULL AND industry_sector != ''"),
            'total_certifications' => $this->countRows("SELECT COUNT(*) AS total FROM profile_certifications"),
            'total_courses' => $this->countRows("SELECT COUNT(*) AS total FROM profile_courses"),
            'total_current_jobs' => $totalCurrentJobs,
            'average_profile_completion' => $this->averageRows("
                SELECT AVG(profile_completion_percent) AS average_value
                FROM profiles p
                INNER JOIN users u ON u.id = p.user_id
                WHERE u.role = 'alumnus'
            "),
            'current_employment_rate' => $totalAlumni > 0 ? round(($totalCurrentJobs / $totalAlumni) * 100, 1) : 0
        ];
    }

    private function countRows($sql)
    {
        $row = $this->sqlitedb->query($sql)->fetch();

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private function averageRows($sql)
    {
        $row = $this->sqlitedb->query($sql)->fetch();

        return isset($row['average_value']) ? round((float) $row['average_value'], 1) : 0;
    }

    public function getAnalyticsAlumni()
    {
        $sql = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                p.headline,
                p.industry_sector,
                p.profile_completion_percent,
                d.degree_name,
                d.completion_date,
                e.company_name,
                e.job_title
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN profile_degrees d ON d.user_id = u.id
            LEFT JOIN employment_history e ON e.user_id = u.id AND e.is_current = 1
            WHERE u.role = 'alumnus'
            GROUP BY u.id
            ORDER BY u.full_name ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getCertificationAnalytics()
    {
        $sql = "
            SELECT 
                certification_name,
                provider_name,
                COUNT(DISTINCT user_id) AS alumni_count
            FROM profile_certifications
            GROUP BY certification_name, provider_name
            ORDER BY alumni_count DESC, certification_name ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getIndustryAnalytics()
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN industry_sector IS NULL OR industry_sector = '' THEN 'Unspecified'
                    ELSE industry_sector
                END AS industry_sector,
                COUNT(DISTINCT user_id) AS alumni_count
            FROM profiles
            GROUP BY industry_sector
            ORDER BY alumni_count DESC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }
}
