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
class load extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('load_model');
        $roles = $this->session->userdata('roles');
        $i = 0;
        if ($roles) {
            foreach ($roles as $role => $row) {
                $GLOBALS['roles'][$i] = $row['class'] . '/' . $row['function'];
                $i++;
            }
        } else {
            $GLOBALS['roles'][0] = 'load/index';
        }
        $GLOBALS['a'] = [];
    }

    public function test2() {
        $this->load->view('load/inc/header_view');
        $this->load->view('load/load_view');
        $this->load->view('load/inc/footer_view');
    }

    public function index() {
        $this->_required_login();

        $this->load->library('pagination');
        $this->load->library('table');

        //pagination
        $config['base_url'] = site_url('load/index');
        $config['per_page'] = 10;
        $config['num_links'] = 5;
        $config['total_rows'] = count($this->get_load_view('x', 0, 1));
        $this->pagination->initialize($config);

        $this->load->model('driver_model');
        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];
        $this->load->model('carrier_model');
        $data['carriers'] = $this->carrier_model->get();
        $this->load->model('customer_model');
        $data['customers'] = $this->customer_model->get();
        $data['drivers'] = $this->driver_model->get();

        $data['loads'] = $this->get_load_view('x', 0, 1, 'date_created', 'desc', $config['per_page'], $this->uri->segment(3));
//        print_r($data['loads']);
//        $this->output->set_output(json_encode($data['loads']));
//        return false;
//        $this->output->enable_profiler(TRUE);

        $config['num_rows'] = count($data['loads']);
        $this->pagination->initialize($config);

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/load_view2');
        $this->load->view('general/inc/footer_view');
    }

    public function index2($param) {
        $this->load->view('upload/upload_form', array('error' => ' '));
    }

    public function do_upload_form() {

        // Detect form submission.
        if ($this->input->post('submit')) {
            $path = './uploads/';
            $this->load->library('upload');

            /**
             * Refer to https://ellislab.com/codeigniter/user-guide/libraries/file_uploading.html 
             * for full argument documentation.
             *
             */
            // Define file rules
            $this->upload->initialize(array(
                "upload_path" => $path,
                "allowed_types" => "gif|jpg|png",
                "max_size" => '100',
                "max_width" => '1024',
                "max_height" => '768'
            ));

            if ($this->upload->do_multi_upload("uploadfile")) {

                $data['upload_data'] = $this->upload->get_multi_upload_data();

                echo '<p class = "bg-success">' . count($data['upload_data']) . 'File(s) successfully uploaded.</p>';
            } else {
                // Output the errors
                $errors = array('error' => $this->upload->display_errors('<p class = "bg-danger">', '</p>'));

                foreach ($errors as $k => $error) {
                    echo $error;
                }
            }
        } else {
            echo '<p class = "bg-danger">An error occured, please try again later.</p>';
        }
        // Exit to avoid further execution
        exit();
    }

    public function unauth() {
        $data = $this->get_session_user_data();
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/not_auth');
        $this->load->view('general/inc/footer_view');
    }

    public function recursive($id) {
        $this->load->model('user_model');
        $users = $this->user_model->get();
        $result = $this->get_child($id, $users);
        return $result;
//        $this->output->set_output(json_encode($result));
    }

    // al valor que se pase se identifica al hijo(s)
    public function get_child($id, $users) {
        for ($i = 0; $i < count($users); $i++) {
            $user = $users[$i];
            if ($id == $user['user_iduser']) {
                array_push($GLOBALS['a'], $user['iduser']);
                $this->get_child($user['iduser'], $users);
            }
        }
        return $GLOBALS['a'];
    }

    public function load_details($id, $sw = 0) {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $this->load->model('driver_model');
        $this->load->model('load_trace_model');
        $this->load->model('shipment_model');

        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];

        $data['load'] = $this->get_load_view($id, $sw = 0);
        $driver_id = $data['load'][0]['ts_driver_idts_driver'];
        $data['driver'] = $this->driver_model->get($driver_id);
        $data['callchecks'] = $this->get_chat($id);
        $shipments = $this->shipment_model->get_shipment(['ts_load_idts_load' => $id]);
        for ($i = 0; $i < count($shipments); $i++) {
            $ship_contacts = $this->get_shipment_contacts($shipments[$i]['idshipment']);
            $shipments[$i]['contacts'] = $ship_contacts;
        }
        $data['shipments'] = $shipments;

        $result = $this->load_trace_model->get(['ts_load_idts_load' => $id], 'date', 'asc');

        for ($i = 0; $i < count($result); $i++) {
            $driver_address = json_decode($this->get_driver_address($result[$i]['lat'], $result[$i]['lng']));
            $result[$i]['driver_address'] = $driver_address->results[0]->formatted_address;
        }
        $data['traces'] = $result;

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/load_details');
        $this->load->view('general/inc/footer_view');
    }

    public function get_shipment_contacts($id, $json = null) {
        $this->load->model('shipment_customer_contact_model');
        $result = $this->shipment_customer_contact_model->get_contacts(['shipment_idshipment' => $id]);
        if ($json) {
            $this->output->set_output(json_encode($result));
            return false;
        }
        return $result;
    }

    public function get_load_trace($load_id, $json = null) {
        $this->load->model('load_trace_model');
        $result = $this->load_trace_model->get(['ts_load_idts_load' => $load_id], 'date', 'asc');
        if ($json) {
            $this->output->set_output(json_encode($result));
            return false;
        }
        return $result;
    }

    public function get_chat($id, $sw = null) {
        $this->load->model('callcheck_model');
        $result = $this->callcheck_model->get_chat([
            'ts_load_idts_load' => $id
        ]);

        if ($sw) {
            $this->output->set_output(json_encode($result));
            return false;
        }
        return $result;
    }

    public function app_get_chat($id) {
        $this->load->model('callcheck_model');
        $result = $this->callcheck_model->get_chat([
            'ts_load_idts_load' => $id
        ]);

        $this->output->set_output(json_encode($result));
    }

    public function get_last_msg($id, $sw = null) {
        $this->load->model('callcheck_model');
        $last_date = $this->input->post('last_date');
        $result = $this->callcheck_model->get_chat([
            'ts_load_idts_load' => $id
                ], $last_date);
        if ($sw) {
            $this->output->set_output(json_encode($result));
            return false;
        }

        return $result;
    }

    public function set_driver_msg() {
        $this->load->model('callcheck_model');

        $user_id = $this->input->post('user_id');
        $load_id = $this->input->post('load_id');
        $type = $this->input->post('type');
        $comment = $this->input->post('comment');

        $result = $this->callcheck_model->insert([
            'user_iduser' => $user_id,
            'ts_load_idts_load' => $load_id,
            'type' => $type,
            'driver' => 1,
            'comment' => $comment,
            'date' => now()
        ]);

        if ($result) {
            $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Callcheck added.']));
            return false;
        }
        $this->output->set_output(json_encode(['result' => 0, 'error' => 'Callcheck not added']));
    }

    public function send_driver_mail($param) {
        $email = $param['email'];
        $customer = $param['customer'];
        $origin = $param['origin'];
        $destination = $param['destination'];

        $to = "$email";
        $subject = "Load assigned";
// Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
        $headers .= 'From: <service@smith.com>' . "\r\n";

        $message = '<div><h1>Smith Transportation</h1></div>
                        <p>Load assigned to you</p>
                        <ul>
                            <li>Customer: ' . $customer . '</li>
                            <li>Origin: ' . $origin . '</li>
                            <li>Destination: ' . $destination . '</li>
                        </ul>
                        <p>Check the smith app to see load details.</p>';
        mail($to, $subject, $message, $headers);
    }

    public function add() {
        $this->_required_login();
        $data = $this->get_session_user_data();
        $this->load->model('carrier_model');
        $data['carriers'] = $this->carrier_model->get();
        $this->load->model('driver_model');
        $data['drivers'] = $this->driver_model->get();
        $this->load->model('customer_model');
        $data['customers'] = $this->customer_model->get();
        $data['last_load'] = $this->load_model->get(null, 'idts_load', 'desc', 1);
//        $this->output->enable_profiler(TRUE);
//        print_r($data['last_load']);

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/register_view');
        $this->load->view('general/inc/footer_view');
    }

    public function add2() {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());

        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];

        $this->load->model('carrier_model');
        $data['carriers'] = $this->carrier_model->get();

        $this->load->model('customer_model');
        $data['customers'] = $this->customer_model->get();

        $this->load->model('customer_contact_model');
        $fisrt_customer = $data['customers'][0];
        $data['first_customer_contacts'] = $this->customer_contact_model->get(['ts_customer_idts_customer' => $fisrt_customer['idts_customer']]);

        $last_load = $this->load_model->get(null, 'load_number', 'desc', 1);
        if (count($last_load) >= 1) {
            $start = $last_load[0];
        } else {
            $start = ['load_number' => 1630];
        }

        $data['last_load'] = $start;
        echo'<br>';
