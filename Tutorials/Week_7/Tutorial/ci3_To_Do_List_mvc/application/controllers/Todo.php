<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Todo extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Load database, session, URL helper, and model
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Todo_model');
    }

    private function get_or_create_user_id()
    {
        // Try to get existing user id from session
        $user_id = $this->session->userdata('todo_user_id');

        // If no user id exists, create one
        if (empty($user_id)) {
            $user_id = uniqid('todo_', true);

            // Store generated id in CodeIgniter session
            $this->session->set_userdata('todo_user_id', $user_id);

            log_message('debug', 'New To-Do user id generated and stored in session: ' . $user_id);
        } else {
            log_message('debug', 'Existing To-Do user id found in session: ' . $user_id);
        }

        return $user_id;
    }

    public function index()
    {
        // Get the unique user id for this browser/session
        $user_id = $this->get_or_create_user_id();

        // Get this user's existing actions from database
        $data['actions'] = $this->Todo_model->get_actions_by_user($user_id);

        // Send user id to view for debugging/learning
        $data['user_id'] = $user_id;

        // Flash messages
        $data['success'] = $this->session->flashdata('success');
        $data['error'] = $this->session->flashdata('error');

        // Load the page
        $this->load->view('todo_view', $data);
    }

    public function add()
    {
        // Get user id from session
        $user_id = $this->get_or_create_user_id();

        // Get form input safely
        $action_title = trim($this->input->post('action_title', TRUE));

        // Basic validation
        if ($action_title === '') {
            $this->session->set_flashdata('error', 'Please enter a To-Do action.');
            redirect('todo');
            return;
        }

        // Add action to database
        $inserted = $this->Todo_model->add_action($user_id, $action_title);

        if ($inserted) {
            log_message('debug', 'New To-Do action added for user id ' . $user_id . ': ' . $action_title);
            $this->session->set_flashdata('success', 'To-Do action added successfully.');
        } else {
            log_message('error', 'Failed to add To-Do action for user id ' . $user_id);
            $this->session->set_flashdata('error', 'Something went wrong. Please try again.');
        }

        redirect('todo');
    }
}