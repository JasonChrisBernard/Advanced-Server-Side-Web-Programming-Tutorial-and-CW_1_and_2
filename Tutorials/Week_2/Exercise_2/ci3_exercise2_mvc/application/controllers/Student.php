<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Student extends CI_Controller{
    public function details(){
        $this -> load -> view('students_details')
    }
}



?>