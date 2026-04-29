<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Api_model', 'apiModel');
        $this->load->model('Analytics_model', 'analyticsModel');

        $this->configureCors();

        if ($this->input->method(TRUE) === 'OPTIONS') {
            $this->output->set_status_header(204)->set_output('');
            $this->output->_display();
            exit;
        }
    }

    private function configureCors()
    {
        $origin = $this->input->server('HTTP_ORIGIN');
        $allowedOrigins = $this->config->item('api_allowed_origins');

        if (!is_array($allowedOrigins)) {
            $allowedOrigins = [];
        }

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Max-Age: 600');
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

    private function requireScope($requiredScope)
    {
        $plainToken = $this->getBearerToken();

        if ($plainToken === '') {
            $this->apiModel->logUsage(
                null,
                $this->endpoint(),
                $this->method(),
                $requiredScope,
                'missing_token',
                401,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Missing bearer token. Use Authorization: Bearer YOUR_API_KEY.',
                'required_scope' => $requiredScope
            ], 401);
        }

        $apiKey = $this->apiModel->verifyBearerToken($plainToken);

        if (!$apiKey) {
            $this->apiModel->logUsage(
                null,
                $this->endpoint(),
                $this->method(),
                $requiredScope,
                'invalid_or_revoked_token',
                401,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid or revoked API key.',
                'required_scope' => $requiredScope
            ], 401);
        }

        if (!$this->apiModel->hasScope($apiKey, $requiredScope)) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                $requiredScope,
                'forbidden_scope',
                403,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'This API key is not scoped for this endpoint.',
                'client_platform' => $apiKey['client_platform'],
                'key_scopes' => explode(',', $apiKey['scopes']),
                'required_scope' => $requiredScope
            ], 403);
        }

        $this->enforceRateLimit($apiKey, $requiredScope);
        $this->apiModel->updateLastUsed($apiKey['id']);

        return $apiKey;
    }

    private function enforceRateLimit($apiKey, $requiredScope)
    {
        $limit = (int) $this->config->item('api_rate_limit_per_minute');

        if ($limit <= 0) {
            return;
        }

        $windowStart = date('Y-m-d H:i:s', time() - 60);
        $recentRequests = $this->apiModel->countRecentUsage($apiKey['id'], $this->ipAddress(), $windowStart);

        if ($recentRequests < $limit) {
            return;
        }

        $this->apiModel->logUsage(
            $apiKey['id'],
            $this->endpoint(),
            $this->method(),
            $requiredScope,
            'rate_limited',
            429,
            $this->ipAddress(),
            $this->userAgent()
        );

        $this->jsonResponse([
            'status' => 'error',
            'message' => 'Rate limit exceeded. Try again in one minute.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'limit_per_minute' => $limit
        ], 429);
    }

    private function logSuccess($apiKey, $requiredScope, $statusCode = 200)
    {
        $this->apiModel->logUsage(
            $apiKey['id'],
            $this->endpoint(),
            $this->method(),
            $requiredScope,
            'allowed',
            $statusCode,
            $this->ipAddress(),
            $this->userAgent()
        );
    }

    public function health()
    {
        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Scoped AR Alumni API is running.',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }

    public function mobile_alumni_of_day()
    {
        $requiredScope = 'read:alumni_of_day';
        $apiKey = $this->requireScope($requiredScope);

        $featured = $this->apiModel->getAlumniOfDay();

        if (!$featured) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                $requiredScope,
                'not_found',
                404,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'not_found',
                'message' => 'No Alumni of the Day selected for today.'
            ], 404);
        }

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Mobile AR Alumni of the Day retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $featured
        ], 200);
    }

    public function featured_today()
    {
        $this->featured_by_date(date('Y-m-d'));
    }

    public function featured_by_date($featureDate)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $featureDate)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Feature date must use YYYY-MM-DD format.'
            ], 400);
        }

        $requiredScope = 'read:alumni_of_day';
        $apiKey = $this->requireScope($requiredScope);
        $featured = $this->apiModel->getFeaturedByDate($featureDate);

        if (!$featured) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                $requiredScope,
                'not_found',
                404,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'not_found',
                'message' => 'No Alumni of the Day selected for this date.',
                'required_scope' => $requiredScope
            ], 404);
        }

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Featured alumnus retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $featured
        ], 200);
    }

    public function alumni_profile($id)
    {
        $requiredScope = 'read:alumni';
        $apiKey = $this->requireScope($requiredScope);
        $profile = $this->apiModel->getPublicAlumniProfile($id);

        if (!$profile) {
            $this->apiModel->logUsage(
                $apiKey['id'],
                $this->endpoint(),
                $this->method(),
                $requiredScope,
                'not_found',
                404,
                $this->ipAddress(),
                $this->userAgent()
            );

            $this->jsonResponse([
                'status' => 'not_found',
                'message' => 'Public alumni profile not found.',
                'required_scope' => $requiredScope
            ], 404);
        }

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Public alumni profile retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $profile
        ], 200);
    }

    public function analytics_summary()
    {
        $requiredScope = 'read:analytics';
        $apiKey = $this->requireScope($requiredScope);

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Analytics summary retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $this->apiModel->getAnalyticsSummary()
        ], 200);
    }

    public function analytics_alumni()
    {
        $requiredScope = 'read:alumni';
        $apiKey = $this->requireScope($requiredScope);

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Analytics alumni records retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $this->apiModel->getAnalyticsAlumni()
        ], 200);
    }

    public function analytics_certifications()
    {
        $requiredScope = 'read:analytics';
        $apiKey = $this->requireScope($requiredScope);

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Certification analytics retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $this->apiModel->getCertificationAnalytics()
        ], 200);
    }

    public function analytics_industries()
    {
        $requiredScope = 'read:analytics';
        $apiKey = $this->requireScope($requiredScope);

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Industry analytics retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $this->apiModel->getIndustryAnalytics()
        ], 200);
    }

    public function analytics_charts()
    {
        $requiredScope = 'read:analytics';
        $apiKey = $this->requireScope($requiredScope);

        $this->logSuccess($apiKey, $requiredScope);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Analytics chart datasets retrieved successfully.',
            'client_platform' => $apiKey['client_platform'],
            'required_scope' => $requiredScope,
            'data' => $this->analyticsModel->getDashboardDataset()
        ], 200);
    }
}
