<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class University_view extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
    }

    public function modules() {
        $this->load->view('modules_view');
    }

    public function student() {
        $this->load->view('student_view');
    }
}