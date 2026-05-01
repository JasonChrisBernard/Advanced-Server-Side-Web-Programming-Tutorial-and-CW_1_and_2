<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class University extends CI_Controller {

    private $api_key = 'UNI-API-2026-JASON-9F4A72';

    public function __construct() {
        parent::__construct();

        $this->load->model('Module_model');
        $this->load->model('Student_model');
        $this->load->helper('url');
    }

    private function json_response($status_code, $data) {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function check_api_key() {
        $header_key = $this->input->get_request_header('X-API-KEY');
        $query_key = $this->input->get('api_key');

        if ($header_key === $this->api_key || $query_key === $this->api_key) {
            return true;
        }

        $this->json_response(401, array(
            'status' => false,
            'message' => 'Invalid or missing API key'
        ));

        return false;
    }

    public function modules() {
        if (!$this->check_api_key()) {
            return;
        }

        $modules = $this->Module_model->get_all_modules();

        return $this->json_response(200, array(
            'status' => true,
            'message' => 'Modules loaded successfully',
            'data' => $modules
        ));
    }

    public function module() {
        if (!$this->check_api_key()) {
            return;
        }

        $module_code = $this->input->get('code');

        if (empty($module_code)) {
            return $this->json_response(400, array(
                'status' => false,
                'message' => 'Module code is required'
            ));
        }

        $module_data = $this->Module_model->get_module_with_students($module_code);

        if (!$module_data) {
            return $this->json_response(404, array(
                'status' => false,
                'message' => 'Module not found'
            ));
        }

        return $this->json_response(200, array(
            'status' => true,
            'message' => 'Module details loaded successfully',
            'data' => $module_data
        ));
    }

    public function student() {
        if (!$this->check_api_key()) {
            return;
        }

        $student_no = $this->input->get('student_no');

        if (empty($student_no)) {
            return $this->json_response(400, array(
                'status' => false,
                'message' => 'Student number is required'
            ));
        }

        $student_data = $this->Student_model->get_student_with_modules($student_no);

        if (!$student_data) {
            return $this->json_response(404, array(
                'status' => false,
                'message' => 'Student not found'
            ));
        }

        return $this->json_response(200, array(
            'status' => true,
            'message' => 'Student modules loaded successfully',
            'data' => $student_data
        ));
    }
}