//        print_r($last_load[0]);
//        $data['last_load'] = $last_load[0];
        $data['error'] = '';

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/register3_view');
        $this->load->view('general/inc/footer_view');
    }

    function enable($id) {
        $load_id = $this->load_model->update([
            'sw' => '1',
                ], $id);
    }

    function upload_file() {
        $status = "";
        $msg = "";
        $file_element_name = 'userfile';

        if (empty($_POST['customer'])) {
            $status = "error";
            $msg = "Please enter a title";
        }

        if ($status != "error") {
            $config['upload_path'] = '././tkgo_files2/';
            $config['allowed_types'] = 'pdf|gif|jpg|png|doc|txt';
//            $config['max_size'] = 1024 * 8;
//            $config['encrypt_name'] = TRUE;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload($file_element_name)) {
                $status = 'error';
                $msg = $this->upload->display_errors('', '');
            } else {
                $data = $this->upload->data();
//                $file_id = $this->files_model->insert_file($data['file_name'], $_POST['title']);
//                if ($file_id) {
//                    $status = "success";
//                    $msg = "File successfully uploaded";
//                } else {
//                    unlink($data['full_path']);
//                    $status = "error";
//                    $msg = "Something went wrong when saving the file, please try again.";
//                }
            }
//            @unlink($_FILES[$file_element_name]);
        }
        echo json_encode(array('status' => $status, 'msg' => $msg));
    }

    function select_validate($post_string) {
        return $post_string == '0' ? FALSE : TRUE;
    }

    function do_upload() {

//        echo 'total files: ' . count($_FILES['uploadfile']);
//        for ($i = 1; $i < 3; $i++) {
//            print_r(json_decode($this->input->post('ship_contacts_' . $i)));
//        }

        date_default_timezone_set("America/New_York");
        $this->output->set_content_type('application_json');

        $this->load->model('shipment_model');
        $this->load->model('shipment_customer_contact_model');

        $this->form_validation->set_rules('carrier', 'Carrier', 'required|callback_select_validate');
        $this->form_validation->set_rules('driver', 'Driver', 'required|callback_select_validate');

        $this->form_validation->set_message('select_validate', 'Please select a %s');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['status' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }

        //check shipment in database
        $sw_bol_number = 0;
        $repeat_bol_number = '';
        $missing = '';
        $shipments = json_decode($this->input->post('shipments'));
        for ($i = 0; $i < count($shipments); $i++) {
            $shipment_data = array(
                'bol_number' => $shipments[$i]->bol_number,
            );
            $shp_id = $this->shipment_model->get($shipment_data);
            if ($shp_id) {
                $sw_bol_number = 1;
                $repeat_bol_number = $shp_id[0]['bol_number'];
            }
        }

        if ($sw_bol_number) {
            $this->output->set_output(json_encode(['status' => 0, 'error' => ['bol_number' => 'BOL #' . $repeat_bol_number . ' is already registered.']]));
            return false;
        }

        $carrier = $this->input->post('carrier');
        $driver = $this->input->post('driver');
        $load_number = $this->input->post('load_number');
        $tender = $this->input->post('status') ? $this->input->post('status') : 0;
//        $shipments = $this->input->post('shipments');

        $load_id = $this->load_model->insert([
            'user_iduser' => $this->session->userdata('user_id'),
            'ts_carrier_idts_carrier' => $carrier,
            'ts_driver_idts_driver' => $driver,
            'tender' => $tender,
            'load_number' => $load_number,
            'date_created' => date('Y-m-d H:i:s')
        ]);

//        $this->pdf_image($load_number);
//        print_r($shipments);
//        return false;
        if ($load_id) {
            //Insert Shipments
            if (count($shipments) > 0) {
                $path = '../tkgo_files2/';
                $this->load->library('upload');

                $this->upload->initialize(array(
                    "upload_path" => $path,
                    "allowed_types" => "pdf|gif|jpg|png",
                    "max_size" => '100',
                    "max_width" => '1024',
                    "max_height" => '768'
                ));

                if ($this->upload->do_multi_upload("uploadfile", $load_id, $shipments)) {

                    $data['upload_data'] = $this->upload->get_multi_upload_data();


                    $pdf_pages_number = [];
                    $n = 0;
                    foreach ($data['upload_data'] as $k => $info) {

                        $pdf_pages_number[$n] = $info['pages_number'];
                        $n++;
                    }

                    $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Load created.']));
                } else {

                    $errors = array('error' => $this->upload->display_errors('<p class = "bg-danger">', '</p>'));

                    foreach ($errors as $k => $error) {
                        echo $error;
                    }
                }


                //**************** Insert Shipments in Database **************************

                for ($i = 0; $i < count($shipments); $i++) {
                    $shipment_data = array(
                        'ts_load_idts_load' => $load_id,
                        'ts_customer_idts_customer' => $shipments[$i]->customer,
                        'pickup_address' => $shipments[$i]->pickup,
                        'pickup_number' => $shipments[$i]->pickup_number,
                        'pickup_zipcode' => $shipments[$i]->pickup_zipcode,
                        'pickup_lat' => $shipments[$i]->pickup_lat,
                        'pickup_lng' => $shipments[$i]->pickup_lng,
                        'drop_address' => $shipments[$i]->drop,
                        'drop_number' => $shipments[$i]->drop_number,
                        'drop_zipcode' => $shipments[$i]->drop_zipcode,
                        'drop_lat' => $shipments[$i]->drop_lat,
                        'drop_lng' => $shipments[$i]->drop_lng,
                        'bol_number' => $shipments[$i]->bol_number,
                        'pages_number' => is_array($pdf_pages_number) ? $pdf_pages_number[$i] : 0,
                        'url_bol' => $load_id . '_bol_' . $shipments[$i]->bol_number . '.pdf',
                        'date_created' => date("Y-m-d H:i:s")
                    );

                    //insert shipments in database
                    $shp_id = $this->shipment_model->insert($shipment_data);

                    //Insert  contacts in database
                    $index = $i + 1;
                    $ship_contacts = json_decode($this->input->post('ship_contacts_' . $index));
                    if ($ship_contacts) {
                        $ship_contacts_value = [];
                        for ($j = 0; $j < count($ship_contacts); $j++) {
                            $ship_contacts_data = array(
                                'shipment_idshipment' => $shp_id,
                                'ts_customer_contact_idts_customer_contact' => $ship_contacts[$j]->contact_id
                            );
                            $ship_contacts_value[$j] = $ship_contacts_data;
                            $email_data['email'] = $ship_contacts[$j]->email;
                            $email_data['bol'] = $shipments[$i]->bol_number;
                            $email_data['load_id'] = $load_id;
                            $this->send_ship_contact_email($email_data);
                        }
                        $this->shipment_customer_contact_model->insertBatch($ship_contacts_value);
                    }
                }
//            print_r($rate_value);
//                $this->shipment_model->insertBatch($shipment_value);
            }

            //Send notification to the driver
            $this->load->model('driver_model');

            //send push notification
            if ($tender == 1) {
                $drivers = $this->driver_model->get($this->input->post('driver'));
                $driver = $drivers[0];
                $this->push_not_new_load($this->input->post('load_number'), $driver['app_id'], $driver['apns_number']);
            }

            $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Load created.']));
//            $loads = $this->get_load_view(['idts_load' => $load_id]);
//            $load = $loads[0];
//
//            $param['email'] = $load['driver_email'];
//            $param['customer'] = $load['customer_name'];
//            $param['load_number'] = $load['load_number'];
//            $this->send_mail_2($param);
//            $this->output->set_output(json_encode(['result' => 1]));
        } else {
            $this->output->set_output(json_encode(['result' => 0, 'error' => 'Load not created.']));
        }

        $this->output->set_output(json_encode(['status' => 1, 'msg' => 'Load Succesfully created.']));
    }

    function send_ship_contact_email($email) {
        $this->load->library('email');
        $this->email->clear(true);
//        $msg = $this->load->view('load/shipment_mail_view', $data, true);
        $msg = 'Check BOL attached';


        $this->email->from('service@smith-cargo.com');
        $this->email->to($email['email']);
//        $this->email->bcc('willmar@leanstaffing.com, alvarob@leanstaffing.com, hpena@leanstaffing.com');

        $subject = 'Shipment assigned';

        $this->email->subject($subject);
        $this->email->message($msg);
        $this->email->attach('../tkgo_files2/' . $email['load_id'] . '_bol_' . $email['bol'] . '.pdf');
        $this->email->set_mailtype("html");

        if ($this->email->send()) {
            return true;
        }
        return false;
    }

    function do_upload2($id_load) {

        date_default_timezone_set("America/New_York");
//        $this->output->set_content_type('application_json');
        $this->load->model('shipment_model');

        $this->form_validation->set_rules('carrier', 'Carrier', 'required');
        $this->form_validation->set_rules('driver', 'Driver', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }

        $dr = $this->input->post('driver');
        $email = '';

        $carrier = $this->input->post('carrier');
        $driver = $this->input->post('driver');
        $load_number = $this->input->post('load_number');
        $status = $this->input->post('status');
        $update_array = [];
        if ($status == 1) {
            $update_array = [
                'user_iduser' => $this->session->userdata('user_id'),
                'ts_carrier_idts_carrier' => $carrier,
                'ts_driver_idts_driver' => $driver,
                'tender' => $status,
                'load_number' => $load_number
            ];
        } else {
            $update_array = [
                'user_iduser' => $this->session->userdata('user_id'),
                'ts_carrier_idts_carrier' => $carrier,
                'ts_driver_idts_driver' => $driver,
                'load_number' => $load_number
            ];
        }


        //Update load part
        $affected_rows = $this->load_model->update([
            'user_iduser' => $this->session->userdata('user_id'),
            'ts_carrier_idts_carrier' => $carrier,
            'ts_driver_idts_driver' => $driver,
            'tender' => $status,
            'load_number' => $load_number
                ], $id_load);

        /**
         * Upddate shipments
         */
        //update shipments
        $shipments = json_decode($this->input->post('update_shipment'));
        if (count($shipments) > 0) {
            $this->db->update_batch('shipment', $shipments, 'idshipment');
        }

        // insert new shipments
        $new_shipments = json_decode($this->input->post('new_shipment'));

        $this->pdf_image($load_number);

        //upload BOL documents


        $path = '../tkgo_files2/';
        $this->load->library('upload');

        // Define file rules
        $this->upload->initialize(array(
            "upload_path" => $path,
            "allowed_types" => "pdf|gif|jpg|png",
            "max_size" => '100',
            "max_width" => '1024',
            "max_height" => '768'
        ));

        if ($this->upload->do_multi_upload("uploadfile", $id_load, $new_shipments)) {

            $data['upload_data'] = $this->upload->get_multi_upload_data();

//                print_r($data['upload_data']);
            $pdf_pages_number = [];
            $n = 0;
            foreach ($data['upload_data'] as $k => $info) {
//                    echo $k . ': ' . $info['pages_number'] . '\n';
                $pdf_pages_number[$n] = $info['pages_number'];
                $n++;
            }

//                echo '<p class = "bg-success">' . count($data['upload_data']) . 'File(s) successfully uploaded.</p>';
        } else {
            // Output the errors
            $errors = array('error' => $this->upload->display_errors('<p class = "bg-danger">', '</p>'));

            foreach ($errors as $k => $error) {
                echo $error;
            }
        }

        // Insert new Shipments in Database

        if (count($new_shipments) > 0) {
            for ($i = 0; $i < count($new_shipments); $i++) {
                $shipment_data = array(
                    'ts_load_idts_load' => $id_load,
                    'ts_customer_idts_customer' => $new_shipments[$i]->customer,
                    'pickup_address' => $new_shipments[$i]->pickup,
                    'drop_address' => $new_shipments[$i]->drop,
                    'bol_number' => $new_shipments[$i]->bol_number,
                    'pages_number' => is_array($pdf_pages_number) ? $pdf_pages_number[$i] : 0,
                    'url_bol' => $id_load . '_bol_' . $new_shipments[$i]->bol_number . '.pdf',
                    'date_created' => date("Y-m-d H:i:s")
                );
                $shipment_value[$i] = $shipment_data;
            }

            $this->shipment_model->insertBatch($shipment_value);
//            $this->output->enable_profiler(TRUE);
        }

        //Delete shipments in database
        $delete_shipments = json_decode($this->input->post('delete_shipment'), true);

        if (count($delete_shipments) > 0) {
            foreach ($delete_shipments as $delete_shipment => $value) {
                $this->shipment_model->delete($value['idshipment']);
            }
        }

        //Send notification to the driver
        $this->load->model('driver_model');

        //send push notification
        if ($status == 1) {
            $drivers = $this->driver_model->get($this->input->post('driver'));
            $driver = $drivers[0];
            $this->push_not_custom_msg_load($this->input->post('load_number'), $driver['app_id'], $driver['apns_number'], 'check Load #', 'Load Updated');
        }

        $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Load updated.']));

//            $loads = $this->get_load_view(['idts_load' => $load_id]);
//            $load = $loads[0];
//
//            $param['email'] = $load['driver_email'];
//            $param['customer'] = $load['customer_name'];
//            $param['load_number'] = $load['load_number'];
//            $this->send_mail_2($param);
//            $this->output->set_output(json_encode(['result' => 1]));
    }

    function multi_upload() {
        $customer = $this->input->post('customer');
        $carrier = $this->input->post('carrier');
        $driver = $this->input->post('driver');
        $loads = json_decode($this->input->post('loads'), true);

        $i = 0;
        $load_array = [];
        foreach ($loads as $load => $row) {
            $ids['idts_load'] = $row['idts_load'];
            $all[$i] = ['idts_load' => $row['idts_load']];
            $i++;
            array_push($load_array, ['idts_load' => $row['idts_load']]);
        }
        $age = array("Peter" => "35", "Ben" => "37", "Joe" => "43");
        print_r($load_array);
        print_r('AGE: ' . $age);
        $load_id = $this->load_model->update([
            'ts_carrier_idts_carrier' => $carrier,
            'ts_driver_idts_driver' => $driver,
            'ts_customer_idts_customer' => $customer,
            'status' => 1
                ], $load_array);
    }

    function pdf_image($load_number) {
        $imagick = new Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage('../tkgo_files2/' . $load_number . '.pdf');
        $imagick->extentImage(1700, 2200, 0, 0);
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage('../tkgo_files2/' . $load_number . '.jpg');
    }

    function pdf() {
        $this->load->helper('pdf_helper');
        /*
          ---- ---- ---- ----
          your code here
          ---- ---- ---- ----
         */

        $data['class'] = $this->get_class_name();
        $data['user'] = $this->session->userdata('name');

        $this->load->view('load/pdfreport', $data);
    }

    public function register() {

        $this->output->set_content_type('application_json');

        //form validation

        $this->form_validation->set_rules('customer', 'Customer', 'required');
        $this->form_validation->set_rules('driver', 'Driver', 'required');
        $this->form_validation->set_rules('load_number', 'Load Number', 'required');
        $this->form_validation->set_rules('or_name', 'Origin Name', 'required');
        $this->form_validation->set_rules('or_address_1', 'Origin Address 1', 'required');
        $this->form_validation->set_rules('or_zipcode', 'Origin Zipcode', 'required');
        $this->form_validation->set_rules('or_city', 'Origin City', 'required');
        $this->form_validation->set_rules('or_state', 'Origin State', 'required');
        $this->form_validation->set_rules('or_country', 'Origin Country', 'required');
        $this->form_validation->set_rules('dt_name', 'Destination Name', 'required');
        $this->form_validation->set_rules('dt_address_1', 'Destination Address 1', 'required');
        $this->form_validation->set_rules('dt_zipcode', 'Destination Zipcode', 'required');
        $this->form_validation->set_rules('dt_city', 'Destination City', 'required');
        $this->form_validation->set_rules('dt_state', 'Destination State', 'required');
        $this->form_validation->set_rules('dt_country', 'Destination Country', 'required');
//        $this->form_validation->set_rules('name', 'Name', 'required|min_length[4]|max_length[32]');
//        $this->form_validation->set_rules('phone', 'Phone', 'required|min_length[4]|max_length[16]');
//        $this->form_validation->set_rules('login', 'Login', 'required|min_length[4]|max_length[16]|is_unique[user.login]');
//        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[user.email]');
//        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|max_length[16]|matches[confirm_password]');
//        $this->form_validation->set_rules('confirm_password', 'Password Confirmation', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }

        $dr = $this->input->post('driver');
        $email = '';
        switch ($dr) {
            case "1":
                $email = "sergio@smith-cargo.com";
                break;
            case "2":
                $email = "hpena@leanstaffing.com";
                break;

            default:
//                echo "Your favorite color is neither red, blue, nor green!";
        }

        $status = 1;
        $customer = $this->input->post('customer');
        $carrier = $this->input->post('carrier');
        $driver = $this->input->post('driver');
        $load_number = $this->input->post('load_number');

//                echo $dt[0];
//        return false;'
        // --------------- Origin data --------------------

        $or_earliest = $this->input->post('or_earliest');
        $or_latest = $this->input->post('or_latest');
        $or_name = $this->input->post('or_name');
        $or_address_1 = $this->input->post('or_address_1');
        $or_address_2 = $this->input->post('or_address_2');
        $or_zipcode = $this->input->post('or_zipcode');
        $or_city = $this->input->post('or_city');
        $or_state = $this->input->post('or_state');
        $or_country = $this->input->post('or_country');
        $or_contact = $this->input->post('or_contact');
        $or_phone = $this->input->post('or_phone');
        $or_fax = $this->input->post('or_fax');
        $or_email = $this->input->post('or_email');
        $or_comments = $this->input->post('or_comments');
        $pk_lat = $this->input->post('origin_lat');
        $pk_lng = $this->input->post('origin_lng');

        // --------------- Destination data ---------------------

        $dt_earliest = $this->input->post('dt_earliest');
        $dt_latest = $this->input->post('dt_latest');
        $dt_name = $this->input->post('dt_name');
        $dt_address_1 = $this->input->post('dt_address_1');
        $dt_address_2 = $this->input->post('dt_address_2');
        $dt_zipcode = $this->input->post('dt_zipcode');
        $dt_city = $this->input->post('dt_city');
        $dt_state = $this->input->post('dt_state');
        $dt_country = $this->input->post('dt_country');
        $dt_contact = $this->input->post('dt_contact');
        $dt_phone = $this->input->post('dt_phone');
        $dt_fax = $this->input->post('dt_fax');
        $dt_email = $this->input->post('dt_email');
        $dt_comments = $this->input->post('dt_comments');
        $dp_lat = $this->input->post('destination_lat');
        $dp_lng = $this->input->post('destination_lng');

        $items = $this->input->post('items');

//        print_r(json_decode($items));
//        $this->insertItemsLoad(5, json_decode($items));
//        return false;

        $load_id = $this->load_model->insert([
            'user_iduser' => $this->session->userdata('user_id'),
            'ts_carrier_idts_carrier' => $carrier,
            'ts_driver_idts_driver' => $driver,
            'ts_customer_idts_customer' => $customer,
            'status' => $status,
            'load_number' => $load_number,
            'or_earliest' => $or_earliest,
            'or_latest' => $or_latest,
            'or_name' => $or_name,
            'or_address_1' => $or_address_1,
            'or_address_2' => $or_address_2,
            'or_zipcode' => $or_zipcode,
            'or_city' => $or_city,
            'or_state' => $or_state,
            'or_country' => $or_country,
            'or_contact' => $or_contact,
            'or_phone' => $or_phone,
            'or_fax' => $or_fax,
            'or_email' => $or_email,
            'or_comments' => $or_comments,
            'dt_earliest' => $dt_earliest,
            'dt_latest' => $dt_latest,
            'dt_name' => $dt_name,
            'dt_address_1' => $dt_address_1,
            'dt_address_2' => $dt_address_2,
            'dt_zipcode' => $dt_zipcode,
            'dt_city' => $dt_city,
            'dt_state' => $dt_state,
            'dt_country' => $dt_country,
            'dt_contact' => $dt_contact,
            'dt_phone' => $dt_phone,
            'dt_fax' => $dt_fax,
            'dt_email' => $dt_email,
            'dt_comments' => $dt_comments,
            'pk_lat' => $pk_lat,
            'pk_lng' => $pk_lng,
            'dp_lat' => $dp_lat,
            'dp_lng' => $dp_lng,
        ]);

        if ($load_id) {
            $param['email'] = $email;
            $param['customer'] = $customer;
            $param['origin'] = $or_address_1 . ' ' . $or_city . ' ' . $or_state . ' ' . $or_zipcode;
            $param['destination'] = $dt_address_1 . ' ' . $dt_city . ' ' . $dt_state . ' ' . $dt_zipcode;
            $this->insertItemsLoad($load_id, json_decode($items));
            $this->send_mail($param);
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }

        $this->output->set_output(json_encode(['result' => 0, 'error' => 'Load not created.']));
    }

    public function update($id) {
        $this->load->model('driver_model');
        $this->load->model('customer_model');
        $this->load->model('item_model');

        $data['drivers'] = $this->driver_model->get();
        $data['items'] = $this->item_model->get([
            'ts_load_idts_load' => $id
        ]);
        $data['user'] = $this->session->userdata('name');
        $data['customers'] = $this->customer_model->get();
        $data['class'] = $this->get_class_name();
        $loads = $this->get_load_view($id, $sw = 1);
        $data['load'] = $loads[0];
        $data['status'] = [
            1 => 'To Pick UP',
            2 => 'Loaded',
            3 => 'On Schedule',
            4 => 'Behind Schedule',
            5 => 'Canceled',
            6 => 'Delivered',
        ];
        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/update_view');
        $this->load->view('general/inc/footer_view');
    }

    public function update_load() {

        $this->output->set_content_type('application_json');
        //form validation

        $this->form_validation->set_rules('customer', 'Customer', 'required');
        $this->form_validation->set_rules('driver', 'Driver', 'required');
        $this->form_validation->set_rules('load_number', 'Load Number', 'required');
        $this->form_validation->set_rules('or_name', 'Origin Name', 'required');
        $this->form_validation->set_rules('or_address_1', 'Origin Address 1', 'required');
        $this->form_validation->set_rules('or_zipcode', 'Origin Zipcode', 'required');
        $this->form_validation->set_rules('or_city', 'Origin City', 'required');
        $this->form_validation->set_rules('or_state', 'Origin State', 'required');
        $this->form_validation->set_rules('or_country', 'Origin Country', 'required');
        $this->form_validation->set_rules('dt_name', 'Destination Name', 'required');
        $this->form_validation->set_rules('dt_address_1', 'Destination Address 1', 'required');
        $this->form_validation->set_rules('dt_zipcode', 'Destination Zipcode', 'required');
        $this->form_validation->set_rules('dt_city', 'Destination City', 'required');
        $this->form_validation->set_rules('dt_state', 'Destination State', 'required');
        $this->form_validation->set_rules('dt_country', 'Destination Country', 'required');
//        $this->form_validation->set_rules('name', 'Name', 'required|min_length[4]|max_length[32]');
//        $this->form_validation->set_rules('phone', 'Phone', 'required|min_length[4]|max_length[16]');
//        $this->form_validation->set_rules('login', 'Login', 'required|min_length[4]|max_length[16]|is_unique[user.login]');
//        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[user.email]');
//        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|max_length[16]|matches[confirm_password]');
//        $this->form_validation->set_rules('confirm_password', 'Password Confirmation', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
            return false;
        }

        $dr = $this->input->post('driver');
        $email = '';
        switch ($dr) {
            case "1":
                $email = "fparker@smith-cargo.com";
                break;
            case "2":
                $email = "robert@leanstaffing.com";
                break;
            case "3":
                $email = "willmar@leanstaffing.com";
                break;
            case "4":
                $email = "hpena@leanstaffing.com";
                break;
            case "5":
                $email = "lmorillo@leanstaffing.com";
                break;

            default:
                echo "Your favorite color is neither red, blue, nor green!";
        }



        $load_id = $this->input->post('load_id');
        $status = $this->input->post('status');
        $customer = $this->input->post('customer');
        $driver = $this->input->post('driver');
        $load_number = $this->input->post('load_number');

