<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dbtest extends CI_Controller
{
    public function index()
    {
        $sql = "
            SELECT 
                id,
                full_name,
                email,
                role,
                email_verified,
                created_at
            FROM users
            ORDER BY id ASC
        ";

        $stmt = $this->sqlitedb->query($sql);
        $users = $stmt->fetchAll();

        $response = [
            'status' => 'success',
            'message' => 'SQLite connection is working inside CodeIgniter.',
            'users' => $users
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response, JSON_PRETTY_PRINT));
    }
}