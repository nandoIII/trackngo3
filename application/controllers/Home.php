<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of home
 *
 * @author nando_000
 */
class home extends CI_Controller {

    public function index() {
        $this->load->view('home/inc/header_view');
        $this->load->view('home/home_view');
        $this->load->view('home/inc/footer_view');
    }
    
    public function register() {
        $this->load->view('home/inc/header_view');
        $this->load->view('home/register_view');
        $this->load->view('home/inc/footer_view');        
    }

    public function test() {
        $this->db->where(['user_id' => 2]);
        $q = $this->db->get_where('user');
        print_r($q->result());
    }

}

?>
