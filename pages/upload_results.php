<?php
namespace Stanford\ProjCaFacts2;
/** @var \Stanford\ProjCaFacts2\ProjCaFacts2 $module */

if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "saveField":
            $field_type  = $_POST['field_type'];
            if($field_type == "file"){
                $file       = current($_FILES);
                $result     = $module->parseResultsCSVtoDB($file);
            }

            echo "<p id='upload_results'>".json_encode($result)."</p>";
            exit;
        break;

        default:
        break;
    }

    echo json_encode($result);
    exit;
}

$pending_results    = $module->getPendingResultsShipping();
$all_results        = $module->getAllRecordsWithResults();

$actual_link        = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if(isset($_GET["download_results_shipping"]) OR isset($_GET["download_all_results"]) ){
    $get_all        = isset($_GET["download_all_results"]);
    $headers        = array("Record ID","UPC" , "Age", "Gender", "Codename" , "Address 1", "Address 2", "City", "State", "Zip",  "Test Result", "Result" );
    if($get_all){
        $headers        = array("Record ID","Participant ID", "UPC" , "Age", "Gender", "Codename" ,  "Zip",  "Test Result" );
    }
    $output_dest    = 'php://output';
    $output         = fopen($output_dest, 'w') or die('Can\'t create .csv file, try again later.');

    //Add the headers
    fputcsv($output, $headers);

    // write each row at a time to a file
    foreach($pending_results as $part){
        $row_array      = array();
        $gender = empty($part["Gender"])? null : ($part["Gender"] == 1 ? "Male" : "Female");
        $age    = $part["Age"] ?? null;

        $row_array[]    = $part["Record ID"];
        if($get_all){
            $row_array[]    = $part["Participant ID"];
        }
        $row_array[]    = $part["UPC"];
        $row_array[]    = $age;
        $row_array[]    = $gender;
        $row_array[]    = $part["Codename"];
        if(!$get_all) {
            $row_array[] = $part["Address 1"];
            $row_array[] = $part["Address 2"];
            $row_array[] = $part["City"];
            $row_array[] = $part["State"];
        }
        $row_array[]    = $part["Zip"];
        $row_array[]    = $part["Test Result"];

        fputcsv($output, $row_array);
    }

    $file_name = $get_all ? "all_participants_with_results.csv" : "participant_test_results_for_mailing.csv";

    // output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' .$file_name);

    fclose($output);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>
<div style='margin:20px 40px 0 0;'>
    <h4>TEST RESULTS - BULK UPLOAD</h4>

    <p>Upload .CSV file using this <a href="<?=$CSV_EXAMPLE?>">[TEMPLATE.csv]</a></p>
    <br>
    <br>

    <?php
        $loading            = $module->getUrl("docs/images/icon_loading.gif");
        $loaded             = $module->getUrl("docs/images/icon_loaded.png");
        $failed             = $module->getUrl("docs/images/icon_fail.png");
        $qrscan_src         = $module->getUrl("docs/images/fpo_qr_bar.png");
        $doublearrow_src    = $module->getUrl("docs/images/icon_doublearrow.png");
        $link_kit_upc       = $module->getUrl("pages/link_kit_upc.php");
        $upload_results     = $module->getUrl("pages/upload_results.php");
    ?>

    <style>
        #pending_invites div{
            display:inline-block;
        }
        #pending_invites input{
            font-size: 20px;
            /* padding:10px;
            border-radius: 3px;
            border: 1px solid #ccc; */
            display:inline-block;
            cursor:pointer;
            width:800px;
            color:#999;
        }
        #pending_invites .qrscan{
            position:relative;
            cursor:pointer;
        }

        #pending_invites .upcscan label{
            width: 142px;
            background-position-X:-82px;
        }
        #pending_invites .upcscan{
            margin-left:200px;
            position:relative;
        }
        #pending_invites .upcscan:before{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:-130px;
            background:url(<?=$doublearrow_src?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan.loading:before{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:-130px;
            background:url(<?=$loading?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan.link_loading:after{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:102%;
            background:url(<?=$loading?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan.link_loaded:after{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:102%;
            background:url(<?=$loaded?>) no-repeat;
            background-size:contain;
        }


        #pending_invites h6{
            color:#999;
        }

        #pending_invites h6.next_step{
            color:#000;
            font-weight:bold;
        }

        #pending_invites h6.step_used{
            color:#999;
            font-weight:bold;
        }

        a.btn:visited {
            text-decoration:none;
            color:#fff;
        }

        #result_msg.good { color:green }
        #result_msg.bad { color:red }

        #result_msg{
            position:relative;
            min-height:20px;
            padding-left:25px;
        }
        #result_msg::before{
            content:"";
            position:absolute;
            left:0; top:0;
            width:20px;
            height:20px;
        }
        #result_msg.loading::before{
            background:url(<?=$loading?>) 50% no-repeat;
            background-size:contain;
        }
        #result_msg.loaded::before{
            background:url(<?=$loaded?>) 50% no-repeat;
            background-size:contain;
        }
        #result_msg.failed::before{
            background:url(<?=$failed?>) 50% no-repeat;
            background-size:contain;
        }
        #failed_rowids{
             min-height:150px;
        }
    </style>

    <section id="pending_invites">

        <div class='qrscan'>
            <h6 class="next_step">Upload CSV Here</h6>
            <em>Takes 1+ seconds per record</em>
            <br><br>
            <form method="post" enctype="multipart/form-data">
            <label for='upload_csv'></label><input type='file' name='upload_csv' id='upload_csv' placeholder="Test Results CSV"/>
            </form>
            <h6 id="result_msg" class="d-block my-3"></h6>
        </div>
    </section>
    <a href="<?=$link_kit_upc?>" id="upload_btn" type="button" class="btn btn-lg btn-primary">Upload and Process File</a>


    <hr>
    <style>
        .statuses th {
            padding:5px 8px;
            text-align:center;
        }
        .statuses td {
            text-align:center;
            background:#CCC;
        }
        .statuses tbody tr td.N {
            background:indianred !important;
            color:#fff;
            font-weight:bold;
        }
        .statuses tbody tr td.Y{
            background:mediumseagreen !important;
            color:#fff;
            font-weight:bold;
        }
        .statuses .participant{
            background:#fff;
            padding:8px;
            text-align:left;
        }
        .statuses tbody tr:nth-child(even) td {background: #FFF}
        .statuses tbody tr:nth-child(odd) td {background: #EFEFEF}
    </style>

    <section>
        <h4>Participant with Results (pending shipping) Report <span class="float-right"><a id="download_results_for_sending" class="btn btn-success btn-small" href="<?=$upload_results?>&download_results_shipping=1">Download CSV</a></span></h4>
        <table id="statuses" class="statuses" width="100%" border="1" data-page-length='25'>
            <thead>
            <tr>
                <th>Record ID</th>
                <th>UPC</th>
                <th>Test Result</th>
                <th>Result</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Codename</th>
                <th>Address 1</th>
                <th>Address 2</th>
                <th>City</th>
                <th>State</th>
                <th>Zip</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach($pending_results as $part){
                $gender = empty($part["Gender"])? null : ($part["Gender"] == 1 ? "Male" : "Female");
                $age    = $part["Age"] ?? null;
                $html = "<tr>";
                $html .= "<td>{$part["Record ID"]}</td>";
                $html .= "<td>{$part["UPC"]}</td>";
                $html .= "<td>{$part["Test Result"]}</td>";
                $html .= "<td>{$part["Result"]}</td>";
                $html .= "<td>$age</td>";
                $html .= "<td>$gender</td>";
                $html .= "<td>{$part["Codename"]}</td>";
                $html .= "<td>{$part["Address 1"]}</td>";
                $html .= "<td>{$part["Address 2"]}</td>";
                $html .= "<td>{$part["City"]}</td>";
                $html .= "<td>{$part["State"]}</td>";
                $html .= "<td>{$part["Zip"]}</td>";
                $html .= "</tr>";
                echo $html;
            }
            ?>
            </tbody>
        </table>
    </section>

    <hr>

    <section>
        <h4>All Participants with Results  <span class="float-right"><a id="download_all_results" class="btn btn-success btn-small" href="<?=$upload_results?>&download_all_results=1">Download CSV</a></span></h4>
        <table id="statuses_all" class="statuses" width="100%" border="1" data-page-length='25'>
            <thead>
            <tr>
                <th>Record ID</th>
                <th>Participant ID</th>
                <th>UPC</th>
                <th>Test Result</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Codename</th>
                <th>Zip</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach($all_results as $part){
                $gender = empty($part["Gender"])? null : ($part["Gender"] == 1 ? "Male" : "Female");
                $age    = $part["Age"] ?? null;
                $html = "<tr>";
                $html .= "<td>{$part["Record ID"]}</td>";
                $html .= "<td>{$part["Participant ID"]}</td>";
                $html .= "<td>{$part["UPC"]}</td>";
                $html .= "<td>{$part["Test Result"]}</td>";
                $html .= "<td>$age</td>";
                $html .= "<td>$gender</td>";
                $html .= "<td>{$part["Codename"]}</td>";
                $html .= "<td>{$part["Zip"]}</td>";
                $html .= "</tr>";
                echo $html;
            }
            ?>
            </tbody>
        </table>
    </section>

    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function(){
            $('#statuses').DataTable();
            $('#statuses_all').DataTable();

            $("#upload_btn").click(function(){
                var file =  $("#upload_csv").prop('files')[0];

                if(file){
                    ajaxlikeFormUpload($("#upload_csv"));
                }

                return false;
            });

            function ajaxlikeFormUpload(el){
                // create temp hidden iframe for submitting from/to;
                if($('iframe[name=iframeTarget]').length < 1){
                    var iframe = document.createElement('iframe');
                    $(iframe).css('display','none');
                    $(iframe).attr('src','#');
                    $(iframe).attr('name','iframeTarget');
                    $('body').append(iframe);

                    $(iframe).on('load', function(e) {
                        // Handler for "load" called.
                        var innerDoc    = iframe.contentDocument || iframe.contentWindow.document;
                        var iframe_doc  = $(innerDoc);
                        var result      = iframe_doc.find("#upload_results").text();
                        var result      = $.parseJSON(result);

                        var success_records = result["success"];
                        var fail_records    = result["failed"];
                        var total_rows      = result["total"];

                        $("#result_msg").removeClass("loading");
                        if(success_records){
                            $("#result_msg").addClass("loaded").html("Success " + success_records + " of <b>" + total_rows + "</b> records successfully updated");

                            var failed = $("<textarea>").attr("id","failed_rowids").val("UPC not found in main project:\r\n" + fail_records.join("\r\n"));
                            failed.insertAfter($("#result_msg"));
                        }else{
                            $("#result_msg").addClass("failed").html("Error : records not updated");
                        }
                    });
                }

                var input_field     = el.attr("name");
                var field_type      = el.attr("type");
                var file            = el.prop('files')[0];

                $("#result_msg").removeClass("loaded").removeClass("failed").removeClass("loading").addClass("loading").text("Processing data ...");
                $("#failed_rowids").remove();

                el.parent().attr("target","iframeTarget");
                el.parent().append($("<input type='hidden'>").attr("name","action").val("saveField"));
                el.parent().append($("<input type='hidden'>").attr("name","field_type").val(field_type));
                el.parent().append($("<input type='hidden'>").attr("name","input_field").val(input_field));
                el.parent().trigger("submit");
            }
        });
    </script>
</div>

