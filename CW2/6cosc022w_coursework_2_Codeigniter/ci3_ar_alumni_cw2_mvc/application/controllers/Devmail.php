<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Devmail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Auth_model', 'authModel');
    }

    public function index()
    {
        $emails = $this->authModel->getLatestOutboxEmails(30);

        $data = [
            'title' => 'Development Email Outbox',
            'emails' => $emails
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('devmail/index', $data);
        $this->load->view('templates/footer');
    }
}