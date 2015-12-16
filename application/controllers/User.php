<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of user
 *
 * @author Hernando Peña <your.name at your.org>
 */
class user extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('role_model');
        $this->load->model('user_role_model');
        $GLOBALS['a'] = [];
        $roles = $this->session->userdata('roles');
        $i = 0;
        if ($roles) {
            foreach ($roles as $role => $row) {
                $GLOBALS['roles'][$i] = $row['class'] . '/' . $row['function'];
                $i++;
            }
        }
    }

//    private function _required_login() {
//        if ($this->session->userdata('user_id') == FALSE) {
//            $this->logout();
//            $this->output->set_output(json_encode(['result' => 0, 'error' => 'You are not authorized']));
//            return false;
//        }
//    }

    public function pass_encoder($pass) {
        echo hash('sha256', $pass . SALT);
    }

    public function index() {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $data['class'] = $this->get_class_name();
        $data['user'] = $this->session->userdata('name');
        $data['roles'] = $GLOBALS['roles'];
        $data['users'] = $this->user_model->get_user();

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('user/user_view');
        $this->load->view('general/inc/footer_view');
    }

    public function unauth() {
        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('user/not_auth');
        $this->load->view('general/inc/footer_view');
    }

    public function add() {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];
        $data['users'] = $this->user_model->get();
        $data['all_roles'] = $this->role_model->get();
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('user/register_view');
        $this->load->view('general/inc/footer_view');
    }

    public function edit($id) {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];

        $this->load->model('user_role_model');

        $data['users'] = $this->user_model->get();
        $user = $this->user_model->get_user($id);
        $data['user_edit'] = $user[0];

        $data['all_roles'] = $this->role_model->get();
        $data['user_edit_roles'] = $this->user_role_model->get(['user_iduser' => $id]);

