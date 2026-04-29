<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Profile_model', 'profileModel');
    }

    private function render($view, $data = [])
    {
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    private function requireLogin()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            exit;
        }
    }

    private function requireAlumnus()
    {
        $this->requireLogin();

        if ($this->session->userdata('role') !== 'alumnus') {
            show_error('Only alumni users can manage alumni profiles.', 403);
        }
    }

    private function userId()
    {
        return (int) $this->session->userdata('user_id');
    }

    private function isPost()
    {
        return $this->input->method(TRUE) === 'POST';
    }

    private function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function isValidDate($date)
    {
        if ($date === '' || $date === null) {
            return false;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }

    public function index()
    {
        $this->requireAlumnus();

        $userId = $this->userId();

        $data = [
            'title' => 'Manage Alumni Profile',
            'profile' => $this->profileModel->getProfile($userId),
            'counts' => $this->profileModel->getCounts($userId)
        ];

        $this->render('profile/index', $data);
    }

    public function edit_basic()
    {
        $this->requireAlumnus();

        $userId = $this->userId();

        $profile = $this->profileModel->getProfile($userId);

        $data = [
            'title' => 'Edit Basic Profile',
            'profile' => $profile,
            'errors' => []
        ];

        if ($this->isPost()) {
            $formData = [
                'personal_info' => trim($this->input->post('personal_info', TRUE)),
                'headline' => trim($this->input->post('headline', TRUE)),
                'biography' => trim($this->input->post('biography', TRUE)),
                'linkedin_url' => trim($this->input->post('linkedin_url', TRUE))
            ];

            if ($formData['personal_info'] === '') {
                $data['errors'][] = 'Personal information is required.';
            }

            if ($formData['headline'] === '') {
                $data['errors'][] = 'Professional headline is required.';
            }

            if ($formData['biography'] === '') {
                $data['errors'][] = 'Biography is required.';
            }

            if ($formData['linkedin_url'] === '') {
                $data['errors'][] = 'LinkedIn profile URL is required.';
            } elseif (!$this->isValidUrl($formData['linkedin_url'])) {
                $data['errors'][] = 'LinkedIn profile URL must be a valid URL.';
            } elseif (stripos($formData['linkedin_url'], 'linkedin.com') === false) {
                $data['errors'][] = 'LinkedIn profile URL must contain linkedin.com.';
            }

            if (empty($data['errors'])) {
                $this->profileModel->updateBasicProfile($userId, $formData);

                $this->session->set_flashdata('success', 'Basic profile updated successfully.');
                redirect('profile');
                return;
            }

            $data['profile'] = array_merge($profile, $formData);
        }

        $this->render('profile/edit_basic', $data);
    }

    public function upload_image()
    {
        $this->requireAlumnus();

        $userId = $this->userId();

        $data = [
            'title' => 'Upload Profile Image',
            'profile' => $this->profileModel->getProfile($userId),
            'errors' => []
        ];

        if ($this->isPost()) {
            $uploadPath = FCPATH . 'uploads/profiles/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            $config = [
                'upload_path' => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png',
                'max_size' => 2048,
                'encrypt_name' => TRUE
            ];

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('profile_image')) {
                $data['errors'][] = strip_tags($this->upload->display_errors());
            } else {
                $uploadData = $this->upload->data();

                $relativePath = 'uploads/profiles/' . $uploadData['file_name'];

                if (!empty($data['profile']['profile_image_path'])) {
                    $oldFile = FCPATH . $data['profile']['profile_image_path'];

                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                $this->profileModel->updateProfileImage($userId, $relativePath);

                $this->session->set_flashdata('success', 'Profile image uploaded successfully.');
                redirect('profile');
                return;
            }
        }

        $this->render('profile/upload_image', $data);
    }

    public function items($type)
    {
        $this->requireAlumnus();

        $config = $this->profileModel->typeConfig($type);

        if (!$config) {
            show_404();
        }

        $userId = $this->userId();

        $data = [
            'title' => $config['title'],
            'type' => $type,
            'config' => $config,
            'items' => $this->profileModel->getItems($userId, $type)
        ];

        $this->render('profile/manage_items', $data);
    }

    public function create_item($type)
    {
        $this->requireAlumnus();

        $config = $this->profileModel->typeConfig($type);

        if (!$config) {
            show_404();
        }

        $data = [
            'title' => 'Add ' . $config['single'],
            'type' => $type,
            'config' => $config,
            'mode' => 'create',
            'item' => [],
            'errors' => [],
            'action' => 'profile/items/' . $type . '/create'
        ];

        if ($this->isPost()) {
            $itemData = $this->collectItemData($config);
            $data['item'] = $itemData;
            $data['errors'] = $this->validateItemData($type, $config, $itemData);

            if (empty($data['errors'])) {
                $this->profileModel->createItem($this->userId(), $type, $itemData);

                $this->session->set_flashdata('success', $config['single'] . ' added successfully.');
                redirect('profile/items/' . $type);
                return;
            }
        }

        $this->render('profile/item_form', $data);
    }

    public function edit_item($type, $id)
    {
        $this->requireAlumnus();

        $config = $this->profileModel->typeConfig($type);

        if (!$config) {
            show_404();
        }

        $item = $this->profileModel->getItem($this->userId(), $type, $id);

        if (!$item) {
            show_404();
        }

        $data = [
            'title' => 'Edit ' . $config['single'],
            'type' => $type,
            'config' => $config,
            'mode' => 'edit',
            'item' => $item,
            'errors' => [],
            'action' => 'profile/items/' . $type . '/edit/' . (int) $id
        ];

        if ($this->isPost()) {
            $itemData = $this->collectItemData($config);
            $data['item'] = array_merge($item, $itemData);
            $data['errors'] = $this->validateItemData($type, $config, $itemData);

            if (empty($data['errors'])) {
                $this->profileModel->updateItem($this->userId(), $type, $id, $itemData);

                $this->session->set_flashdata('success', $config['single'] . ' updated successfully.');
                redirect('profile/items/' . $type);
                return;
            }
        }

        $this->render('profile/item_form', $data);
    }

    public function delete_item($type, $id)
    {
        $this->requireAlumnus();

        if (!$this->isPost()) {
            show_error('Delete requests must use POST.', 405);
        }

        $config = $this->profileModel->typeConfig($type);

        if (!$config) {
            show_404();
        }

        $this->profileModel->deleteItem($this->userId(), $type, $id);

        $this->session->set_flashdata('success', $config['single'] . ' deleted successfully.');
        redirect('profile/items/' . $type);
    }

    private function collectItemData($config)
    {
        $data = [];

        foreach ($config['fields'] as $field => $label) {
            if ($field === 'is_current') {
                $data[$field] = $this->input->post($field) ? 1 : 0;
            } else {
                $data[$field] = trim($this->input->post($field, TRUE));
            }
        }

        if (isset($data['is_current']) && (int) $data['is_current'] === 1) {
            $data['end_date'] = '';
        }

        return $data;
    }

    private function validateItemData($type, $config, $data)
    {
        $errors = [];

        if ($type === 'employment') {
            if (empty($data['company_name'])) {
                $errors[] = 'Company name is required.';
            }

            if (empty($data['job_title'])) {
                $errors[] = 'Job title is required.';
            }

            if (empty($data['start_date']) || !$this->isValidDate($data['start_date'])) {
                $errors[] = 'Start date must be a valid date.';
            }

            if (empty($data['is_current'])) {
                if (empty($data['end_date']) || !$this->isValidDate($data['end_date'])) {
                    $errors[] = 'End date is required unless this is your current job.';
                }
            }

            return $errors;
        }

        foreach ($config['fields'] as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = $label . ' is required.';
            }
        }

        if (!empty($data['official_url']) && !$this->isValidUrl($data['official_url'])) {
            $errors[] = 'Official URL must be a valid URL.';
        }

        if (!empty($data['completion_date']) && !$this->isValidDate($data['completion_date'])) {
            $errors[] = 'Completion date must be a valid date.';
        }

        return $errors;
    }
}