<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student_model extends CI_Model {

    public function get_student_with_modules($student_no) {
        $student_no = strtoupper(trim($student_no));

        $student = $this->db
            ->select('student_id, student_no, full_name, email')
            ->from('students')
            ->where('student_no', $student_no)
            ->get()
            ->row_array();

        if (!$student) {
            return false;
        }

        $modules = $this->db
            ->select('m.module_code, m.module_name, m.lecturer')
            ->from('module_students ms')
            ->join('modules m', 'm.module_id = ms.module_id')
            ->join('students s', 's.student_id = ms.student_id')
            ->where('s.student_no', $student_no)
            ->order_by('m.module_code', 'ASC')
            ->get()
            ->result_array();

        return array(
            'student' => $student,
            'modules' => $modules
        );
    }
}