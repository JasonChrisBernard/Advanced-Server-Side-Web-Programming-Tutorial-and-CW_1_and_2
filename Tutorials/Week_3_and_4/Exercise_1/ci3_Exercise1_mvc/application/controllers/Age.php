<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property Age_model $ageModel
 * @property CI_Input $input
 */
class Age extends CI_Controller{
    public function __construct(){
        parent::__construct();

        $this->load->model('Age_model','ageModel');


    }
    public function index(){
        $this->load->view('dob_form');

    }

    public function calculate(){
        $dateOfBirth = $this -> input -> post('date_of_birth');

        $ageString = $this->ageModel->calculate_age($dateOfBirth);

        $data['age_result'] = $ageString;

        $this -> load -> view('age_result', $data);

    }

    
}


?>