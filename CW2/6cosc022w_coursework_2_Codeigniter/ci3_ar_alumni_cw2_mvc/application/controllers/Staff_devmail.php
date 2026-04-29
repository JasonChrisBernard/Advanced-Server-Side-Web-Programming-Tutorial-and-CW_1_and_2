<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Staff_devmail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Staff_auth_model', 'staffAuthModel');
    }

    public function index()
    {
        $emails = $this->staffAuthModel->getLatestOutboxEmails(30);

        $data = [
            'title' => 'Staff Development Email Outbox',
            'emails' => $emails
        ];

        $this->load->view('cw2_templates/header', $data);
        $this->load->view('staff_devmail/index', $data);
        $this->load->view('cw2_templates/footer');
    }
}