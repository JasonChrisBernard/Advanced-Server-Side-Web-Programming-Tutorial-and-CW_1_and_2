<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Todo_model extends CI_Model
{
    public function add_action($user_id, $action_title)
    {
        $data = array(
            'user_id'      => $user_id,
            'action_title' => $action_title,
            'created_at'   => date('Y-m-d H:i:s')
        );

        return $this->db->insert('todo_actions', $data);
    }

    public function get_actions_by_user($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');

        $query = $this->db->get('todo_actions');

        return $query->result_array();
    }
}