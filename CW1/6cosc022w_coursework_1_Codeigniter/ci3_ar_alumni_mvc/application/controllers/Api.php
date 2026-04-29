<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Api_model', 'apiModel');

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Methods: GET, OPTIONS');

        if ($this->input->method(TRUE) === 'OPTIONS') {
            $this->output
                ->set_status_header(204)
                ->set_output('');
            $this->output->_display();
            exit;
        }
    }

    private function endpoint()
    {
        return $this->uri->uri_string();
    }

    private function method()
    {
        return $this->input->method(TRUE);
    }

    private function ipAddress()
    {
        return $this->input->ip_address();
    }

    private function userAgent()
    {
        return $this->input->user_agent();
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT));

        $this->output->_display();
        exit;
    }

    private function getBearerToken()
    {
        $authHeader = '';

        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            } elseif (isset($headers['authorization'])) {
                $authHeader = $headers['authorization'];
            }
        }

        if ($authHeader === '') {
            $authHeader = $this->input->server('HTTP_AUTHORIZATION');
        }

        if ($authHeader === '') {
            $authHeader = $this->input->server('REDIRECT_HTTP_AUTHORIZATION');
        }

        if (!$authHeader) {
            return '';
        }

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function requireBearerToken($requiredScope = 'read:alumni')
    {
        $plainToken = $this->getBearerToken();

        if ($plainToken === '') {
            $this->apiModel->logUsage(
                null,
                $this->endpoint(),
                $this->method(),
                401,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Missing bearer token. Use Authorization: Bearer YOUR_API_KEY.'
            ], 401);
        }

        $apiKey = $this->apiModel->verifyBearerToken($plainToken);

        if (!$apiKey) {
            $this->apiModel->logUsage(
                null,
                $this->endpoint(),
                $this->method(),
                401,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid or revoked API key.'
            ], 401);
        }

        if (!$this->apiModel->hasScope($apiKey, $requiredScope)) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                403,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'API key does not have permission to access this endpoint.'
            ], 403);
        }

        $this->apiModel->updateLastUsed($apiKey['id']);

        return $apiKey;
    }

    public function health()
    {
        $this->jsonResponse([
            'status' => 'success',
            'message' => 'AR Alumni Public API is running.',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }

    public function featured_today()
    {
        $apiKey = $this->requireBearerToken('read:alumni');

        $featureDate = date('Y-m-d');
        $featured = $this->apiModel->getFeaturedByDate($featureDate);

        if (!$featured) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                404,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'not_found',
                'message' => 'No featured alumnus selected for today.',
                'feature_date' => $featureDate
            ], 404);
        }

        $this->apiModel->logUsage(
            $apiKey['id'],
            $this->endpoint(),
            $this->method(),
            200,
            $this->ipAddress(),
            $this->userAgent()
        );

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Featured alumnus retrieved successfully.',
            'data' => $featured
        ], 200);
    }

    public function featured_by_date($date)
    {
        $apiKey = $this->requireBearerToken('read:alumni');

        $d = DateTime::createFromFormat('Y-m-d', $date);

        if (!$d || $d->format('Y-m-d') !== $date) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                400,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Date must use YYYY-MM-DD format.'
            ], 400);
        }

        $featured = $this->apiModel->getFeaturedByDate($date);

        if (!$featured) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                404,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'not_found',
                'message' => 'No featured alumnus selected for this date.',
                'feature_date' => $date
            ], 404);
        }

        $this->apiModel->logUsage(
            $apiKey['id'],
            $this->endpoint(),
            $this->method(),
            200,
            $this->ipAddress(),
            $this->userAgent()
        );

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Featured alumnus retrieved successfully.',
            'data' => $featured
        ], 200);
    }

    public function alumni_profile($id)
    {
        $apiKey = $this->requireBearerToken('read:alumni');

        $profile = $this->apiModel->getPublicAlumniProfile($id);

        if (!$profile) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                404,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'not_found',
                'message' => 'Public alumni profile not found.'
            ], 404);
        }

        $this->apiModel->logUsage(
            $apiKey['id'],
            $this->endpoint(),
            $this->method(),
            200,
            $this->ipAddress(),
            $this->userAgent()
        );

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Alumni profile retrieved successfully.',
            'data' => $profile
        ], 200);
    }
}