//        $this->output->enable_profiler(TRUE);

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('user/update_view');
        $this->load->view('general/inc/footer_view');
    }

    public function update() {
        $this->_required_login();
        $this->load->model('user_role_model');

        $this->form_validation->set_rules('name', 'Name', 'required');
        $this->form_validation->set_rules('phone', 'Phone', 'required');
        $this->form_validation->set_rules('login', 'Login', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }
        $id = $this->input->post('id');
        $reports_to = $this->input->post('reports_to');
        $name = $this->input->post('name');
        $phone = $this->input->post('phone');
        $login = $this->input->post('login');
        $email = $this->input->post('email');
        $role = $this->input->post('role');
        $role_value = [];

        $user_id = $this->user_model->update([
            'user_iduser' => $reports_to,
            'name' => $name,
            'phone' => $phone,
            'login' => $login,
            'email' => $email
                ], $id);


        $this->user_role_model->delete(['user_iduser' => $id]);
        $i = 0;
        foreach ($role as $selected) {
            $role_data = [
                'user_iduser' => $id,
                'role_idrole' => $selected
            ];

            $role_value[$i] = $role_data;
            $i++;
        }

        $updated_roles = $this->user_role_model->insertBatch($role_value);
        if ($updated_roles) {
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }

        $this->session->set_userdata(['user_id' => $user_id]);

        if ($user_id) {
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }

        $this->output->set_output(json_encode(['result' => 0, 'error' => 'User not updated.']));
    }

    public function login() {

        $login = $this->input->post('login');
        $password = $this->input->post('password');


        $result = $this->user_model->get([
            'login' => $login,
            'password' => hash('sha256', $password . SALT)
        ]);

        $this->output->set_content_type('application_json');

        if ($result) {
            $this->session->set_userdata(['user_id' => $result[0]['iduser']]);
            $this->session->set_userdata(['name' => $result[0]['name']]);
            $this->session->set_userdata(['login' => $result[0]['login']]);
            $this->session->set_userdata(['brother' => $result[0]['brother']]);
            $this->session->set_userdata(['parent' => $result[0]['user_iduser']]);
            $roles = $this->get_roles($result[0]['iduser']);
            $this->session->set_userdata(['roles' => $roles]);

            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }
        $this->output->set_output(json_encode(['result' => 0]));
    }

    public function get_roles($id) {
        $this->_required_login();
        $result = $this->user_role_model->get_roles($id);
        return $result;
    }

    public function register() {
        $this->_required_login();

        $this->output->set_content_type('application_json');

        $this->form_validation->set_rules('company', 'Company', 'required');
        $this->form_validation->set_rules('name', 'Name', 'required|min_length[4]|max_length[32]');
        $this->form_validation->set_rules('phone', 'Phone', 'required|min_length[4]|max_length[16]');
        $this->form_validation->set_rules('login', 'Login', 'required|min_length[4]|max_length[16]|is_unique[user.login]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[user.email]');
        $this->form_validation->set_rules('status', 'Status', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|max_length[16]|matches[confirm_password]');
        $this->form_validation->set_rules('confirm_password', 'Password Confirmation', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }

        $company = $this->input->post('company');
        $reports_to = $this->input->post('reports_to');
        $name = $this->input->post('name');
        $phone = $this->input->post('phone');
        $login = $this->input->post('login');
        $email = $this->input->post('email');
        $status = $this->input->post('status');
        $password = $this->input->post('password');
        $role = $this->input->post('role');
        $role_value = [];


        $user_id = $this->user_model->insert([
            'company_idcompany' => $company,
            'name' => $name,
            'user_iduser' => $reports_to,
            'phone' => $phone,
            'login' => $login,
            'email' => $email,
            'state' => $status,
            'password' => hash('sha256', $password . SALT),
            'date_added' => date("Y-m-d H:i:s"),
            'date_modified' => date("Y-m-d H:i:s")
        ]);

        if ($user_id) {
            $i = 0;
            foreach ($role as $selected) {
                $role_data = [
                    'user_iduser' => $user_id,
                    'role_idrole' => $selected
                ];

                $role_value[$i] = $role_data;
                $i++;
            }

            $this->user_role_model->insertBatch($role_value);
            $this->session->set_userdata(['user_id' => $user_id]);

            //Sending confirmation mail
            $param['email'] = $email;
            $param['login'] = $login;
            $param['password'] = $password;
            $this->send_mail($param);

            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }
        $this->output->set_output(json_encode(['result' => 0, 'error' => 'User not created.']));
    }

    public function get($id = null) {
        $this->_required_login();

        if ($id != null) {
            $result = $this->user_model->get([
                'iduser' => $id
            ]);
        } else {
            $result = $this->user_model->get();
        }

        $this->output->set_output(json_encode($result));
    }

    public function recursive($id) {
        $this->_required_login();
        $users = $this->user_model->get();
        $result = $this->get_child($id, $users);
        $this->output->set_output(json_encode($result));
    }

    // al valor que se pase se identifica al hijo(s)
    public function get_child($id, $users) {
        $this->_required_login();
        for ($i = 0; $i < count($users); $i++) {
            $user = $users[$i];
            if ($id == $user['user_iduser']) {
                array_push($GLOBALS['a'], $user);
                $this->get_child($user['iduser'], $users);
            }
        }
        return $GLOBALS['a'];
    }

    public function send_mail($param) {
        $this->_required_login();
        $this->load->library('email');

        $this->email->from('service@smith-cargo.com', 'Smith Transportation');
        $this->email->to($param['email']);
        $this->email->cc('another@another-example.com');
        $this->email->bcc('them@their-example.com');

        $this->email->subject('Smith Transportation user account');
        $this->email->message('<div><h1>Smith Transportation</h1></div>
                        <p>New user account</p>
                        <ul>
                            <li>user: ' . $param['login'] . '</li>
                            <li>password: ' . $param['password'] . '</li>
                        </ul>
                        <p>Login with your accoutn <a href="leanstaffing.com/trackngo">here</a></p>');
        $this->email->set_mailtype("html");

        if (!$this->email->send()) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
        }
    }

}
