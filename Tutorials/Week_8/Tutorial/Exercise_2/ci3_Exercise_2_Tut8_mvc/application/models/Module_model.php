<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Module_model extends CI_Model {

    public function get_all_modules() {
        return $this->db
            ->select('module_id, module_code, module_name, lecturer')
            ->from('modules')
            ->order_by('module_code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_module_with_students($module_code) {
        $module_code = strtoupper(trim($module_code));

        $module = $this->db
            ->select('module_id, module_code, module_name, lecturer')
            ->from('modules')
            ->where('module_code', $module_code)
            ->get()
            ->row_array();

        if (!$module) {
            return false;
        }

        $students = $this->db
            ->select('s.student_no, s.full_name, s.email')
            ->from('module_students ms')
            ->join('students s', 's.student_id = ms.student_id')
            ->join('modules m', 'm.module_id = ms.module_id')
            ->where('m.module_code', $module_code)
            ->order_by('s.full_name', 'ASC')
            ->get()
            ->result_array();

        return array(
            'module' => $module,
            'students' => $students
        );
    }
}