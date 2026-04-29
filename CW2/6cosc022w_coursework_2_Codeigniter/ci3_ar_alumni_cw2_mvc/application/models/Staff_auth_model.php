<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Staff_auth_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function getStaffByEmail($email)
    {
        $sql = "
            SELECT *
            FROM staff_users
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':email' => strtolower(trim($email))
        ]);

        return $stmt->fetch();
    }

    public function getStaffById($id)
    {
        $sql = "
            SELECT *
            FROM staff_users
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':id' => (int) $id
        ]);

        return $stmt->fetch();
    }

    public function createStaffUser($fullName, $email, $passwordHash, $department, $jobTitle, $role = 'analyst')
    {
        $now = $this->now();

        $sql = "
            INSERT INTO staff_users
            (
                full_name,
                email,
                password_hash,
                role,
                department,
                job_title,
                email_verified,
                is_active,
                created_at,
                updated_at
            )
            VALUES
            (
                :full_name,
                :email,
                :password_hash,
                :role,
                :department,
                :job_title,
                0,
                1,
                :created_at,
                :updated_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':full_name' => trim($fullName),
            ':email' => strtolower(trim($email)),
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':department' => trim($department),
            ':job_title' => trim($jobTitle),
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        return $this->sqlitedb->lastInsertId();
    }

    public function createAuthToken($staffUserId, $tokenType, $expiryMinutes)
    {
        $this->revokeOpenTokens($staffUserId, $tokenType);

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $now = $this->now();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)$expiryMinutes . ' minutes'));

        $sql = "
            INSERT INTO staff_auth_tokens
            (
                staff_user_id,
                token_hash,
                token_type,
                expires_at,
                used_at,
                created_at
            )
            VALUES
            (
                :staff_user_id,
                :token_hash,
                :token_type,
                :expires_at,
                NULL,
                :created_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':staff_user_id' => (int) $staffUserId,
            ':token_hash' => $tokenHash,
            ':token_type' => $tokenType,
            ':expires_at' => $expiresAt,
            ':created_at' => $now
        ]);

        return $plainToken;
    }

    public function revokeOpenTokens($staffUserId, $tokenType)
    {
        $sql = "
            UPDATE staff_auth_tokens
            SET used_at = :used_at
            WHERE staff_user_id = :staff_user_id
            AND token_type = :token_type
            AND used_at IS NULL
        ";

        $this->sqlitedb->query($sql, [
            ':used_at' => $this->now(),
            ':staff_user_id' => (int) $staffUserId,
            ':token_type' => $tokenType
        ]);
    }

    public function getValidToken($plainToken, $tokenType)
    {
        $tokenHash = hash('sha256', $plainToken);

        $sql = "
            SELECT *
            FROM staff_auth_tokens
            WHERE token_hash = :token_hash
            AND token_type = :token_type
            AND used_at IS NULL
            AND expires_at > :now
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':token_hash' => $tokenHash,
            ':token_type' => $tokenType,
            ':now' => $this->now()
        ]);

        return $stmt->fetch();
    }

    public function markTokenUsed($tokenId)
    {
        $sql = "
            UPDATE staff_auth_tokens
            SET used_at = :used_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':used_at' => $this->now(),
            ':id' => (int) $tokenId
        ]);
    }

    public function verifyEmail($staffUserId)
    {
        $sql = "
            UPDATE staff_users
            SET email_verified = 1,
                updated_at = :updated_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':updated_at' => $this->now(),
            ':id' => (int) $staffUserId
        ]);
    }

    public function updatePassword($staffUserId, $passwordHash)
    {
        $sql = "
            UPDATE staff_users
            SET password_hash = :password_hash,
                updated_at = :updated_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':password_hash' => $passwordHash,
            ':updated_at' => $this->now(),
            ':id' => (int) $staffUserId
        ]);
    }

    public function updateLastLogin($staffUserId)
    {
        $now = $this->now();

        $sql = "
            UPDATE staff_users
            SET last_login_at = :last_login_at,
                updated_at = :updated_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':last_login_at' => $now,
            ':updated_at' => $now,
            ':id' => (int) $staffUserId
        ]);
    }

    public function saveEmailToOutbox($toEmail, $subject, $body)
    {
        $sql = "
            INSERT INTO staff_email_outbox
            (
                to_email,
                subject,
                body,
                status,
                created_at
            )
            VALUES
            (
                :to_email,
                :subject,
                :body,
                'pending',
                :created_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':to_email' => strtolower(trim($toEmail)),
            ':subject' => $subject,
            ':body' => $body,
            ':created_at' => $this->now()
        ]);
    }

    public function getLatestOutboxEmails($limit = 30)
    {
        $limit = (int) $limit;

        $sql = "
            SELECT *
            FROM staff_email_outbox
            ORDER BY id DESC
            LIMIT $limit
        ";

        $stmt = $this->sqlitedb->query($sql);

        return $stmt->fetchAll();
    }
}