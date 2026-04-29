<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_docs extends CI_Controller
{
    public function index()
    {
        $data = [
            'title' => 'AR Alumni Scoped API Documentation'
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('api_docs/swagger_ui', $data);
        $this->load->view('templates/footer');
    }

    public function openapi()
    {
        $serverUrl = rtrim(site_url(''), '/');

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'AR Alumni CW2 Scoped API',
                'description' => 'Bearer-token API for the Mobile AR App and University Analytics Dashboard. Keys are scoped by client platform: mobile_ar_app uses read:alumni_of_day, while analytics_dashboard uses read:analytics and read:alumni.',
                'version' => '2.0.0'
            ],
            'servers' => [
                [
                    'url' => $serverUrl,
                    'description' => 'Local CodeIgniter server'
                ]
            ],
            'tags' => [
                [
                    'name' => 'System',
                    'description' => 'Health checks and API availability'
                ],
                [
                    'name' => 'Mobile AR App',
                    'description' => 'Scoped endpoints for the AR client'
                ],
                [
                    'name' => 'Analytics Dashboard',
                    'description' => 'Scoped endpoints used by charts, reports, and dashboard summaries'
                ],
                [
                    'name' => 'Alumni Profiles',
                    'description' => 'Scoped public alumni profile endpoints'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'API Key',
                        'description' => 'Use an API key generated in the API Keys screen. The key scope must match the endpoint x-required-scope value.'
                    ]
                ],
                'schemas' => [
                    'ApiErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'error'],
                            'message' => ['type' => 'string', 'example' => 'This API key is not scoped for this endpoint.'],
                            'required_scope' => ['type' => 'string', 'example' => 'read:analytics']
                        ]
                    ],
                    'FeaturedAlumnus' => [
                        'type' => 'object',
                        'properties' => [
                            'feature_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-04-29'],
                            'alumni_id' => ['type' => 'integer', 'example' => 5],
                            'full_name' => ['type' => 'string', 'example' => 'Alice Cloud'],
                            'headline' => ['type' => 'string', 'example' => 'Cloud Engineer at SkyStack Solutions'],
                            'profile_image_url' => ['type' => 'string', 'nullable' => true]
                        ]
                    ],
                    'AlumniProfile' => [
                        'type' => 'object',
                        'properties' => [
                            'alumni_id' => ['type' => 'integer', 'example' => 5],
                            'full_name' => ['type' => 'string', 'example' => 'Alice Cloud'],
                            'email' => ['type' => 'string', 'example' => 'alice.cloud@eastminster.ac.uk'],
                            'industry_sector' => ['type' => 'string', 'example' => 'Cloud Computing'],
                            'profile_completion_percent' => ['type' => 'integer', 'example' => 95],
                            'sections' => ['type' => 'object']
                        ]
                    ],
                    'AnalyticsSummary' => [
                        'type' => 'object',
                        'properties' => [
                            'total_alumni' => ['type' => 'integer', 'example' => 24],
                            'total_programmes' => ['type' => 'integer', 'example' => 6],
                            'total_industries' => ['type' => 'integer', 'example' => 8],
                            'total_certifications' => ['type' => 'integer', 'example' => 31],
                            'total_courses' => ['type' => 'integer', 'example' => 28],
                            'total_current_jobs' => ['type' => 'integer', 'example' => 19],
                            'average_profile_completion' => ['type' => 'number', 'example' => 86.5],
                            'current_employment_rate' => ['type' => 'number', 'example' => 79.2]
                        ]
                    ],
                    'ChartPoint' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string', 'example' => 'Cloud Computing'],
                            'total' => ['type' => 'integer', 'example' => 7]
                        ]
                    ],
                    'ChartDataset' => [
                        'type' => 'object',
                        'properties' => [
                            'generated_at' => ['type' => 'string', 'example' => '2026-04-29 18:30:00'],
                            'stats' => ['$ref' => '#/components/schemas/AnalyticsSummary'],
                            'charts' => [
                                'type' => 'object',
                                'properties' => [
                                    'programmes' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'industries' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'graduation_years' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'certifications' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'courses' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'profile_completion' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'employment_by_industry' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']],
                                    'top_employers' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ChartPoint']]
                                ]
                            ],
                            'insights' => ['type' => 'array', 'items' => ['type' => 'object']]
                        ]
                    ]
                ]
            ],
            'paths' => [
                '/api/v1/health' => [
                    'get' => [
                        'tags' => ['System'],
                        'summary' => 'Check API health',
                        'description' => 'Returns API status without requiring a bearer token.',
                        'security' => [],
                        'responses' => [
                            '200' => ['description' => 'API is running']
                        ]
                    ]
                ],
                '/api/v1/mobile/alumni-of-day' => $this->protectedGet(
                    ['Mobile AR App'],
                    'Get today\'s AR Alumni of the Day',
                    'Requires scope read:alumni_of_day. Intended for mobile_ar_app keys.',
                    'read:alumni_of_day',
                    '#/components/schemas/FeaturedAlumnus'
                ),
                '/api/v1/featured-today' => $this->protectedGet(
                    ['Mobile AR App'],
                    'Get today\'s featured alumnus',
                    'Alias for AR clients that still call the original CW1 endpoint. Requires scope read:alumni_of_day.',
                    'read:alumni_of_day',
                    '#/components/schemas/FeaturedAlumnus'
                ),
                '/api/v1/featured/{date}' => $this->protectedGet(
                    ['Mobile AR App'],
                    'Get featured alumnus by date',
                    'Requires scope read:alumni_of_day and a date in YYYY-MM-DD format.',
                    'read:alumni_of_day',
                    '#/components/schemas/FeaturedAlumnus',
                    [
                        [
                            'name' => 'date',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string', 'format' => 'date', 'example' => '2026-04-29']
                        ]
                    ]
                ),
                '/api/v1/alumni/{id}' => $this->protectedGet(
                    ['Alumni Profiles'],
                    'Get public alumni profile',
                    'Requires scope read:alumni. Intended for analytics_dashboard keys that need profile-level drill-down data.',
                    'read:alumni',
                    '#/components/schemas/AlumniProfile',
                    [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer', 'example' => 5]
                        ]
                    ]
                ),
                '/api/v1/analytics/summary' => $this->protectedGet(
                    ['Analytics Dashboard'],
                    'Get analytics summary totals',
                    'Requires scope read:analytics.',
                    'read:analytics',
                    '#/components/schemas/AnalyticsSummary'
                ),
                '/api/v1/analytics/alumni' => $this->protectedGet(
                    ['Analytics Dashboard'],
                    'Get alumni records for analytics tables',
                    'Requires scope read:alumni.',
                    'read:alumni'
                ),
                '/api/v1/analytics/certifications' => $this->protectedGet(
                    ['Analytics Dashboard'],
                    'Get certification analytics',
                    'Requires scope read:analytics.',
                    'read:analytics'
                ),
                '/api/v1/analytics/industries' => $this->protectedGet(
                    ['Analytics Dashboard'],
                    'Get industry analytics',
                    'Requires scope read:analytics.',
                    'read:analytics'
                ),
                '/api/v1/analytics/charts' => $this->protectedGet(
                    ['Analytics Dashboard'],
                    'Get all chart datasets and insights',
                    'Requires scope read:analytics. Provides the 6-8 chart datasets used by the dashboard and report screen.',
                    'read:analytics',
                    '#/components/schemas/ChartDataset'
                )
            ]
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function protectedGet($tags, $summary, $description, $scope, $dataSchema = null, $parameters = [])
    {
        $responseSchema = [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'example' => 'success'],
                'message' => ['type' => 'string', 'example' => 'Request completed successfully.'],
                'client_platform' => ['type' => 'string', 'example' => 'analytics_dashboard'],
                'required_scope' => ['type' => 'string', 'example' => $scope],
                'data' => $dataSchema ? ['$ref' => $dataSchema] : ['type' => 'array', 'items' => ['type' => 'object']]
            ]
        ];

        return [
            'get' => [
                'tags' => $tags,
                'summary' => $summary,
                'description' => $description,
                'x-required-scope' => $scope,
                'security' => [
                    [
                        'BearerAuth' => []
                    ]
                ],
                'parameters' => $parameters,
                'responses' => [
                    '200' => [
                        'description' => 'Scoped request succeeded',
                        'content' => [
                            'application/json' => [
                                'schema' => $responseSchema
                            ]
                        ]
                    ],
                    '401' => $this->errorResponse('Missing, invalid, or revoked bearer token'),
                    '403' => $this->errorResponse('API key exists but does not contain the required scope'),
                    '429' => $this->errorResponse('Rate limit exceeded')
                ]
            ]
        ];
    }

    private function errorResponse($description)
    {
        return [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/ApiErrorResponse'
                    ]
                ]
            ]
        ];
    }
}
