<div>
    <div id="dashboard-main">
<!--        <form id="create_note" class="form-horizontal" method="post" action="<?= site_url('api/create_note') ?>">
            <div class="input-append">
                <input tabindex="1" type="text" name="title" placeholder="Note Title" />
                <input tabindex="3" type="submit" class="btn btn-success" value="Create" />
            </div>

            <div class="clearfix"></div>

            <textarea tabindex="2" name="content"></textarea>

        </form>-->

        <div id="list_user">
            <span class="ajax-loader-gray"></span>
        </div>

        <div id="category-actions">
            <div class="loads-title" id="category-title"><img src="<?php echo base_url() ?>/public/img/images/loads-title.png" width="100" height="70" alt="Loads Category"></div>
            <div id="category-button"><a style="outline: medium none;" hidefocus="true" href="<?php echo site_url('load/'); ?>"><img src="<?php echo base_url() ?>/public/img/images/loads-list-bt-45w.png" width="45" height="70" alt="View All Loads"></a></div>

            <?php
            if (in_array("load/add2", $roles)) {
                ?>
                <div id="category-button"><a style="outline: medium none;" hidefocus="true" href="<?php echo site_url('load/add2'); ?>"><img src="<?php echo base_url() ?>/public/img/images/loads-add-bt-45w.png" width="45" height="70" alt="Add a Load"></a></div>
            <?php } ?>            
            <div id="category-button"></div>
            <div id="category-search" class="search-customer">
                <select class="selectpicker" name="customer" id="search_customer">
                    <option value="0">-- Select Customer --</option>
                    <?php
                    foreach ($customers as $customer => $row) {
                        echo '<option value="' . $row['idts_customer'] . '">' . $row['name'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div id="category-search" class="search-carrier">
                <select class="selectpicker" name="carrier" id="search_carrier">
                    <option value="0">-- Select Carrier --</option>
                    <?php
                    foreach ($carriers as $carrier => $row) {
                        echo '<option value="' . $row['idts_carrier'] . '">' . $row['name'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div id="category-search" class="search-loads"><input type="text" name="search_load_number" id="search_load_number" /></div>
            <div id="category-search" class="search-loads" style="width: 50px;margin-left: 10px;"><a class="btn" id="load_search" >Search</a></div>
        </div>

        <div class="table-responsive" style="margin-top:40px;">
            <table id="list_load" class="table table-hover table-bordered table-striped">
                <thead>
                    <tr style="background-color: #EBEBEB">
<!--                        <th style="width:5%"></th>-->
                        <th style="width:8%">Id</th>
                        <th style="width:12%">Create Date</th>
                        <th style="width:11%">Carrier</th>
                        <th style="width:11%">Driver</th>
                        <th style="width:29%">Driver Position</th>
                        <th style="width:7%; font-weight: 800;color: #666;">Status</th>
                        <?php echo in_array('load/update2', $roles) || in_array('load/trash', $roles) ? '<th style="width:7%">Actions</th>' : ''; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $j = 0;
                    foreach ($loads as $load => $row) {

                        //                        print_r($row['shipments'][0]['url_bol']);

                        $driver_address = explode(',', $row['driver_address'], 2);
                        $date = explode(' ', $row['date_created']);
                        $date_formated_temp = explode('-', $date[0]);
                        $date_formated = $date_formated_temp[1] . '/' . $date_formated_temp[2] . '/' . $date_formated_temp[0];

                        //                        $tender = $row['tender'] == 0 ? '<a class="tenderx" data-id="' . $row['idts_load'] . '" href="' . site_url('load/tenderx/' . $row['load_number']) . '/' . $row['ts_driver_idts_driver'] . '" data-toggle="modal" data-target="#tenderModal"> Tender </a>' : '';
                        $tender = $row['tender'] == 0 ? '<a class="set-tender-modal" data-load_number="' . $row['load_number'] . '" data-email="' . $row['driver_email'] . '" data-apns_number="' . $row['driver_apns_number'] . '" data-app_id="' . $row['driver_app_id'] . '" data-bol_url="' . $row['shipments'][0]['url_bol'] . '" data-id="' . $row['idts_load'] . '" data-toggle="modal" data-target="#tenderModal" style="cursor:pointer"> Tender </a>' : '';

                        echo '<tr data-status="' . $row['status'] . '" id="load_' . $row['idts_load'] . '" data-load_id="' . $row['idts_load'] . '" data-toggle="popoverx" class="pox">';
                        echo '<td>' . $row['load_number'] . '</td>';
                        echo '<td>' . $date_formated . ' ' . $date[1] . '</td>';
                        echo '<td>' . $row['carrier_name'] . '</td>';
                        echo '<td>' . $row['driver_name'] . ' ' . $row['driver_last_name'] . '</td>';
                        echo '<td style="width: 250px;">' . $driver_address[0] . '<br>' . $driver_address[1] . ' <span class="map_view" style="cursor:pointer"  data-toggle="modal" data-target="#destinationAddressModal" data-driver_lat="' . $row['driver_latitud'] . '" data-driver_lng="' . $row['driver_longitud'] . '" id="view_destination"><strong>[map]</strong></span></td>';
                        echo '<td class="color"  style="font-weight: 800;color: #666;">' . $row['status'] . '</td>';
                        echo in_array('load/update2', $roles) || in_array('load/trash', $roles) ? '<td>' : '';
                        echo in_array('load/update2', $roles) ? '<a class="edit" data-id="' . $row['idts_load'] . '" href="' . site_url('load/update2/' . $row['idts_load']) . '"> Edit </a>' : '';
                        echo in_array('load/load_details', $roles) ? '<a class="view" data-id="' . $row['idts_load'] . '" href="' . site_url('load/load_details/' . $row['idts_load']) . '"> View </a>' : '';
                        echo in_array('load/trash', $roles) ? '<a class="trash" data-id="' . $row['idts_load'] . '" href="' . site_url('load/trash/' . $row['idts_load']) . '"> Trash </a>' : '';
                        echo in_array('load/tender', $roles) ? $tender : '';
                        echo in_array('load/update2', $roles) || in_array('load/trash', $roles) ? '</td>' : '';
                        echo '</tr>';
                        $j++;
                    }
                    ?>
                </tbody>

            </table>
            <?php echo $this->pagination->create_links() ?>
        </div>

        <!-- Hidden values -->
        <input type="hidden" id="driver_lat" value="">
        <input type="hidden" id="driver_lng" value="">

        <!-- Load view dialog -->

        <div class="modal fade" id="load_view_dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Load Details</h4>
                    </div>
                    <div class="modal-body">
                        <fieldset>

                            <!-- Form Name -->
                            <legend>Load Details</legend>

                            <div id="load_detail"></div>

                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Destination address Modal -->

        <div class="modal fade" id="destinationAddressModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Driver Position</h4>
                    </div>
                    <div class="modal-body">
                        <fieldset>

                            <!-- Form Name -->
                            <legend>Check Address in Map</legend>

                            <div id="map-canvas2"></div>

                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Load Modal -->

        <div class="modal fade" id="editLoadModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit Loads</h4>
                    </div>
                    <div class="modal-body">
                        <fieldset>

                            <!-- Form Name -->
                            <legend>Change values to loads you selected</legend>
                            <input type="hidden" name="loads" id="loads" class="input-xlarge" value=""/>
                            <div class="control-group">
                                <label class="control-label">Customer</label>
                                <div class="controls">
                                    <select class="selectpicker" name="customer" id="customer">
                                        <option value="0" selected="selected">-Select-</option>
                                        <?php
                                        foreach ($customers as $customer => $row) {
                                            echo '<option value="' . $row['idts_customer'] . '">' . $row['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>        

                            <div class="control-group">
                                <label class="control-label">Carrier</label>
                                <div class="controls">
                                    <select class="selectpicker" name="carrier" id="carrier">
                                        <option value="0" selected="selected">-Select-</option>
                                        <?php
                                        foreach ($carriers as $carrier => $row) {
                                            echo '<option value="' . $row['idts_carrier'] . '">' . $row['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>        

                            <div class="control-group">
                                <label class="control-label">Driver</label>
                                <div class="controls">
                                    <select class="selectpicker" name="driver" id="driver">
                                        <option value="select" selected="selected">-Select-</option>

                                    </select>
                                </div>
                            </div>  

                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btn_edit_loads">Save changes</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tender Modal -->

        <div class="modal fade" id="tenderModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Tender Load</h4>
                    </div>
                    <div class="modal-body">
                        <fieldset>
                            <table style="width:810px;">
                                <tr>
                                    <td>Subject</td>
                                    <td>
                                        <input type="text" id="title">
                                        <input type="hidden" id="tender_load_id">
                                        <input type="hidden" id="tender_load_number">
                                        <input type="hidden" id="tender_app_id">
                                        <input type="hidden" id="tender_apns_number">                                        
                                        <input type="hidden" id="email">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">Special Instructions</td>
                                </tr>
                                <tr>
                                    <td colspan="2"><textarea id="msg"></textarea></td>                                    
                                </tr>
                                <tr>
                                    <td colspan="2"><iframe id="tender_iframe" width="100%" height="600" id="if_bol" style="margin-top:15px" src="../../../tkgo_files/<?php echo $load['load_number'] ?>.pdf"></iframe></td>
                                </tr>
                            </table>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button id="tender-load" class="btn btn-red btn-small" hidefocus="true" name="submit" style="outline: medium none;"><span class="gradient">Tender</span></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>        

    </div>
</div>

<!-- Hidden content -->

<div id="popover_content" style="display: none">
    <ul>
        <li><a data-id="4" class="editLink" title="Edit this Load" href=""><i class="icon-pencil"></i> Edit</a></li>
        <li><a data-id="4" class="editLink" title="Send message to driver" href=""><i class="icon-user"></i> Send Message</a></li>
        <li><a data-id="4" class="viewLink" title="View Load" href=""><i class="icon-eye-open"></i> View Load</a></li>
        <li></li>
    </ul>
</div>

<style>
    .popover{
        left: 272px !important;
    }
    .popover-title {
        font-size: 15px;
    }
    .popover-content {
        width: 150px;
    }
    /* 
    #map-canvas,  #map-canvas2{
        height: 300px;
        width: 520px;
        margin: 0;
        padding: 0;
    }
    .modal{
        width: 610px;
    }    */

    .sele{
        width: 70px;
    }

    .container2{
        width: 500px;
    }

    .origin{
        float: left;
    }
    .destination{

        float: left;
    }

    .smallinput{
        width: 150px;
    }

    #map-canvas,  #map-canvas2{
        height: 300px;
        width: 520px;
        margin: 0;
        padding: 0;
    }

    .item_input{
        width: 60px;
    }
    th{
        text-align: center;
        font-weight: 700;
    }

    .item_head{
        height: 20px;
    }

    .modal{
        width: 610px;
        overflow-y: auto;
        overflow-x: auto;
        height: 540px;
    }

    #category-search {
        position: relative;
        height: 40px;
        width: 190px;
        background-color: #F6F6F6;
        float: left;
        background-image: url(../images/loads-search-bt-230w.png);
        background-repeat: no-repeat;
        margin-right: 5px;
        top: 14px;        
    }

    select {
        width: 190px;
    }

    input[type="text"], input[type="password"] {
        height: 30px;
        background-color: #fff;
    }

    #tenderModal{
        width: 900px;
        height: 700px;
        left: 40% !important;
    }

    #tenderModal .modal-dialog{
        width: 880px;
    }    

    #tenderModal .modal-body{
        max-height: 520px;
    }    

</style>

<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAp8XadZn74QX4NLDphnzehQ0AN7q6NCwg"></script>
<script>
    var ck_load = [];
    $('[data-toggle=popover]').popover({
        trigger: "click",
    });

    $('[data-toggle=popover]').on('click', function (e) {
        $('[data-toggle=popover]').not(this).popover('hide');
        var load = $(this);
        var load_id = load.data('load_id');

        $('.popover-title').html('Load Options <span style="margin-left:15px;" class="pull-right"><a href="#" onclick="$(&quot;#' + this + '&quot;).popover(&quot;toggle&quot;);" class="text-danger popover-close" data-bypass="true" title="Close"><i class="fa fa-closex"></i></a></span>');
        $('.popover-content').html('<ul><li><a data-id="4" title="Edit this Load" href="load/update2/' + load_id + '"><i class="icon-pencil"></i> Edit</a></li> <li><a data-id="4" title="View this Load" href="load/load_details/' + load_id + '"><i class="icon-eye-open"></i> View</a></li> <li><a data-id="4" title="Edit this Load" href="load/trash/' + load_id + '"><i class="icon-trash"></i>Trash</a> </li></ul>');

        console.log('datos de load: ' + load.data('load_id'));
    });
    $(function () {
        $('#save').click(function () {
            alert($('#mytable').find('input[type="checkbox"]:checked').length + ' checked');
        });

        $('body').on('click', '.edit_load', function (evt) {
            ck_load = [];
            $('#list_load').find('input[type="checkbox"]:checked').each(function () {
                var load = $(this);
                console.log(load.attr('name'));
                ck_load.push({idts_load: load.attr('name')});
            });
            console.log(ck_load);
            editLoads();
        });

        $('body').on('click', '#btn_edit_loads', function (evt) {
            $("#loads").val(JSON.stringify(ck_load));
            $.ajax({
                type: "POST",
                url: '<?php echo site_url('load/multi_upload') ?>',
                data: {
                    customer: $("#customer").val(),
                    carrier: $("#carrier").val(),
                    driver: $("#driver").val(),
                    loads: $("#loads").val()
                },
                async: true,
                dataType: "json",
                beforeSend: function () {
                    $('#result_destination').html('Loading...');
                    $('#result_destination').show();
                },
                success: function (data) {
                    //                console.log('google result ' + data.status);
                    //
                    //                $('#result_destination').show();
                }
            });
        });

        // Get loads filtered
        $('body').on('click', '#load_search', function (evt) {
            evt.preventDefault();
            $.ajax({
                type: "POST",
                url: '<?php echo site_url('load/get_load_view/0/1/1/date_created/desc') ?>',
                data: {
                    search_customer: $("#search_customer").val(),
                    search_carrier: $("#search_carrier").val(),
                    search_load_number: $("#search_load_number").val()
                },
                async: true,
                dataType: "json",
                beforeSend: function () {
                    $('#result_destination').html('Loading...');
                    $('#result_destination').show();
                },
                success: function (data) {
                    $('#list_load tbody').empty();
                    var output = '';
                    for (var i = 0; i < data.length; i++) {
                        var status = data[i].status;
                        var data_format = data[i].date_created.split(" ");
                        var myd = data_format[0].split("-");
                        output += '<tr data-status="' + status + '" id="load_' + data[i].idts_load + '" data-load_id="' + data[i].idts_load + '" data-toggle="popover" class="po">';
                        output += '<td>' + data[i].load_number + '</td>';
                        output += '<td>' + myd[1] + '/' + myd[2] + '/' + myd[0] + ' ' + data_format[1] + '</td>';
                        output += '<td>' + data[i].carrier_name + '</td>';
                        output += '<td>' + data[i].driver_name + ' ' + data[i].driver_last_name + '</td>';
                        output += '<td>' + data[i].driver_address + '</td>';
                        output += '<td class="color" style="font-weight: 800;color: #666;">' + status + '</td>';
                        output += '<td><a href=" <?php echo site_url('load/update2') ?>' + '/' + data[i].idts_load + '"> Edit </a><a href=" <?php echo site_url('load/load_details') ?>' + '/' + data[i].idts_load + '"> View </a><a class="trash" data-id="' + data[i].idts_load + '" href="<?php echo site_url('load/trash') ?>' + '/' + data[i].idts_load + '"> Trash </a></td>';
                        output += '</tr>';
                    }
                    $('#list_load tbody').append(output);
                }
            });
        });

        //adds highlight when clicked
        $('#list_load tbody tr').on('click', function (event) {
            $(this).addClass('highlight').siblings().removeClass('highlight');
        });

        //Inicialize popover
        $('body').on('click', '.po', function (evt) {
            evt.preventDefault();
            $('#list_load tr').popover('hide');

            var load_id = $(this).data('load_id');
            var editHtml = '<ul><li data-load_edit="' + load_id + '">Edit</li></ul>';
            var popover = $(this).attr('id');
            $('#popover_content ul li a.editLink').attr('href', 'load/update2/' + popover);
            $('#popover_content ul li a.viewLink').attr('href', 'load/load_details/' + popover);

            $(this).popover({
                "trigger": "manual",
                "html": "true",
                "title": 'Load Options <span style="margin-left:15px;" class="pull-right"><a href="#" onclick="$(&quot;#' + popover + '&quot;).popover(&quot;toggle&quot;);" class="text-danger popover-close" data-bypass="true" title="Close"><i class="fa fa-close"></i></a></span>',
                "content": '<ul><li><a data-id="4" title="Edit this Load" href="load/update2/' + popover + '"><i class="icon-pencil"></i> Edit</a></li> <li><a data-id="4" title="View this Load" href="load/load_details/' + load_id + '"><i class="icon-eye-open"></i> View</a></li> <li><a data-id="4" title="Edit this Load" href="load/trash/' + load_id + '"><i class="icon-trash"></i>Trash</a> </li></ul>'
            });
        });

        $('body').on('click', '.edit', function (evt) {
            evt.preventDefault();
            var load = $(this).data('id');
            ;
            location.href = 'load/update2/' + load;
        });

        $('body').on('click', '.view', function (evt) {
            evt.preventDefault();
            var load = $(this).data('id');
            ;
            location.href = 'load/load_details/' + load;
        });

        // trash load
        $('body').on('click', '.trash', function (evt) {
            evt.preventDefault();
            var load = $(this);

            bootbox.confirm({
                size: 'small',
                title: 'Delete Load',
                message: "Are you sure you want to delete this load?",
                callback: function (result) {
                    if (result) {
                        console.log('ok');
                        $.ajax({
                            type: "POST",
                            url: '<?php echo site_url('load/trash') ?>/' + load.data('id'),
                            async: true,
                            dataType: "json",
                            success: function (o) {
                                if (o.result == 1) {
                                    $('#load_' + load.data('id')).hide();
                                }
                            }
                        });
                    } else {
                        console.log('cancel');
                    }
                }
            })
        });

        //show tender modal

        $('body').on('click', '.set-tender-modal', function (evt) {
            evt.preventDefault();
            var load = $(this);
            $('#tender_load_id').val(load.data('id'));
            $('#tender_load_number').val(load.data('load_number'));
            $('#tender_app_id').val(load.data('app_id'));
            $('#tender_apns_number').val(load.data('apns_number'));
            $('#email').val(load.data('email'));
            $('#tender_iframe').attr('src', '../../../tkgo_files2/' + load.data('bol_url'));
        });

        //tender push not, set in callcheck and send email

        $('body').on('click', '#tender-load', function (evt) {
            evt.preventDefault();
            var load = $(this);
            $.ajax({
                type: "POST",
                url: '<?php echo site_url('load/push_not_custom_msg_load') ?>',
                async: true,
                data: {
                    title: $('#title').val(),
                    msg: $('textarea#msg').val(),
                    load_id: $('#tender_load_id').val(),
                    load_number: $('#tender_load_number').val(),
                    app_id: $('#tender_app_id').val(),
                    apns_number: $('#tender_apns_number').val(),
                    email: $('#email').val(),
                    tender: 1,
                },
                dataType: "json",
                success: function (data) {
//                    var o = data['dbresult'];
//                    saveMsg(o.date, o.time, o.city, o.state, o.comment, o.entered_by);
//                    $('#styled_message').val('');
                }
            });
        });


        $('body').on('click', '.map_view', function (evt) {
            evt.preventDefault();

            //            console.log('load values: '+$(this).data('driver_lat'));

            var self = $(this);
            var lat = parseFloat(self.data('driver_lat'));
            var lng = parseFloat(self.data('driver_lng'));

            $("#driver_lat").val(parseFloat(self.data('driver_lat')));
            $("#driver_lng").val(parseFloat(self.data('driver_lng')));

            //            initialize2(lat, lng);

            //            $.ajax({
            //                type: "POST",
            //                url: 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' + lat + ',' + lng + '&key=AIzaSyAp8XadZn74QX4NLDphnzehQ0AN7q6NCwg',
            //                async: true,
            //                dataType: "json",
            //                beforeSend: function () {
            //                    $('#result_destination').html('Loading...');
            //                    $('#result_destination').show();
            //                },
            //                success: function (data) {
            ////                console.log('google result ' + data.status);
            //
            //                    var lat = data.results[0].geometry.location.lat;
            //                    var lng = data.results[0].geometry.location.lng;
            //                    var lng = data.results[0].geometry.location.lng;
            //                    var zipcode = data.results[0].address_components[6].long_name;
            //                    var city = data.results[0].address_components[2].long_name;
            //                    var state = data.results[0].address_components[4].short_name;
            //                    console.log('destination: state: ' + state + ', lat: ' + lat + ', lng: ' + lng + ', zipcode: ' + zipcode);
            //                    $('#destinationAddressModal').show();
            //                    $('#map-canvas2').css('display', 'block');
            //                    $('#map-canvas').css('display', 'none');
            //                    initialize2(lat, lng);
            ////
            ////                $('#result_destination').show();
            //                }
            //            });
        });

        $('#destinationAddressModal').on('shown.bs.modal', function (e) {
            $('#map-canvas2').html('');
            initialize2();
        });

        $('body').on('click', '#search_btn', function (evt) {
            evt.preventDefault();

            var search = $('#search');

            $.ajax({
                type: "POST",
                url: '',
                async: true,
                dataType: "json",
                beforeSend: function () {
                    $('#result_destination').html('Loading...');
                    $('#result_destination').show();
                },
                success: function (data) {
                    //                console.log('google result ' + data.status);
                    //
                    //                $('#result_destination').show();
                }
            });
        });


        $("#carrier").change(function () {
            var carrier = $(this);
            $('#driver option').remove();
            $('#driver').append('<option value="loading">loading...</option>');
            var postData = {carrier_id: $(this).val()
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

    });

    function editLoads() {
        $('#editLoadModal').modal('show');
    }

    function initialize2() {
//        $().html()
        var lat = $("#driver_lat").val();
        var lng = $("#driver_lng").val();
        var myLatlng = new google.maps.LatLng(lat, lng);
        var mapOptions = {
            zoom: 13,
            center: myLatlng
        };
        var map = new google.maps.Map(document.getElementById('map-canvas2'), mapOptions);
        var marker = new google.maps.Marker({
//            icon: 'map-marker-driver.png',
            position: new google.maps.LatLng(lat, lng),
            map: map,
        });

        $('#myModal').on('shown', function () {
            google.maps.event.trigger(map, "resize");
        });
    }

</script>