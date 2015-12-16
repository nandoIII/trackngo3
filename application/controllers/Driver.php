<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of load
 *
 * @author nando_000
 */
class driver extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('driver_model');
        $roles = $this->session->userdata('roles');
        $i = 0;
        if ($roles) {
            foreach ($roles as $role => $row) {
                $GLOBALS['roles'][$i] = $row['class'] . '/' . $row['function'];
                $i++;
            }
        }
    }

    public function index() {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $data['roles'] = $GLOBALS['roles'];
        $data['class'] = $this->get_class_name();
        $data['user'] = $this->session->userdata('name');
        $data['drivers'] = $this->driver_model->get_driver();

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('driver/driver_view');
        $this->load->view('general/inc/footer_view');
    }

    public function unauth() {
        $data = $this->get_session_user_data();
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('driver/not_auth');
        $this->load->view('general/inc/footer_view');
    }

    public function get($id = null, $sw = null) {
        $this->_required_login();
        $result = $this->driver_model->get();
        if ($sw) {
            $this->output->set_output(json_encode($result));
        }
        return $result;
    }

    public function app_get($id = null) {
//        $this->_required_login();
        $result = $this->driver_model->get($id = null);
        $this->output->set_output(json_encode($result));
    }

    public function carrier_drivers($id) {
        $this->_required_login();
        $this->load->model('carrier_model');
        $carrier = $this->carrier_model->get($id);
        $data['carrier'] = $carrier[0];
        $data['class'] = $this->get_class_name();
        $data['user'] = $this->session->userdata('name');
        $data['drivers'] = $this->driver_model->get(['ts_carrier_idts_carrier' => $id]);
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('driver/driver_view');
        $this->load->view('general/inc/footer_view');
    }

    public function add($id = null) {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $this->load->model('carrier_model');
        $data['carriers'] = $this->carrier_model->get();
        $data['class'] = $this->get_class_name();
        $data['user'] = $this->session->userdata('name');
        $data['roles'] = $GLOBALS['roles'];
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('driver/register_view');
        $this->load->view('general/inc/footer_view');
    }

    public function register() {
        $this->_required_login();
        $this->output->set_content_type('application_json');

//        $this->form_validation->set_rules('name', 'Name', 'required|min_length[2]|max_length[32]');
//        $this->form_validation->set_rules('phone', 'Phone', 'required|min_length[4]|max_length[16]');
//        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[user.email]');
//        $this->form_validation->set_rules('address', 'Address', 'required|min_length[2]|max_length[32]');
//        $this->form_validation->set_rules('city', 'City', 'required|min_length[2]|max_length[32]');
//        $this->form_validation->set_rules('state', 'State', 'required|min_length[2]|max_length[32]');
//        $this->form_validation->set_rules('country', 'Country', 'required|min_length[1]|max_length[32]');
//
//        if ($this->form_validation->run() == FALSE) {
//            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
//            return false;
//        }

        $carrier = $this->input->post('carrier');
        $name = $this->input->post('name');
        $last_name = $this->input->post('last_name');
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');
        $login = $this->input->post('driver_login');
        $pass = $this->input->post('pass');



        $driver_id = $this->driver_model->insert([
            'ts_carrier_idts_carrier' => $carrier,
            'name' => $name,
            'last_name' => $last_name,
            'full_name' => $name . ' ' . $last_name,
            'phone' => $phone,
            'email' => $email,
            'login' => $login,
            'pass' => $pass
        ]);
        if ($driver_id) {
            $param['email'] = $email;
            $param['name'] = $name . ' ' . $last_name;
            $this->send_mail($param);
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }
        $this->output->set_output(json_encode(['result' => 0, 'error' => 'Driver not created.']));
    }

    public function edit($id) {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];

        $this->load->model('carrier_model');
        $data['carriers'] = $this->carrier_model->get();

        $driver = $this->driver_model->get_driver($id);

        $data['driver'] = $driver[0];

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('driver/update_view');
        $this->load->view('general/inc/footer_view');
    }

    public function update() {
        $this->_required_login();

        $this->form_validation->set_rules('name', 'Name', 'required');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required');
        $this->form_validation->set_rules('phone', 'Phone', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required');
        $this->form_validation->set_rules('login', 'Login', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }
        $id = $this->input->post('id');
        $carrier = $this->input->post('carrier');
        $name = $this->input->post('name');
        $last_name = $this->input->post('last_name');
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');
        $login = $this->input->post('login');

        $user_id = $this->driver_model->update([
            'ts_carrier_idts_carrier' => $carrier,
            'name' => $name,
            'last_name' => $last_name,
            'full_name' => $name . ' ' . $last_name,
            'phone' => $phone,
            'email' => $email,
            'login' => $login
                ], $id);

        if ($user_id) {
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }

        $this->output->set_output(json_encode(['result' => 0, 'error' => 'Driver not updated.']));
    }

    public function send_mail($param) {
        $this->_required_login();
        $this->load->library('email');

        $this->email->from('service@smith-cargo.com', 'Smith Transportation');
        $this->email->to($param['email']);
        $this->email->cc('another@another-example.com');
        $this->email->bcc('them@their-example.com');

        $this->email->subject('Smith Transportation new driver');
        $this->email->message('<div><h1>Smith Transportation</h1></div>
            <p>New Driver</p>
                        <ul>
                            <li> ' . $param['name'] . ' has been added as a driver in Smith Trackngo System. </li>
                            <li>Download iPhone version <a href="">here</a>.</li>
                            <li>Download Android version <a href="https://play.google.com/store/apps/details?id=com.leanlocation.staffing">here</a>.</li>
                        </ul>');
        $this->email->set_mailtype("html");

        if (!$this->email->send()) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
        }
    }

}
