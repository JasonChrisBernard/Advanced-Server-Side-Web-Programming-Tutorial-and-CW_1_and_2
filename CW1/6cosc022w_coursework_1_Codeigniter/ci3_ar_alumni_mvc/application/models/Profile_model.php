<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function typeConfig($type)
    {
        $configs = [

            'degrees' => [
                'title' => 'Degrees',
                'single' => 'Degree',
                'table' => 'profile_degrees',
                'fields' => [
                    'degree_name' => 'Degree Name',
                    'university_name' => 'University Name',
                    'official_url' => 'Official Degree Page URL',
                    'completion_date' => 'Completion Date'
                ]
            ],

            'certifications' => [
                'title' => 'Professional Certifications',
                'single' => 'Certification',
                'table' => 'profile_certifications',
                'fields' => [
                    'certification_name' => 'Certification Name',
                    'provider_name' => 'Provider Name',
                    'official_url' => 'Course / Certification URL',
                    'completion_date' => 'Completion Date'
                ]
            ],

            'licences' => [
                'title' => 'Professional Licences',
                'single' => 'Licence',
                'table' => 'profile_licences',
                'fields' => [
                    'licence_name' => 'Licence Name',
                    'awarding_body' => 'Awarding Body',
                    'official_url' => 'Awarding Body URL',
                    'completion_date' => 'Completion Date'
                ]
            ],

            'courses' => [
                'title' => 'Short Professional Courses',
                'single' => 'Course',
                'table' => 'profile_courses',
                'fields' => [
                    'course_name' => 'Course Name',
                    'provider_name' => 'Provider Name',
                    'official_url' => 'Course Page URL',
                    'completion_date' => 'Completion Date'
                ]
            ],

            'employment' => [
                'title' => 'Employment History',
                'single' => 'Employment Record',
                'table' => 'employment_history',
                'fields' => [
                    'company_name' => 'Company Name',
                    'job_title' => 'Job Title',
                    'start_date' => 'Start Date',
                    'end_date' => 'End Date',
                    'is_current' => 'Current Job',
                    'description' => 'Description'
                ]
            ]
        ];

        return isset($configs[$type]) ? $configs[$type] : null;
    }

    public function createDefaultProfile($userId)
    {
        $now = $this->now();

        $sql = "
            INSERT OR IGNORE INTO profiles
            (
                user_id,
                personal_info,
                headline,
                biography,
                linkedin_url,
                profile_completion_percent,
                is_public,
                created_at,
                updated_at
            )
            VALUES
            (
                :user_id,
                '',
                '',
                '',
                '',
                0,
                1,
                :created_at,
                :updated_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId,
            ':created_at' => $now,
            ':updated_at' => $now
        ]);
    }

    public function getProfile($userId)
    {
        $this->createDefaultProfile($userId);

        $sql = "
            SELECT 
                p.*,
                u.full_name,
                u.email
            FROM profiles p
            INNER JOIN users u ON u.id = p.user_id
            WHERE p.user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetch();
    }

    public function updateBasicProfile($userId, $data)
    {
        $this->createDefaultProfile($userId);

        $sql = "
            UPDATE profiles
            SET personal_info = :personal_info,
                headline = :headline,
                biography = :biography,
                linkedin_url = :linkedin_url,
                updated_at = :updated_at
            WHERE user_id = :user_id
        ";

        $this->sqlitedb->query($sql, [
            ':personal_info' => trim($data['personal_info']),
            ':headline' => trim($data['headline']),
            ':biography' => trim($data['biography']),
            ':linkedin_url' => trim($data['linkedin_url']),
            ':updated_at' => $this->now(),
            ':user_id' => (int) $userId
        ]);

        $this->recalculateCompletion($userId);
    }

    public function updateProfileImage($userId, $path)
    {
        $this->createDefaultProfile($userId);

        $sql = "
            UPDATE profiles
            SET profile_image_path = :profile_image_path,
                updated_at = :updated_at
            WHERE user_id = :user_id
        ";

        $this->sqlitedb->query($sql, [
            ':profile_image_path' => $path,
            ':updated_at' => $this->now(),
            ':user_id' => (int) $userId
        ]);

        $this->recalculateCompletion($userId);
    }

    public function getItems($userId, $type)
    {
        $config = $this->typeConfig($type);

        if (!$config) {
            return [];
        }

        $table = $config['table'];

        $sql = "
            SELECT *
            FROM $table
            WHERE user_id = :user_id
            ORDER BY id DESC
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetchAll();
    }

    public function getItem($userId, $type, $id)
    {
        $config = $this->typeConfig($type);

        if (!$config) {
            return false;
        }

        $table = $config['table'];

        $sql = "
            SELECT *
            FROM $table
            WHERE id = :id
            AND user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':id' => (int) $id,
            ':user_id' => (int) $userId
        ]);

        return $stmt->fetch();
    }

    public function createItem($userId, $type, $data)
    {
        $config = $this->typeConfig($type);

        if (!$config) {
            return false;
        }

        $table = $config['table'];
        $fields = array_keys($config['fields']);

        $columns = ['user_id'];
        $placeholders = [':user_id'];
        $params = [
            ':user_id' => (int) $userId
        ];

        foreach ($fields as $field) {
            $columns[] = $field;
            $placeholders[] = ':' . $field;

            if ($field === 'is_current') {
                $params[':' . $field] = !empty($data[$field]) ? 1 : 0;
            } else {
                $params[':' . $field] = isset($data[$field]) ? trim($data[$field]) : null;
            }
        }

        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $placeholders[] = ':created_at';
        $placeholders[] = ':updated_at';

        $params[':created_at'] = $this->now();
        $params[':updated_at'] = $this->now();

        $sql = "
            INSERT INTO $table
            (" . implode(', ', $columns) . ")
            VALUES
            (" . implode(', ', $placeholders) . ")
        ";

        $this->sqlitedb->query($sql, $params);

        $this->recalculateCompletion($userId);

        return true;
    }

    public function updateItem($userId, $type, $id, $data)
    {
        $config = $this->typeConfig($type);

        if (!$config) {
            return false;
        }

        $table = $config['table'];
        $fields = array_keys($config['fields']);

        $sets = [];
        $params = [
            ':id' => (int) $id,
            ':user_id' => (int) $userId
        ];

        foreach ($fields as $field) {
            $sets[] = "$field = :$field";

            if ($field === 'is_current') {
                $params[':' . $field] = !empty($data[$field]) ? 1 : 0;
            } else {
                $params[':' . $field] = isset($data[$field]) ? trim($data[$field]) : null;
            }
        }

        $sets[] = "updated_at = :updated_at";
        $params[':updated_at'] = $this->now();

        $sql = "
            UPDATE $table
            SET " . implode(', ', $sets) . "
            WHERE id = :id
            AND user_id = :user_id
        ";

        $this->sqlitedb->query($sql, $params);

        $this->recalculateCompletion($userId);

        return true;
    }

    public function deleteItem($userId, $type, $id)
    {
        $config = $this->typeConfig($type);

        if (!$config) {
            return false;
        }

        $table = $config['table'];

        $sql = "
            DELETE FROM $table
            WHERE id = :id
            AND user_id = :user_id
        ";

        $this->sqlitedb->query($sql, [
            ':id' => (int) $id,
            ':user_id' => (int) $userId
        ]);

        $this->recalculateCompletion($userId);

        return true;
    }

    public function getCounts($userId)
    {
        $counts = [];

        $tables = [
            'degrees' => 'profile_degrees',
            'certifications' => 'profile_certifications',
            'licences' => 'profile_licences',
            'courses' => 'profile_courses',
            'employment' => 'employment_history'
        ];

        foreach ($tables as $key => $table) {
            $sql = "SELECT COUNT(*) AS total FROM $table WHERE user_id = :user_id";

            $stmt = $this->sqlitedb->query($sql, [
                ':user_id' => (int) $userId
            ]);

            $row = $stmt->fetch();
            $counts[$key] = (int) $row['total'];
        }

        return $counts;
    }

    public function recalculateCompletion($userId)
    {
        $profile = $this->getProfile($userId);
        $counts = $this->getCounts($userId);

        $score = 0;
        $total = 10;

        if (!empty($profile['personal_info'])) {
            $score++;
        }

        if (!empty($profile['headline'])) {
            $score++;
        }

        if (!empty($profile['biography'])) {
            $score++;
        }

        if (!empty($profile['linkedin_url'])) {
            $score++;
        }

        if (!empty($profile['profile_image_path'])) {
            $score++;
        }

        if ($counts['degrees'] > 0) {
            $score++;
        }

        if ($counts['certifications'] > 0) {
            $score++;
        }

        if ($counts['licences'] > 0) {
            $score++;
        }

        if ($counts['courses'] > 0) {
            $score++;
        }

        if ($counts['employment'] > 0) {
            $score++;
        }

        $percent = (int) round(($score / $total) * 100);

        $sql = "
            UPDATE profiles
            SET profile_completion_percent = :percent,
                updated_at = :updated_at
            WHERE user_id = :user_id
        ";

        $this->sqlitedb->query($sql, [
            ':percent' => $percent,
            ':updated_at' => $this->now(),
            ':user_id' => (int) $userId
        ]);

        return $percent;
    }
}