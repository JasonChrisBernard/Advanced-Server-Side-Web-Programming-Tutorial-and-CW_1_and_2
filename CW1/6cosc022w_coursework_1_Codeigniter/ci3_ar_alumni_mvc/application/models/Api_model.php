<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    /*
    |--------------------------------------------------------------------------
    | API KEY MANAGEMENT
    |--------------------------------------------------------------------------
    */

    public function createApiKey($developerUserId, $keyName, $scopes = 'read:alumni')
    {
        $plainKey = 'ak_' . bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $plainKey);
        $keyPrefix = substr($plainKey, 0, 12);

        $sql = "
            INSERT INTO api_keys
            (
                developer_user_id,
                key_name,
                key_prefix,
                key_hash,
                scopes,
                revoked_at,
                last_used_at,
                created_at
            )
            VALUES
            (
                :developer_user_id,
                :key_name,
                :key_prefix,
                :key_hash,
                :scopes,
                NULL,
                NULL,
                :created_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':developer_user_id' => (int) $developerUserId,
            ':key_name' => trim($keyName),
            ':key_prefix' => $keyPrefix,
            ':key_hash' => $keyHash,
            ':scopes' => $scopes,
            ':created_at' => $this->now()
        ]);

        return [
            'id' => $this->sqlitedb->lastInsertId(),
            'plain_key' => $plainKey,
            'key_prefix' => $keyPrefix
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

        $stmt = $this->sqlitedb->query($sql, [
            ':developer_user_id' => (int) $developerUserId
        ]);

        return $stmt->fetchAll();
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

        $stmt = $this->sqlitedb->query($sql, [
            ':id' => (int) $keyId,
            ':developer_user_id' => (int) $developerUserId
        ]);

        return $stmt->fetch();
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

        $stmt = $this->sqlitedb->query($sql, [
            ':id' => (int) $keyId,
            ':developer_user_id' => (int) $developerUserId
        ]);

        return $stmt->fetch();
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

        $stmt = $this->sqlitedb->query($sql, [
            ':key_hash' => $keyHash
        ]);

        return $stmt->fetch();
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

        return in_array('*', $scopes) || in_array($requiredScope, $scopes);
    }

    /*
    |--------------------------------------------------------------------------
    | API USAGE LOGGING
    |--------------------------------------------------------------------------
    */

    public function logUsage($apiKeyId, $endpoint, $method, $statusCode, $ipAddress, $userAgent)
    {
        $sql = "
            INSERT INTO api_usage_logs
            (
                api_key_id,
                endpoint,
                http_method,
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
                k.key_prefix
            FROM api_usage_logs l
            INNER JOIN api_keys k ON k.id = l.api_key_id
            WHERE k.developer_user_id = :developer_user_id
            AND k.id = :key_id
            ORDER BY l.created_at DESC
            LIMIT $limit
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':developer_user_id' => (int) $developerUserId,
            ':key_id' => (int) $keyId
        ]);

        return $stmt->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ALUMNI API DATA
    |--------------------------------------------------------------------------
    */

    public function getFeaturedByDate($featureDate)
    {
        $sql = "
            SELECT 
                fa.feature_date,
                fa.selected_at,
                u.id AS alumni_id,
                u.full_name,
                u.email,
                p.personal_info,
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

        $stmt = $this->sqlitedb->query($sql, [
            ':feature_date' => $featureDate
        ]);

        $featured = $stmt->fetch();

        if (!$featured) {
            return false;
        }

        $featured['profile_image_url'] = !empty($featured['profile_image_path'])
            ? base_url($featured['profile_image_path'])
            : null;

        $featured['sections'] = $this->getProfileSections($featured['alumni_id']);

        return $featured;
    }

    public function getPublicAlumniProfile($userId)
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
                p.profile_image_path,
                p.profile_completion_percent
            FROM users u
            INNER JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :user_id
            AND u.role = 'alumnus'
            AND p.is_public = 1
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        $profile = $stmt->fetch();

        if (!$profile) {
            return false;
        }

        $profile['profile_image_url'] = !empty($profile['profile_image_path'])
            ? base_url($profile['profile_image_path'])
            : null;

        $profile['sections'] = $this->getProfileSections($profile['alumni_id']);

        return $profile;
    }

    public function getProfileSections($userId)
    {
        return [
            'degrees' => $this->getDegrees($userId),
            'certifications' => $this->getCertifications($userId),
            'licences' => $this->getLicences($userId),
            'courses' => $this->getCourses($userId),
            'employment_history' => $this->getEmploymentHistory($userId)
        ];
    }

    private function getDegrees($userId)
    {
        $sql = "
            SELECT 
                degree_name,
                university_name,
                official_url,
                completion_date
            FROM profile_degrees
            WHERE user_id = :user_id
            ORDER BY completion_date DESC, id DESC
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetchAll();
    }

    private function getCertifications($userId)
    {
        $sql = "
            SELECT 
                certification_name,
                provider_name,
                official_url,
                completion_date
            FROM profile_certifications
            WHERE user_id = :user_id
            ORDER BY completion_date DESC, id DESC
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetchAll();
    }

    private function getLicences($userId)
    {
        $sql = "
            SELECT 
                licence_name,
                awarding_body,
                official_url,
                completion_date
            FROM profile_licences
            WHERE user_id = :user_id
            ORDER BY completion_date DESC, id DESC
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetchAll();
    }

    private function getCourses($userId)
    {
        $sql = "
            SELECT 
                course_name,
                provider_name,
                official_url,
                completion_date
            FROM profile_courses
            WHERE user_id = :user_id
            ORDER BY completion_date DESC, id DESC
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetchAll();
    }

    private function getEmploymentHistory($userId)
    {
        $sql = "
            SELECT 
                company_name,
                job_title,
                start_date,
                end_date,
                is_current,
                description
            FROM employment_history
            WHERE user_id = :user_id
            ORDER BY start_date DESC, id DESC
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetchAll();
    }
}