//                echo $dt[0];
//        return false;'
        // --------------- Origin data --------------------

        $or_earliest = $this->input->post('or_earliest');
        $or_latest = $this->input->post('or_latest');
        $or_name = $this->input->post('or_name');
        $or_address_1 = $this->input->post('or_address_1');
        $or_address_2 = $this->input->post('or_address_2');
        $or_zipcode = $this->input->post('or_zipcode');
        $or_city = $this->input->post('or_city');
        $or_state = $this->input->post('or_state');
        $or_country = $this->input->post('or_country');
        $or_contact = $this->input->post('or_contact');
        $or_phone = $this->input->post('or_phone');
        $or_fax = $this->input->post('or_fax');
        $or_email = $this->input->post('or_email');
        $or_comments = $this->input->post('or_comments');
        $pk_lat = $this->input->post('origin_lat');
        $pk_lng = $this->input->post('origin_lng');

        // --------------- Destination data ---------------------

        $dt_earliest = $this->input->post('dt_earliest');
        $dt_latest = $this->input->post('dt_latest');
        $dt_name = $this->input->post('dt_name');
        $dt_address_1 = $this->input->post('dt_address_1');
        $dt_address_2 = $this->input->post('dt_address_2');
        $dt_zipcode = $this->input->post('dt_zipcode');
        $dt_city = $this->input->post('dt_city');
        $dt_state = $this->input->post('dt_state');
        $dt_country = $this->input->post('dt_country');
        $dt_contact = $this->input->post('dt_contact');
        $dt_phone = $this->input->post('dt_phone');
        $dt_fax = $this->input->post('dt_fax');
        $dt_email = $this->input->post('dt_email');
        $dt_comments = $this->input->post('dt_comments');
        $dp_lat = $this->input->post('destination_lat');
        $dp_lng = $this->input->post('destination_lng');

        $items = $this->input->post('items');
