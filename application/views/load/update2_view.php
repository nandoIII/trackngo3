<div id="category-actions">
    <div class="loads-title" id="category-title"><img src="<?php echo base_url() ?>/public/img/images/loads-title.png" width="100" height="70" alt="Loads Category"></div>
    <div id="category-button"><a style="outline: medium none;" hidefocus="true" href="<?php echo site_url('load/'); ?>"><img src="<?php echo base_url() ?>/public/img/images/loads-list-bt-45w.png" width="45" height="70" alt="View All Loads"></a></div>
    <div id="category-button"><a style="outline: medium none;" hidefocus="true" href="<?php echo site_url('load/add2'); ?>"><img src="<?php echo base_url() ?>/public/img/images/loads-add-bt-45w.png" width="45" height="70" alt="Add a Load"></a></div>
</div>  
<div class="container">
    <div class="row">
        <div class="container">
            <h2>Load #<?php echo $load['load_number'] ?></h2>
            <?php echo $error; ?>
            <?php $attributes = array('id' => 'register3_form'); ?>
            <?php echo form_open_multipart('load/do_upload2/' . $load['idts_load'], $attributes); ?>
            <?php // print_r('file is '.$file); ?>
            <input type="hidden" name="load_number" value="<?php echo $load['load_number'] ?>" />
            <input type="hidden" name="update_shipment" id="update_shipment"/>
            <input type="hidden" name="new_shipment" id="new_shipment"/>
            <input type="hidden" name="delete_shipment" id="delete_shipment"/>
            <input type="hidden" id="status" name="status" value="0">

            <div class="control-group">
                <label class="control-label">Carrier</label>
                <div class="controls">
                    <select class="selectpicker" name="carrier" id="carrier">
                        <?php
                        foreach ($carriers as $carrier => $row) {
                            $selected = '';
                            if ($row['idts_carrier'] == $load['ts_carrier_idts_carrier']) {
                                $selected = 'selected="selected"';
                            }
                            echo '<option ' . $selected . ' value="' . $row['idts_carrier'] . '">' . $row['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>        

            <div class="control-group">
                <label class="control-label">Driver</label>
                <div class="controls">
                    <select class="selectpicker" name="driver" id="driver">
                        <?php
                        foreach ($drivers as $driver => $row) {
                            $selected = '';
                            if ($row['idts_driver'] == $load['ts_driver_idts_driver']) {
                                $selected = 'selected="selected"';
                            }
                            echo '<option ' . $selected . ' value="' . $row['idts_driver'] . '">' . $row['name'] . ' ' . $row['last_name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div id="customer_list">
                <select class="selectpicker" name="customer">
                    <?php
                    foreach ($customers as $customer => $row) {
                        echo '<option value="' . $row['idts_customer'] . '">' . $row['name'] . '</option>';
                    }
                    ?>
                </select>                    
            </div>            

            <div id="add_shp">add Shipment</div>

            <table id="bol-table">
                <thead>
                <td>Customer</td>
                <td>Pickup address</td>
                <td>Drop address</td>
                <td>BOL #</td>
                <td>BOL file</td>
                <td>Actions</td>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    foreach ($shipments as $shipment => $row) {
//                                            $date = explode(' ', $row['date']);
//                                            $date_formated_temp = explode('-', $date[0]);
//                                            $date_formated = $date_formated_temp[1] . '/' . $date_formated_temp[0] . '/' . $date_formated_temp[2];

                        echo'<tr class="update_shipment" id="' . $row['idshipment'] . '">';
                        echo'<td>';
                        echo'<select class="selectpicker" name="customer">';
                        foreach ($customers as $customer => $cust_row) {
                            $selected = '';
                            if ($cust_row['idts_customer'] == $row['ts_customer_idts_customer']) {
                                $selected = 'selected="selected"';
                            }
                            echo '<option ' . $selected . '  value="' . $cust_row['idts_customer'] . '">' . $cust_row['name'] . '</option>';
                        }
                        echo'</select>';
                        echo'</td>';
                        echo'<td style="text-align: center; width:90px"><input type="text" class="pk" name="pickup" value="' . $row['pickup_address'] . '" /></td>';
                        echo'<td style="text-align: center; width:90px"><input type="text" class="dp" name="drop" value="' . $row['drop_address'] . '"/></td>';
                        echo'<td style="text-align: center; width:90px"><input type="text" class="bn" name="bol_number" value="' . $row['bol_number'] . '"/></td>'
                        . '<td style="text-align: center;"><a href="../../../tkgo_files2/' . $row['url_bol'] . '" target="_blank">View</a></td>'
                        . '<td style="text-align: center;"><a href="" data-id="' . $row['idshipment'] . '" class="remove_shp">Delete</a></td>'
                        . '</tr>';
                        $i++;
                    }
                    ?>                                        
                </tbody>
            </table>            

            <br /><br />
            <button type="submit" id="save_btn" class="btn btn-red btn-small" hidefocus="true" style="outline: medium none;"><span class="gradient">Save</span></button>
            <button type="submit" id="savensend_btn" class="btn btn-red btn-small" hidefocus="true" name="submit" style="outline: medium none;"><span class="gradient">Save and send</span></button>
            <button id="btn_cancel" style="border-radius: 16%; height: 25px;">Cancel</button>
            </form> 
        </div>
    </div>
</div>
<style>
    #bol-table{
        width: 900px;    
    }
    #bol-table td{
        border: 1px solid #000;
    }    
</style>
<script>
    $(function () {
        var count_ship = <?php echo count($shipments) ?>;
        var shp_number = parseInt(count_ship);
        check_file();

        $('#customer_list').hide();
        //Enable tender to driver
        $('body').on('click', '#savensend_btn', function (evt) {
            $('#status').val(1);
        });

        //Enable tender to driver
        $('body').on('click', '#save_btn', function (evt) {
            $('#status').val(0);

        });
        //Enable tender to driver
        $('body').on('click', '#btn_cancel', function (evt) {
                    location.href = '<?php echo site_url('/load') ?>';

        });

        function check_file() {
            var file = '';
            file = '<?php echo $file ?>';
            if (file != '') {
                $('#link_delete_file').css('visibility', 'block');
                $('#userfile').css('visibility', 'hidden');
            } else {
                $('#link_delete_file').css('visibility', 'hidden');
                $('#delete_file').css('visibility', 'hidden');
                $('#userfile').css('visibility', 'block');
            }
        }

        $('#upload_file').submit(function (e) {
            e.preventDefault();
            var url = $(this).attr('action');
            var postData = $(this).serialize();
            $("#userfile").AjaxFileUpload({
                url: url,
                secureuri: false,
                fileElementId: 'userfile',
                dataType: 'json',
                data: postData,
                success: function (data, status)
                {
                    if (data.status != 'error')
                    {
                        $('#files').html('<p>Reloading files...</p>');
                        refresh_files();
                        $('#title').val('');
                    }
                    alert(data.msg);
                }
            });
            return false;
        });

        $('#register3_form').submit(function (evt) {
            // submit the form 
            evt.preventDefault();
            getShipmentData();

            var options = {
//                target: '#output2', // target element(s) to be updated with server response 
                beforeSubmit: showRequest, // pre-submit callback 
                success: showResponse, // post-submit callback 

                // other available options: 
                //url:       url         // override for form's 'action' attribute 
                //type:      type        // 'get' or 'post', override for form's 'method' attribute 
                dataType: 'json'        // 'xml', 'script', or 'json' (expected server response type) 
                        //clearForm: true        // clear all form fields after successful submit 
                        //resetForm: true        // reset the form after successful submit 

                        // $.ajax options can be used here too, for example: 
                        //timeout:   3000 
            };

            $(this).ajaxSubmit(options);
            // return false to prevent normal browser submit and page navigation 
            return false;
        });


        $('#register2_form').submit(function (evt) {
            evt.preventDefault();
            var url = $(this).attr('action');
            var postData = $(this).serialize();
            var formData = new FormData($('form')[0]);

            $.ajax({
                type: "POST",
                url: url + '/' +<?php echo $load['idts_load'] ?>,
                async: true,
                data: formData,
                processData: false,
                dataType: "json",
                beforeSend: function () {
                    $('#msn').show();
                    $(".ajax-loader-load").show();
                },
                success: function (o) {
                    if (o.result == 1) {
//                    window.location.href = '<?php echo site_url('dashboard') ?>';
                        $("#success").show();
                        $("#success").html('<ul><li>The load was successfully added.</li></ul>');
//                                                    setTimeout(function () {
//                                                        $("#success").fadeOut();
//                                                        window.location = '<?php echo site_url('load') ?>';
//                                                    }, 1000);
                    } else {
                        var output = '<ul>';
                        for (var key in o.error) {
                            var value = o.error[key];
                            output += '<li>' + value + '</li>';
                        }
                        output += '</ul>';
                        $("#register_form_error").html(output);
                        $("#register_form_error").show();
                        $('html, body').animate({scrollTop: 0}, 'fast');
                    }
                }, complete: function () {
                    $('#msn').hide();
                    $(".ajax-loader-load").hide();
                }

            });
        });


        $("#carrier").change(function () {
            var carrier = $(this);
            $('#driver option').remove();
            $('#driver').append('<option value="loading">loading...</option>');

            var postData = {
                carrier_id: $(this).val()
            };
            console.log(carrier.val());
            if (carrier.val() == 0) {
                $('#driver option').remove();
                $('#driver').append('<option value="0">-Select-</option>');
            } else {

                $.post('<?php echo base_url('carrier/get_drivers_by_carrier') ?>', postData, function (o) {
                    $('#driver option').remove();
                    var drivers = o;
                    var output = '';
                    if (drivers.length == 0) {
                        output += '<option value="no_driver">-No driver asigned to this carrier-</option>';
                    }

                    if (carrier.val() === 'select') {
                        output += '<option value="select">-Select-</option>';
                    }

                    for (var i = 0; i < drivers.length; i++) {
                        output += '<option value="' + drivers[i].idts_driver + '">' + drivers[i].name + ' ' + drivers[i].last_name + '</option>';
                    }

                    $('#driver').append(output);

                }, 'json');

            }

        });

        $('body').on('click', '#delete_file', function (event) {
            var file = $(this);
            var file_name = file.data('file_name');
            console.log(file_name);

            $.ajax({
                type: "POST",
                url: '<?php echo base_url('load/delete_file') ?>' + '/' + file_name,
                async: true,
                dataType: "json",
                success: function (o) {
                    location.reload();
                }
            });

            location.reload();

        });

        //Add new row to Shipment the table
        $('body').on('click', '#add_shp', function (evt) {
            evt.preventDefault();
            setShipmentData(shp_number);
            shp_number++;
//            $('#bol-table tbody').append('<tr id="shp_' + shp_number + '">'+shp_number+'<td id="customer_' + shp_number + '">Customer</td><td><input type="text" name="pickup"/></td><td><input type="text" name="drop"/></td><td><input type="text" name="bol_number"/></td><td><input type="file" multiple = "multiple" accept = "image/*" class = "form-control" name="uploadfile[]" size="20" /></td></tr>');
//                    $('#customer_' + shp_number).html($('#customer_list').html());
        });

        //Remove shipment
        $('body').on('click', '.remove_shp', function (evt) {
            evt.preventDefault();
//            delete_shipments                        
            var tr_id = $(this).data('id');
            delete_shipments.push({
                idshipment: tr_id
            });
            var delete_shipment = JSON.stringify(delete_shipments);
            $('#delete_shipment').val(delete_shipment);
            var tr = $('#' + tr_id);
            $(tr).remove();
        });

        //Remove new shipment
        $('body').on('click', '.delete_new', function (evt) {
            evt.preventDefault();
            var tr_id = $(this).data('id');
            var tr = $('#shp_' + tr_id);
            $(tr).remove();
        });
    });

    //Global vars
    var shipments = [];
    var new_shipments = [];
    var delete_shipments = [];

    function setShipmentData(shp_number) {

        tRow = $('<tr id="shp_' + shp_number + '" class="new_shipment">');
        customer = $('<td>').html($('#customer_list').html());
        pickup = $('<td>').html('<input type="text" class="pk" name="pickup"/>');
        drop = $('<td>').html('<input type="text" class="dp" name="drop"/>');
        bol_number = $('<td>').html('<input type="text" class="bn" name="bol_number"/>');
        bol_file = $('<td>').html('<input type="file" multiple = "multiple" accept = "application/pdf" class = "form-control" name="uploadfile[]" size="20" />');
        actions = $('<td>').html('<a href="#" data-id="' + shp_number + '" class="delete_new">Delete</a>');

        tRow.append(customer);
        tRow.append(pickup);
        tRow.append(drop);
        tRow.append(bol_number);
        tRow.append(bol_file);
        tRow.append(actions);

        $('#bol-table tbody').append(tRow);
    }

    function getShipmentData() {
        $('#bol-table tbody tr').each(function () {

            var tr = $(this);
            var cl = tr.attr('class');

            if (cl == 'update_shipment') {
                shipments.push({
                    idshipment: tr.attr('id'),
                    ts_customer_idts_customer: tr.find('select').val(),
                    pickup_address: tr.find('.pk').val(),
                    drop_address: tr.find('.dp').val(),
                    bol_number: tr.find('.bn').val()
                });
            } else {
                new_shipments.push({
                    customer: tr.find('select').val(),
                    pickup: tr.find('.pk').val(),
                    drop: tr.find('.dp').val(),
                    bol_number: tr.find('.bn').val()
                });
            }

            //get row values
            // $('#out').append(this.id);
        });

        var json = JSON.stringify(shipments);
        $('#update_shipment').val(json);

        var new_shipment = JSON.stringify(new_shipments);
        $('#new_shipment').val(new_shipment);

        return true;
    }

    function showResponse(data) {
        console.log('returned data: ' + data.msg);
        alert('Changes saved.');
        location.href = '<?php echo site_url('/load') ?>';
    }

    function showRequest() {
        $('#save_btn').prop('disabled', true);
        $('#savensend_btn').prop('disabled', true);        
        console.log('before');
    }
</script>