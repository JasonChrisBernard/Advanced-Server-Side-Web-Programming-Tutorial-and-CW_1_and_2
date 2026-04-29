<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'auth/login';

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