//        print_r(json_decode($items));
//        $this->insertItemsLoad(5, json_decode($items));
//        return false;

        $load_affedted = $this->load_model->update([
            'user_iduser' => $this->session->userdata('user_id'),
            'ts_driver_idts_driver' => $driver,
            'status' => $status,
            'ts_customer_idts_customer' => $customer,
            'load_number' => $load_number,
            'or_earliest' => $or_earliest,
            'or_latest' => $or_latest,
            'or_name' => $or_name,
            'or_address_1' => $or_address_1,
            'or_address_2' => $or_address_2,
            'or_zipcode' => $or_zipcode,
            'or_city' => $or_city,
            'or_state' => $or_state,
            'or_country' => $or_country,
            'or_contact' => $or_contact,
            'or_phone' => $or_phone,
            'or_fax' => $or_fax,
            'or_email' => $or_email,
            'or_comments' => $or_comments,
            'dt_earliest' => $dt_earliest,
            'dt_latest' => $dt_latest,
            'dt_name' => $dt_name,
            'dt_address_1' => $dt_address_1,
            'dt_address_2' => $dt_address_2,
            'dt_zipcode' => $dt_zipcode,
            'dt_city' => $dt_city,
            'dt_state' => $dt_state,
            'dt_country' => $dt_country,
            'dt_contact' => $dt_contact,
            'dt_phone' => $dt_phone,
            'dt_fax' => $dt_fax,
            'dt_email' => $dt_email,
            'dt_comments' => $dt_comments,
            'pk_lat' => $pk_lat,
            'pk_lng' => $pk_lng,
            'dp_lat' => $dp_lat,
            'dp_lng' => $dp_lng,
                ], $load_id);




        $items_affected = $this->update_load_items($load_id, json_decode($items));
        if ($load_affedted || $items_affected) {
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }

        $this->output->set_output(json_encode(['result' => 0, 'notice' => 'Load or items not updated.']));
    }

    public function update_load_items($load_id, $items) {
        $this->load->model('item_model');

        $this->item_model->delete([
            'ts_load_idts_load' => $load_id
        ]);
        $i = 0;
        $item_value = [];
        print_r($items);
        foreach ($items as $item) {
            $item_data = [
                'ts_load_idts_load' => $load_id,
                'name' => $item->quantity . '@' . $item->length . 'x' . $item->width . 'x' . $item->height . ' cl' . $item->class . ' ' . $item->weight . $item->weight_unit,
                'class' => $item->class,
                'quantity' => $item->quantity,
                'quantity_unit' => $item->quantity_unit,
                'weight' => $item->weight,
                'weight_unit' => $item->weight_unit,
                'length' => $item->length,
                'width' => $item->width,
                'height' => $item->height
            ];

            $item_value[$i] = $item_data;
            $i++;
        }

        $this->item_model->insertBatch($item_value);
    }

    public function trash($id) {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());
        $load_affedted = $this->load_model->update(['sw' => 0], $id);

        if ($load_affedted) {
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }
        $this->output->set_output(json_encode(['result' => 0]));
    }

    public function update2($id) {
        $this->_required_login($this->router->fetch_class(), $this->router->fetch_method());

        $data = $this->get_session_user_data();
        $data['roles'] = $GLOBALS['roles'];
        $data['load'] = $this->load_model->get($id);
        $data['load'] = $data['load'][0];

        $this->load->model('carrier_model');
        $data['carriers'] = $this->carrier_model->get();

        $this->load->model('driver_model');
        $data['drivers'] = $this->driver_model->get([
            'ts_carrier_idts_carrier' => $data['load']['ts_carrier_idts_carrier']
        ]);

        $this->load->model('customer_model');
        $data['customers'] = $this->customer_model->get();

        $this->load->model('shipment_model');
        $data['shipments'] = $this->shipment_model->get_shipment(['ts_load_idts_load' => $id]);


        $data['file'] = file_exists('../tkgo_files2/' . $data['load']['load_number'] . '.pdf');

        $data['error'] = '';

        $this->load->view('general/inc/header_view', $data);
        $this->load->view('load/update2_view');
        $this->load->view('general/inc/footer_view');
    }

    public function delete_file($file) {
        $this->_required_login();
        $result = unlink('../tkgo_files2/' . $file . '.pdf');
        $result = unlink('../tkgo_files2/' . $file . '.jpg');

        if ($result) {
            $this->output->set_output(json_encode(['result' => 1]));
            return false;
        }
        $this->output->set_output(json_encode(['result' => 0]));
    }

    public function get_load($id = null) {
        $this->_required_login();
        if ($id != null) {
            $result = $this->load_model->get([
                'driver' => $id
            ]);
        } else {
            $result = $this->load_model->get();
        }

        $this->output->set_output(json_encode($result));
    }

    /**
     * This function is for the app
     * @param type $id
     */
    public function get_load_view($where = null, $json = null, $sw = null, $order_by = null, $order = null, $limit = null, $start = null) {
        $this->_required_login();
        $this->load->model('item_model');

        // check if request comes from this controller
        $user_id = 0;
        $user = $this->get_session_user_data();
        if ($this->input->post('user_id')) {
            $user_id = $this->input->post('user_id');
        } else {
            $user_id = $user['user_id'];
        }

        // Check if where and make it array
        if (!$where) {
            $where = [];
            $customer = $this->input->post('search_customer');
            $carrier = $this->input->post('search_carrier');
            $load_number = $this->input->post('search_load_number');

            if ($carrier != 0)
                $where['ts_load.ts_carrier_idts_carrier'] = $carrier;

            if ($load_number != '') {
                $where['load_number'] = $load_number;
                $where['ts_driver.full_name'] = $load_number;
                $where['ts_carrier.name'] = $load_number;
            }
        }
//        print_r($where);
        //check if user can see brother's loads
        if ($user['brother']) {
            // get childs of the current user
            $childs = $this->recursive($user['parent']);
            //add current user to the array
            array_push($childs, $user_id);
        } else {
            // get childs of the current user
            $childs = $this->recursive($user_id);
            //add current user to the array
            array_push($childs, $user_id);
        }


        $result = $this->load_model->get_load_view($where, $childs, $sw, $order_by, $order, $limit, $start);
        if ($limit) {
            for ($i = 0; $i < count($result); $i++) {
//            $result[$i]['items'] = $this->get_items_by_load_id($result[$i]['idts_load']);
                $result[$i]['status'] = $this->load_status($result[$i]['idts_load'], $result[$i]['tender']);
                $driver_address = json_decode($this->get_driver_address($result[$i]['driver_latitud'], $result[$i]['driver_longitud']));
                $result[$i]['driver_address'] = $driver_address->results[0]->formatted_address;
                $result[$i]['shipments'] = $this->shipments_by_load($result[$i]['idts_load']);
            }
        }

        if ($json) {
            $this->output->set_output(json_encode($result));
            return false;
        }
        return $result;
    }

    public function shipments_by_load($load_id, $json = null) {
        $this->load->model('shipment_model');
        $shipments = $this->shipment_model->get_shipment(['ts_load_idts_load' => $load_id]);
        if ($json) {
            $this->output->set_output(json_encode($shipments));
        }
        return $shipments;
    }

    public function load_status($load_id, $tender, $json = null) {
        $this->load->model('shipment_model');
        $status = '';
        if ($tender == 1) {
            $status = 'To Pickup';
            $shipments = $this->shipment_model->get(['ts_load_idts_load' => $load_id]);
            if (count($shipments) > 0) {
                foreach ($shipments as $shipment => $row) {
                    if ($row['origin_sign'] == 1) {
                        $status = 'In transit';
                    }

                    if ($row['destination_sign'] == 1) {
                        $status = 'Delivered';
                    } else if ($row['destination_sign'] == 0) {
                        $status = 'In transit';
                    }
                }
            }
        } else {
            $status = 'Not tendered';
        }

        if ($json) {
            $this->output->set_output(json_encode(['status' => $status]));
        }
        return $status;
    }

    public function get_driver_position($id, $sw = null, $load_id = null) {
        $this->load->model('driver_model');

        if (!$load_id) {
            $load_id = $this->input->post('load_id');
        }

        $driver = $this->driver_model->get($id);
        $driver = $driver[0];
        $driver_address = json_decode($this->get_driver_address($driver['lat'], $driver['lng']), true);
        $driver_address['trace'] = $this->get_load_trace($load_id);
//        array_push($driver_address, $this->get_load_trace($load_id));
        if ($sw) {
            $this->output->set_output(json_encode($driver_address));
            return false;
        }

        return $driver_address;
    }

    public function get_driver_address($lat, $lng) {
        $ch = curl_init();
        $var = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&key=AIzaSyAp8XadZn74QX4NLDphnzehQ0AN7q6NCwg';
//        echo 'url '.$var;

        curl_setopt($ch, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&key=AIzaSyAp8XadZn74QX4NLDphnzehQ0AN7q6NCwg');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    /**
     * This function is for the app
     * @param type $id
     */
    public function app_get_load_view($id = null, $sw = null) {
        $this->load->model('item_model');

        header('Access-Control-Allow-Origin: *');
        if ($id != null) {
            $result = $this->load_model->get_load_view([
                'idts_load' => $id
            ]);
            $result[0]['items'] = $this->get_items_by_load_id($result[0]['idts_load']);
        } else {
            $result = $this->load_model->get_load_view();
            for ($i = 0; $i < count($result); $i++) {
                $result[$i]['items'] = $this->get_items_by_load_id($result[$i]['idts_load']);
            }
        }
        if (!$sw) {
            $this->output->set_output(json_encode($result));
        }
        return $result;
    }

    public function get_items_by_load_id($id) {
        $this->load->model('item_model');
        $result = $this->item_model->get_items_by_load_id($id);
        return $result;
    }

    /**
     * This function is for the app
     * @param type $id
     */
    public function app_get_load_by_driver($id_driver, $sw = null) {
        $this->load->model('item_model');

        header('Access-Control-Allow-Origin: *');

        $result = $this->load_model->get_load_view([
            'ts_driver_idts_driver' => $id_driver
                ], 0, 1, 'date_created', 'desc');
//        if ($result)
//            $result[0]['items'] = $this->get_items_by_load_id($result[0]['idts_load']);
        if (!$sw) {
            $this->output->set_output(json_encode($result));
        }

        return $result;
    }

    /**
     * This function is for the app
     * @param type $id
     */
    public function app_get_load_id($id = null) {
        $this->load->model('item_model');

        header('Access-Control-Allow-Origin: *');
        if ($id != null) {
            $result = $this->load_model->get($id);
        } else {
            $result = $this->load_model->get();
        }

        $result_array = $result;

        if ($result) {
            $result_item = $this->item_model->get([
                'ts_load_idts_load' => $result[0]['idts_load']
            ]);
            $result_array = array_merge($result, $result_item);
        }

        $this->output->set_output(json_encode($result_array));
    }

    public function app_get_shipments_by_load($load_id) {
        $this->load->model('shipment_model');
        header('Access-Control-Allow-Origin: *');

        $result = $this->shipment_model->get([
            'ts_load_idts_load' => $load_id
        ]);

        $this->output->set_output(json_encode($result));
    }

    public function insertItemsLoad($id, $items) {
        $this->load->model('item_model');
        $i = 0;
        $item_value = [];
        foreach ($items as $item) {
            $item_data = [
                'ts_load_idts_load' => $id,
                'name' => $item->quantity . '@' . $item->length . 'x' . $item->width . 'x' . $item->height . ' cl' . $item->class . ' ' . $item->weight . $item->weight_unit,
                'description' => $item->description,
                'class' => $item->class,
                'quantity' => $item->quantity,
                'quantity_unit' => $item->quantity_unit,
                'weight' => $item->weight,
                'weight_unit' => $item->weight_unit,
                'length' => $item->length,
                'width' => $item->width,
                'height' => $item->height,
            ];

            $item_value[$i] = $item_data;
            $i++;
        }

        $this->item_model->insertBatch($item_value);
    }

    public function send_mail($param) {
        $email = $param['email'];
        $customer = $param['customer'];
        $origin = $param['origin'];
        $destination = $param['destination'];

        $to = "$email";
        $subject = "Load assigned";
// Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
        $headers .= 'From: <service@smith.com>' . "\r\n";

        $message = '<div><h1>Smith Transportation</h1></div>
                        <p>Load assigned to you</p>
                        <ul>
                            <li>Customer: ' . $customer . '</li>
                            <li>Origin: ' . $origin . '</li>
                            <li>Destination: ' . $destination . '</li>
                        </ul>
                        <p>Check the smith app to see load details.</p>';
        mail($to, $subject, $message, $headers);
    }

    public function send_mail_2($param) {
        $this->load->library('email');

        $email = $param['email'];
        $customer = $param['customer'];
        $load_number = $param['load_number'];

        $this->email->from('service@smith-cargo.com', 'Smith Transportation');
        $this->email->to($email);
        $this->email->cc('another@another-example.com');
        $this->email->bcc('them@their-example.com');

        $this->email->subject('Load #' . $load_number);
        $this->email->message('<div><h1>Smith Transportation</h1></div>
                        <p>Load assigned to you</p>
                        <ul>
                            <li>Customer: ' . $customer . '</li>
                        </ul>
                        <p>Check BOl attached.</p>');
        $this->email->attach('../tkgo_files2/' . $load_number . '.pdf');
        $this->email->set_mailtype("html");

        if (!$this->email->send()) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
        }
    }

    //-------------------- Call Checks ---------------------

    public function save_callcheck($user_id = null, $load_id = null, $type = null, $driver = null, $comment = null, $driver_lat = null, $driver_lng = null, $notify_driver = null, $driver_email = null, $load_number = null, $sw = null) {
        if (!$comment) {
            $comment = $this->input->post('comment');
        }

        if (!$notify_driver) {
            $notify_driver = $this->input->post('notify_driver');
        }

        if (!($driver_lat && $driver_lng)) {
            $driver_lat = $this->input->post('driver_latitud');
            $driver_lng = $this->input->post('driver_loingitude');
        }

        if (!$driver_email) {
            $driver_email = $this->input->post('driver_email');
        }

        if (!$load_number) {
            $load_number = $this->input->post('load_number');
        }

        $driver_position = json_decode($this->get_driver_address($driver_lat, $driver_lng));
        $driver_address = $driver_position->results[0]->formatted_address;
        $driver_address = explode(',', $driver_address);

        $this->load->model('callcheck_model');
        $this->callcheck_model->insert([
            'user_iduser' => $user_id,
            'ts_load_idts_load' => $load_id,
            'type' => $type,
            'notify_driver' => $notify_driver,
            'driver' => $driver,
            'comment' => $comment,
            'city' => $driver_address[1],
            'state' => $driver_address[2],
            'country' => $driver_address[3],
            'date' => date("Y-m-d H:i:s")
        ]);
        $data = $this->get_session_user_data();

        $date = explode(' ', date("Y-m-d H:i:s"));
        $date_formated_temp = explode('-', $date[0]);
        $date_formated = $date_formated_temp[1] . '/' . $date_formated_temp[0] . '/' . $date_formated_temp[2];

        $result = [
            'date' => $date_formated,
            'time' => $date[1],
            'city' => $driver_address[1],
            'state' => $driver_address[2],
            'country' => $driver_address[3],
            'comment' => $comment,
            'entered_by' => $data['login']
        ];

        $param['load_number'] = $load_number;
        $param['email'] = $driver_email;
        $param['comment'] = $comment;
        $this->send_callcheck_mail($param);

        if (!$sw) {
            $this->output->set_output(json_encode($result));
            return false;
        }
        return $result;
    }

    public function send_callcheck_mail($param) {
        $this->load->library('email');

        $email = $param['email'];
        $load_number = $param['load_number'];
        $comment = $param['comment'];
        $user = $this->get_session_user_data();


        $this->email->from('service@smith-cargo.com', 'Smith Transportation');
        $this->email->to($email);
        $this->email->cc('another@another-example.com');
        $this->email->bcc('them@their-example.com');

        $this->email->subject('Callcheck in Load #' . $load_number);
        $this->email->message('<div><h1>Smith Transportation</h1></div>
                        <p>Callcheck in Load #' . $load_number . '</p>
                        <ul>
                            <li>User: ' . $user['user'] . '</li>
                            <li>Callcheck: ' . $comment . '</li>
                        </ul>');
        $this->email->set_mailtype("html");

        if (!$this->email->send()) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
        }
    }

    public function get_callcheck($id_load, $sw = null) {
        $this->load->model('callcheck_model');
        $result = $this->callcheck_model->get([
            'ts_load_idts_load' => $id_load
                ], 'date', 'asc');
        $this->output->set_output(json_encode($result));
    }

    //generic php function to send GCM push notification
    public function send_push_not() {

        $driver_lat = $this->input->post('driver_latitud');
        $driver_lng = $this->input->post('driver_longitud');
        $driver_mail = $this->input->post('driver_mail');
        $load_number = $this->input->post('load_number');
        $apns_number = $this->input->post('apns_number');
        $app_id = $this->input->post('app_id');

//         $registatoin_ids[0] = $this->input->post('status');
        $title = $this->input->post('title');
        $message = $this->input->post('msg');
        $apple_msg = 'Msg load #' . $load_number . '-' . $message;
        $load_id = $this->input->post('load_id');
        $registatoin_ids[0] = $app_id;

        if ($apns_number || $app_id) {
            //Apple notification
            if ($apns_number) {
                $result['result'] = $this->send_apple_not($apns_number, $load_number, $apple_msg);
            }

            //Android notification
            if ($app_id) {
                $result['result'] = json_decode($this->send_android_not($registatoin_ids, $title, $message));
            }
        } else {
            return $this->output->set_output(json_encode(['status' => 0, 'msg' => 'Apple or Android id not found']));
        }

        //Save in database
        $ck = $this->save_callcheck($this->session->userdata('user_id'), $load_id, 1, 0, $message, $driver_lat, $driver_lng, 1, $driver_mail, $load_number, 1);
        $result['dbresult'] = $ck;

//        return $this->output->set_output(json_encode($ck));
        return $this->output->set_output(json_encode($result));
    }

    public function tender($load_number, $driver_id, $push = null, $email = null) {
        $this->load_model->update(['tender' => 1], ['load_number' => $load_number]);

        if (!$push) {
            $this->load->model('driver_model');
            $drivers = $this->driver_model->get($driver_id);
            $driver = $drivers[0];
            $this->push_not_new_load($load_number, $driver['app_id'], $driver['apns_number']);
        }
        
        $param['email'] = $email;
        $param['load_number'] = $load_number;
        $this->send_tender_mail($param);
        $this->output->set_output(json_encode(['status' => '1', 'msg' => 'Load sucesfully tendered']));
    }

    public function send_tender_mail($param) {
        $this->load->library('email');

        $email = $param['email'];
        $load_number = $param['load_number'];

        $this->email->from('service@smith-cargo.com', 'Smith Transportation');
        $this->email->to($email);
//        $this->email->cc('another@another-example.com');
//        $this->email->bcc('them@their-example.com');

        $this->email->subject('Tender Load #' . $load_number);
        $this->email->message('<div><h1>Smith Transportation</h1></div>
                        <p>Load has been tendered to you</p>
                        <ul>
                            <li>Please seek for load #: ' . $load_number . ' in your app.</li>
                        </ul>
                        <p>Check BOl attached.</p>');
//        $this->email->attach('../tkgo_files2/' . $load_number . '.pdf');
        $this->email->set_mailtype("html");

        if (!$this->email->send()) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
        } else {
            $this->output->set_output(json_encode(['status' => '1', 'msg' => 'Tender Email succesfully sent']));
        }
    }

    public function push_not_custom_msg_load($load_number = null, $app_id = null, $apns_number = null, $msg = null, $android_title = null, $tender = null, $email = null, $load_id = null) {

        $android_msg = $msg;
        $apple_msg = $msg;

        if (!$load_number) {
            $load_number = $this->input->post('load_number');
        }

        if (!$load_id) {
            $load_id = $this->input->post('load_id');
        }

        if (!$app_id) {
            $app_id = $this->input->post('app_id');
        }

        if (!$apns_number) {
            $apns_number = $this->input->post('apns_number');
        }

        if (!$msg) {
            $msg = $this->input->post('msg');
        }

        if (!$android_title) {
            $android_title = $this->input->post('android_title');
        }

        if (!$tender) {
            $tender = $this->input->post('tender');
            $android_msg = 'New load #' . $load_number . '-' . $msg;
            $apple_msg = 'New load #' . $load_number . '-' . $msg;
        }

        if (!$email) {
            $email = $this->input->post('email');
        }

        $registatoin_ids[0] = $app_id;

        if ($apns_number || $app_id) {
            //Apple notification
            if ($apns_number) {
                $result = $this->send_apple_not($apns_number, $load_number, $apple_msg);
//                $this->output->set_output(json_encode(['apple msg' => $apple_msg]));
            }

            //Android notification
            if ($app_id) {
                $result = $this->send_android_not($registatoin_ids, $android_title, $android_msg);
            }

            //if tender
            if ($tender) {
                $this->tender($load_number, 1, 1, $email);
                //save in callcheck
                $this->save_callcheck($this->session->userdata('user_id'), $load_id, 1, 0, $msg, 26.13778000, -80.33429800, 1, $email, $load_number, 1);
            }
        } else {
            return $this->output->set_output(json_encode(['status' => 0, 'msg' => 'Apple or Android id not found']));
        }
        return $result;
    }

    public function push_not_new_load($load_number, $app_id, $apns_number) {

        $android_msg = 'load #' . $load_number;
        $apple_msg = 'New load #' . $load_number;
        $registatoin_ids[0] = $app_id;

        if ($apns_number || $app_id) {
            //Apple notification
            if ($apns_number) {
                $result = $this->send_apple_not($apns_number, $load_number, $apple_msg);
            }

            //Android notification
            if ($app_id) {
                $result = $this->send_android_not($registatoin_ids, 'You have a new load', $android_msg);
            }
        } else {
            return $this->output->set_output(json_encode(['status' => 0, 'msg' => 'Apple or Android id not found']));
        }
        return $result;
    }

    //generic php function to send GCM push notification
    public function send_push_not2($load, $app_id, $apns_number) {

        //Google cloud messaging GCM-API url
        $url = 'https://gcm-http.googleapis.com/gcm/send';
        $message = 'You have a new load';

//         $registatoin_ids[0] = $this->input->post('status');
//        $title = $this->input->post('title');
//        $message = $this->input->post('msg');
//        $load_id = $this->input->post('load_id');
        $registatoin_ids[0] = $app_id;


        $fields = array(
            'registration_ids' => $registatoin_ids,
            "data" => array(
                "title" => 'You have a new load',
                "message" => 'Load #' . $load,
                'msgcnt' => count($message),
                'timestamp' => date('Y-m-d h:i:s'),
            ),
        );


        // Google Cloud Messaging GCM API Key
        define("GOOGLE_API_KEY", "AIzaSyDuQEwlOLK55nXfmdevqvXGg_IAgVG4NxQ");

        $headers = array(
            'Authorization: key=' . GOOGLE_API_KEY,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
//        $this->save_callcheck($this->session->userdata('user_id'), $load, 1, 0, $message);
        $this->send_apple_not2($load, $apns_number);
        return $result;
    }

    public function send_apple_not($app_id, $load_number, $message) {

//        echo 'apns number: '.$app_id.'<br>';
        if (!$app_id) {
            $app_id = $this->input->post('app_id');
        }

        if (!$message) {
            $message = $this->input->post('message');
        }

//        $message = 'Msg load #' . $load_number . '-' . $message;
//        echo $message;
// Put your device token here (without spaces):
        $deviceToken = $app_id; //'5ed672addefa254d8e0d054c8acb1658bde5ef8a1b49c75c838ed56b037eb3fa';//
// Put your private key's passphrase here:
        $passphrase = 'staffing';  //20151102        
//        $passphrase = 'Staffing1a';
// Put your alert message here:
//        $message = 'It works, this piece of art works!';
//        
// Enviroment
        $ck = 'ck_bk.pem'; //Development
//        $ck = 'ck.pem'; //production
////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', '../testpush/' . $ck);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
//        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT |
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT |
// REMOVE sandbox when app is in appstore
                STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

//        echo 'Connected to APNS' . PHP_EOL;
// Create the payload body
        $body['aps'] = array(
            'alert' => $message,
            'badge' => 1,
            'sound' => 'oven.caf',
        );

// Encode the payload as JSON
        $payload = json_encode($body);

// Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

//        if (!$result)
//            echo 'Message not delivered' . PHP_EOL;
//        else
//            echo 'Message successfully delivered' . PHP_EOL;
//        echo '<br>';
//        echo $app_id;
//        echo 'testing';
// Close the connection to the server
        fclose($fp);
        return $result;
    }

    public function send_apple_not2($load_number, $app_id) {
        if (!$load_number) {
            $load_number = $this->input->post('load_number');
        }

        if (!$app_id) {
            $app_id = $this->input->post('app_id');
        }


// Put your device token here (without spaces):
        $deviceToken = $app_id; //'5ed672addefa254d8e0d054c8acb1658bde5ef8a1b49c75c838ed56b037eb3fa';//
// Put your private key's passphrase here:
//        $passphrase = 'staffing';  //20151102
        $passphrase = 'Staffing1a';
// Put your alert message here:
        $message = 'New load #' . $load_number;

        // enviroment
//        $ck = 'ck_bk.pem'; // development
        $ck = 'ck.pem'; // production
////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', '../testpush/' . $ck);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT |
// REMOVE sandbox when app is in appstore
                STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

//        echo 'Connected to APNS' . PHP_EOL;
// Create the payload body
        $body['aps'] = array(
            'alert' => $message,
            'badge' => 1,
            'sound' => 'oven.caf',
        );

// Encode the payload as JSON
        $payload = json_encode($body);

// Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

//        if (!$result)
//            echo 'Message not delivered' . PHP_EOL;
//        else
//            echo 'Message successfully delivered' . PHP_EOL;
//        echo '<br>';
//        echo $app_id;
//        echo 'testing';
// Close the connection to the server
        fclose($fp);
    }

    public function send_android_not($registatoin_ids, $title, $message) {
        //Google cloud messaging GCM-API url
        $url = 'https://gcm-http.googleapis.com/gcm/send';

        $fields = array(
            'registration_ids' => $registatoin_ids,
            "data" => array(
                "title" => $title,
                "message" => $message,
                'msgcnt' => count($message),
//                'notid'=> 
                'timestamp' => date('Y-m-d h:i:s'),
            ),
        );


        // Google Cloud Messaging GCM API Key
        define("GOOGLE_API_KEY", "AIzaSyDuQEwlOLK55nXfmdevqvXGg_IAgVG4NxQ");

        $headers = array(
            'Authorization: key=' . GOOGLE_API_KEY,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    public function send_bol($load_number = null) {
        $this->load->library('email');

        if (!$load_number) {
            $load_number = $this->input->post('load_number');
        }

        $email = $this->input->post('email');

        $this->email->from('service@smith-cargo.com', 'Smith Transportation');
        $this->email->to($email);
        $this->email->cc('another@another-example.com');
        $this->email->bcc('them@their-example.com');

        $this->email->subject('BOL #' . $load_number);
        $this->email->message('Attach is the BOL');
        $this->email->attach('../tkgo_files2/' . $load_number . '.pdf');

        if (!$this->email->send()) {
            $this->output->set_output(json_encode(['result' => 0, 'error' => $this->form_validation->error_array()]));
        }
    }

}
