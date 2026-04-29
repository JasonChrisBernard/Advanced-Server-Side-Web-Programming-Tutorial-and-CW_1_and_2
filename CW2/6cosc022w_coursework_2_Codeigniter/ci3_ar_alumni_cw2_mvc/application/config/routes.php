<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$currentPort = isset($_SERVER['SERVER_PORT']) ? (string) $_SERVER['SERVER_PORT'] : '';
$currentHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$isCw1Port = ($currentPort === '8080' || strpos($currentHost, ':8080') !== FALSE);

$route['default_controller'] = $isCw1Port ? 'auth/login' : 'staff_auth/login';

//CW1

$route['register'] = 'auth/register';
$route['verify-sent'] = 'auth/verify_sent';
$route['verify/(:any)'] = 'auth/verify/$1';

$route['login'] = 'auth/login';
$route['logout'] = 'auth/logout';
$route['dashboard'] = 'auth/dashboard';

$route['forgot-password'] = 'auth/forgot_password';
$route['reset-password/(:any)'] = 'auth/reset_password/$1';

$route['devmail'] = 'devmail/index';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


$route['profile'] = 'profile/index';
$route['profile/edit'] = 'profile/edit_basic';
$route['profile/image'] = 'profile/upload_image';

$route['profile/items/(:any)'] = 'profile/items/$1';
$route['profile/items/(:any)/create'] = 'profile/create_item/$1';
$route['profile/items/(:any)/edit/(:num)'] = 'profile/edit_item/$1/$2';
$route['profile/items/(:any)/delete/(:num)'] = 'profile/delete_item/$1/$2';

$route['bidding'] = 'bidding/index';
$route['bidding/place'] = 'bidding/place';
$route['bidding/cancel/(:num)'] = 'bidding/cancel/$1';
$route['bidding/history'] = 'bidding/history';
$route['bidding/run-selection'] = 'bidding/run_selection';
$route['featured-today'] = 'bidding/featured_today';


$route['api-keys'] = 'api_keys/index';
$route['api-keys/create'] = 'api_keys/create';
$route['api-keys/revoke/(:num)'] = 'api_keys/revoke/$1';
$route['api-keys/stats/(:num)'] = 'api_keys/stats/$1';
$route['api-keys/docs'] = 'api_keys/docs';

$route['api/v1/featured-today'] = 'api/featured_today';
$route['api/v1/featured/(:any)'] = 'api/featured_by_date/$1';
$route['api/v1/alumni/(:num)'] = 'api/alumni_profile/$1';

$route['api-docs'] = 'api_docs/index';
$route['api-docs/openapi'] = 'api_docs/openapi';
$route['api/v1/health'] = 'api/health';

//CW2

$route['staff/register'] = 'staff_auth/register';
$route['staff/verify-sent'] = 'staff_auth/verify_sent';
$route['staff/verify/(:any)'] = 'staff_auth/verify/$1';

$route['staff/login'] = 'staff_auth/login';
$route['staff/logout'] = 'staff_auth/logout';
$route['staff/dashboard'] = 'university_dashboard/index';

$route['staff/forgot-password'] = 'staff_auth/forgot_password';
$route['staff/reset-password/(:any)'] = 'staff_auth/reset_password/$1';

$route['staff/devmail'] = 'staff_devmail/index';

$route['staff/dashboard'] = 'university_dashboard/index';
$route['staff/graphs'] = 'university_dashboard/graphs';
$route['staff/analytics/charts'] = 'university_dashboard/chart_data';
$route['staff/reports/charts-export'] = 'university_dashboard/export_chart_data';
$route['staff/alumni'] = 'university_dashboard/alumni';
$route['staff/alumni/export'] = 'university_dashboard/export_alumni';
$route['staff/alumni/(:num)'] = 'university_dashboard/alumni_detail/$1';


$route['api/v1/mobile/alumni-of-day'] = 'api/mobile_alumni_of_day';

$route['api/v1/analytics/summary'] = 'api/analytics_summary';
$route['api/v1/analytics/alumni'] = 'api/analytics_alumni';
$route['api/v1/analytics/certifications'] = 'api/analytics_certifications';
$route['api/v1/analytics/industries'] = 'api/analytics_industries';
$route['api/v1/analytics/charts'] = 'api/analytics_charts';
