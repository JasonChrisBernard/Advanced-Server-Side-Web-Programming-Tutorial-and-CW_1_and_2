<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function getUserByEmail($email)
    {
        $sql = "
            SELECT *
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':email' => strtolower(trim($email))
        ]);

        return $stmt->fetch();
    }

    public function getUserById($id)
    {
        $sql = "
            SELECT *
            FROM users
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->sqlitedb->query($sql, [
            ':id' => (int) $id
        ]);

        return $stmt->fetch();
    }

    public function createUser($fullName, $email, $passwordHash, $role = 'alumnus')
    {
        $now = $this->now();

        $sql = "
            INSERT INTO users
            (
                full_name,
                email,
                password_hash,
                role,
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
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        return $this->sqlitedb->lastInsertId();
    }

    public function createAuthToken($userId, $tokenType, $expiryMinutes)
    {
        $this->revokeOpenTokens($userId, $tokenType);

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $now = $this->now();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) $expiryMinutes . ' minutes'));

        $sql = "
            INSERT INTO auth_tokens
            (
                user_id,
                token_hash,
                token_type,
                expires_at,
                used_at,
                created_at
            )
            VALUES
            (
                :user_id,
                :token_hash,
                :token_type,
                :expires_at,
                NULL,
                :created_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId,
            ':token_hash' => $tokenHash,
            ':token_type' => $tokenType,
            ':expires_at' => $expiresAt,
            ':created_at' => $now
        ]);

        return $plainToken;
    }

    public function revokeOpenTokens($userId, $tokenType)
    {
        $sql = "
            UPDATE auth_tokens
            SET used_at = :used_at
            WHERE user_id = :user_id
            AND token_type = :token_type
            AND used_at IS NULL
        ";

        $this->sqlitedb->query($sql, [
            ':used_at' => $this->now(),
            ':user_id' => (int) $userId,
            ':token_type' => $tokenType
        ]);
    }

    public function getValidToken($plainToken, $tokenType)
    {
        $tokenHash = hash('sha256', $plainToken);

        $sql = "
            SELECT *
            FROM auth_tokens
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
            UPDATE auth_tokens
            SET used_at = :used_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':used_at' => $this->now(),
            ':id' => (int) $tokenId
        ]);
    }

    public function verifyUserEmail($userId)
    {
        $sql = "
            UPDATE users
            SET email_verified = 1,
                updated_at = :updated_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':updated_at' => $this->now(),
            ':id' => (int) $userId
        ]);
    }

    public function updatePassword($userId, $passwordHash)
    {
        $sql = "
            UPDATE users
            SET password_hash = :password_hash,
                updated_at = :updated_at
            WHERE id = :id
        ";

        $this->sqlitedb->query($sql, [
            ':password_hash' => $passwordHash,
            ':updated_at' => $this->now(),
            ':id' => (int) $userId
        ]);
    }

    public function updateLastLogin($userId)
    {
        $sql = "
            UPDATE users
            SET last_login_at = :last_login_at,
                updated_at = :updated_at
            WHERE id = :id
        ";

        $now = $this->now();

        $this->sqlitedb->query($sql, [
            ':last_login_at' => $now,
            ':updated_at' => $now,
            ':id' => (int) $userId
        ]);
    }

    public function saveEmailToOutbox($toEmail, $subject, $body)
    {
        $sql = "
            INSERT INTO email_outbox
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
            FROM email_outbox
            ORDER BY id DESC
            LIMIT $limit
        ";

        $stmt = $this->sqlitedb->query($sql);

        return $stmt->fetchAll();
    }
}