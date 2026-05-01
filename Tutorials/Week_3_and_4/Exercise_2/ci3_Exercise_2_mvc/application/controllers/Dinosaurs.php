<?php defined("BASEPATH") or exit("No direct script access allowed");
/**
 * @property Dinosaur_model $dinosaurModel
 * @property CI_URI $uri
 */
class Dinosaurs extends CI_Controller{

    public function __construct(){
        parent::__construct();


        $this->load->helper('url');
        $this->load->model('Dinosaur_model', 'dinosaurModel');


    }
    public function periods(){
       $data['periods'] = $this -> dinosaurModel -> get_all_periods();
       $this -> load -> view('periods_view', $data);
    }

    public function getinfo(){
        $period = $this->uri->segment(3);
        $data['info'] = $this -> dinosaurModel->get_period_info($period);
        $this -> load -> view ('period_info_view', $data);
    }
}