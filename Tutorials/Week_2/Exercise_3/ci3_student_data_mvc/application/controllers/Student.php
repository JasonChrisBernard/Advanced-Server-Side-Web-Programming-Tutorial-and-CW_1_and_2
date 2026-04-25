<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Student extends CI_Controller{
    public function details(){
        $data['s_name'] = "Jason Bernard";
        $data['s_age'] = 22;
        $data['s_course'] = "Software Engineering";
        $data['s_id'] = "W1912783";
        $data['picture'] = "https://miro.medium.com/v2/resize:fit:1400/1*CEGmzCboef_rJ6si2eiExQ.png";
        $this -> load -> view('students_details', $data);
    }
}



?>