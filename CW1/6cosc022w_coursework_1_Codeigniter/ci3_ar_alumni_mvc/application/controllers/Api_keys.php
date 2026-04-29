<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_keys extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Api_model', 'apiModel');
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

    private function requireDeveloper()
    {
        $this->requireLogin();

        if ($this->session->userdata('role') !== 'developer') {
            show_error('Only developer users can manage API keys.', 403);
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

    public function index()
    {
        $this->requireDeveloper();

        $data = [
            'title' => 'API Key Management',
            'apiKeys' => $this->apiModel->getApiKeys($this->userId())
        ];

        $this->render('api_keys/index', $data);
    }

    public function create()
    {
        $this->requireDeveloper();

        $data = [
            'title' => 'Generate API Key',
            'errors' => [],
            'old' => [
                'key_name' => ''
            ]
        ];

        if ($this->isPost()) {
            $keyName = trim($this->input->post('key_name', TRUE));
            $scope = $this->input->post('scope', TRUE);

            $data['old']['key_name'] = $keyName;

            if ($keyName === '' || strlen($keyName) < 3) {
                $data['errors'][] = 'Client/key name must be at least 3 characters.';
            }

            if ($scope !== 'read:alumni') {
                $scope = 'read:alumni';
            }

            if (empty($data['errors'])) {
                $result = $this->apiModel->createApiKey(
                    $this->userId(),
                    $keyName,
                    $scope
                );

                $this->session->set_flashdata('plain_api_key', $result['plain_key']);
                $this->session->set_flashdata('key_prefix', $result['key_prefix']);
                $this->session->set_flashdata('success', 'API key generated successfully. Copy it now because it will not be shown again.');

                redirect('api-keys');
                return;
            }
        }

        $this->render('api_keys/create', $data);
    }

    public function revoke($keyId)
    {
        $this->requireDeveloper();

        if (!$this->isPost()) {
            show_error('Revoke request must use POST.', 405);
        }

        $apiKey = $this->apiModel->getApiKey($keyId, $this->userId());

        if (!$apiKey) {
            show_404();
        }

        $this->apiModel->revokeApiKey($keyId, $this->userId());

        $this->session->set_flashdata('success', 'API key revoked successfully.');
        redirect('api-keys');
    }

    public function stats($keyId)
    {
        $this->requireDeveloper();

        $apiKey = $this->apiModel->getApiKeyWithStats($keyId, $this->userId());

        if (!$apiKey) {
            show_404();
        }

        $data = [
            'title' => 'API Usage Statistics',
            'apiKey' => $apiKey,
            'logs' => $this->apiModel->getUsageLogs($this->userId(), $keyId, 100)
        ];

        $this->render('api_keys/stats', $data);
    }

    public function docs()
    {
        $this->requireDeveloper();

        $data = [
            'title' => 'API Access Guide'
        ];

        $this->render('api_keys/docs', $data);
    }
}