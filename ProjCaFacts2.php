<?php
namespace Stanford\ProjCaFacts2;

require_once "emLoggerTrait.php";

class ProjCaFacts2 extends \ExternalModules\AbstractExternalModule {
    use emLoggerTrait;

    // Fields in ACCESS CODE Project
    const FIELD_ACCESS_CODE         = 'access_code';
    const FIELD_ZIP                 = 'zip';
    const HOUSEHOLD_ID              = 'household_id';
    const QR_INPUT                  = 'qr_input';
    const TESTKIT_NUMBER            = 'participant_id';
    const SURVEY_ID                 = "survey_id";
    const FIELD_USED_ID             = 'participant_used_id';
    const FIELD_USED_DATE           = 'participant_used_date';
    const FIELD_USAGE_ATTEMPTS      = 'usage_attempts';

    // Fields in MAIN project
    const FIELD_KIT_HOUSEHOLD_CODE  = 'kit_household_code';
    const FIELD_KIT_SHIPPED_DATE    = 'kit_shipped_date';

    const FIELD_HHD_COMPLETE_DATE   = 'hhd_complete_date';
    const FIELD_HHD_RECORD_ID       = 'hhd_record_id';
    const FIELD_HHD_PARTICIPANT_ID  = 'hhd_participant_id';

    const FIELD_DEP1_COMPLETE_DATE  = 'dep_1_complete_date';
    const FIELD_DEP1_RECORD_ID      = 'dep_1_record_id';
    const FIELD_DEP1_PARTICIPANT_ID = 'dep_1_participant_id';

    const FIELD_DEP2_COMPLETE_DATE  = 'dep_2_complete_date';
    const FIELD_DEP2_RECORD_ID      = 'dep_2_record_id';
    const FIELD_DEP2_PARTICIPANT_ID = 'dep_2_participant_id';

    private   $access_code
            , $zip_code
            , $household_id
            , $testkit_number
            , $enabledProjects
            , $main_project_record
            , $main_project
            , $access_code_record
            , $access_code_project
            , $kit_submission_record
            , $kit_submission_project
            , $follow_up_record
            , $follow_up_project;

    // This em is enabled on more than one project so you set the mode depending on the project
    static $MODE;  // access_code_db, kit_order, kit_submission

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	    if (defined(PROJECT_ID)) {
	        // Get the mode of the module
	        self::$MODE = $this->getProjectSetting('em-mode');
	        $this->emDebug("In mode " . self::$MODE);
        }

        // put the proper project ids into class vars
        $this->getAllSupportProjects();
    }

    function redcap_every_page_top($project_id){
		// every page load
		// parse URL for id
		// update the flowsheet launch url to pass in info for id

		// THIS IS SO IMPORTANT FOR DOING THE DAGS
        // $_SESSION["username"] = \ExternalModules\ExternalModules::getUsername();

        $proj_links = array("CA-FACTS Pending Invites Report", "CA-FACTS Bulk Upload Lab Results", "CA-FACTS Test Kit / UPC Linkage","CA-FACTS Return Scan","CA-FACTS Unique Acess Code Generator", "CA-FACTS Results Sent Check Off", "CA-FACTS Reconcile Submission - Main", "CA-FACTS Follow Up Survey Check");
        switch($project_id){
            case $this->main_project:
                $hide_links = array(4);
            break;

            case $this->kit_submission_project:
                $hide_links = array(0,1,2,3,4, 5, 6,7,8);
            break;

            default:
                $hide_links = array(0, 1, 2,3, 5,6,7,8);
            break;
        }
		?>
		<script>
			$(document).ready(function(){
                var proj_links = <?=json_encode($proj_links)?>;
                var hide_links = <?=json_encode($hide_links)?>;

                for(var i in hide_links){
                    var hide_text = proj_links[hide_links[i]];
                    $("a:contains('"+hide_text+"')").parent().remove();
                }

                var move_this = $("a:contains('CA-FACTS Invitation API Instructions')").parent("div");
                move_this.parent().prepend(move_this);
			});
		</script>
		<?php
    }

    /**
     * Print all enabled projects with this EM
     */
    public function displayEnabledProjects($creation_xml_array) {
        // Scan
        $this->getEnabledProjects();
        ?>
        <table class="table table-striped table-bordered" style="width:100%">
            <tr>
                <th>EM Mode</th>
                <th>Project ID</th>
                <th>Project Name</th>
            </tr>
            <?php
            $modes = array("access_code_db", "kit_order");
            foreach($modes as $mode){
                $pid    = isset($this->enabledProjects[$mode]) ? "<a target='_BLANK' href='" . $this->enabledProjects[$mode]['url'] . "'>" . $this->enabledProjects[$mode]['pid'] . "</a>" : "N/A";
                $pname  = isset($this->enabledProjects[$mode]) ?  $this->enabledProjects[$mode]['name'] : "<a href='".$creation_xml_array[$mode]."' target='_BLANK'>Create project [XML Template]</a>";
                echo "<tr>
                        <th>$mode</th>
                        <th>$pid</th>
                        <th>$pname</th>
                    </tr>";
            }
            ?>
        </table>
        <?php
    }

    /**
     * Load all enabled projects with this EM
     */
    public function getEnabledProjects() {
        $enabledProjects    = array();
        $projects           = \ExternalModules\ExternalModules::getEnabledProjects($this->PREFIX);
        while($project = db_fetch_assoc($projects)){
            $pid  = $project['project_id'];
            $name = $project['name'];
            $url  = APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $project['project_id'];
            $mode = $this->getProjectSetting("em-mode", $pid);

            $enabledProjects[$mode] = array(
                'pid'   => $pid,
                'name'  => $name,
                'url'   => $url,
                'mode'  => $mode
            );

        }

        $this->enabledProjects = $enabledProjects;
        // $this->emDebug($this->enabledProjects, "Enabled Projects");
    }

    /**
     * FIND SUPPORT PROJECTS AND THEIR PIDs
     * @return bool
     */
    public function getAllSupportProjects(){
        $this->getEnabledProjects();
        foreach($this->enabledProjects as $project){
            $pid            = $project["pid"];
            $project_mode   = $project["mode"];
            switch($project_mode){
                case "access_code_db":
                    $this->access_code_project = $pid;
                break;

                case "kit_order":
                    $this->main_project = $pid;
                break;

                case "kit_submission":
                    $this->kit_submission_project = $pid;
                break;

                case "follow_up":
                    $this->follow_up_project = $pid;
                break;
            }
        }
    }

    /**
     * Get record_id by USPS tracking
     * @return bool
     */
    public function findMainRecordByTracking($usps_track){
        $filter     = "[return_tracking_number] = '" . $usps_track . "' and [kit_returned_date] = ''";
        $fields     = array("record_id");
        $q          = \REDCap::getData($this->main_project, 'json', null , $fields  , null, null, false, false, false, $filter);
        $results    = json_decode($q,true);

        $record_id  = null;
        if(!empty($results)){
            $result     = current($results);
            $record_id  = $result["record_id"];
        }

        return $record_id;
    }

    /**
     * when shipping boxes returned.
     * @return bool
     */
    public function datestampKitReturn($record_id){
        $data   = array(
            "record_id"                 => $record_id,
            "kit_returned_date"         => Date("Y-m-d")
        );
        $this->emDebug("save return date data", $data);
        $r      = \REDCap::saveData($this->main_project,  'json', json_encode(array($data)) );
    }
    /**
     * Parses request and sets up object
     * @return bool request valid
     */
    public function parseFormInput() {
        $this->emDebug("Incoming POST AC + Zip: ", $_POST);

        if (empty($_POST)){
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
        $this->access_code   = isset($_POST[self::FIELD_ACCESS_CODE]) ? strtoupper(trim(filter_var($_POST[self::FIELD_ACCESS_CODE], FILTER_SANITIZE_NUMBER_INT))) : NULL ;
        $this->zip_code      = isset($_POST[self::FIELD_ZIP])         ? trim(filter_var($_POST[self::FIELD_ZIP], FILTER_SANITIZE_NUMBER_INT)) : NULL ;

        $valid               = (is_null($this->access_code) || is_null($this->zip_code)) ? false : true;
        return $valid;
    }

    /**
     * Verifies the invitation access code and marks it as used, and creates a record in the main project and returns a public survey URL
     * @return bool survey url link
     */
    public function formHandler() {
        // Match INCOMING AccessCode Attempt and Verify ZipCode , find the record in the AC DB
        $address_data = $this->getTertProjectData("access_code_db");
        if (!$address_data){
            $this->returnError("Error, no matching AC/ZIP combination found");
        }

        //TODO ALWAYS RETURN ADDRESS DATA but ADD PROPERTY FOR EXISTING
        if(!empty($address_data["participant_used_id"])){
            // AC ALREADY USED, bUT SEND THE SURVEY URL ANYWAY
            $next_id = $address_data["participant_used_id"];
        }else{
            //AT THIS POINT WE HAVE THE ACCESS CODE RECORD, IT HASNT BEEN ABUSED, IT HASNT YET BEEN CLAIMED
            //0.  GET NEXT AVAIL ID IN MAIN PROJECT
            $next_id = $this->getNextAvailableRecordId($this->main_project);

            //1.  CREATE NEW RECORD, POPULATE these 2 fields
            $data = array(
                "record_id" => $next_id,
                "code"      => $this->access_code
            );
            if($address_data){
                foreach($address_data as $k => $v){
                    if(in_array($k, array("record_id","participant_used_id","participant_used_date","usage_attempts","ca_facts_access_codes_complete"))){
                        continue;
                    }
                    $data[$k] = $v;
                }
            }
            $r    = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
            $this->emDebug("why not saving to main ?? " , $r);


            //2.  UPDATE AC DB record with time stamp and "claimed" main record project
            $data = array(
                "record_id"             => $this->access_code_record,
                "participant_used_id"   => $next_id,
                "participant_used_date" => date("Y-m-d H:i:s")
            );
            $r    = \REDCap::saveData($this->access_code_project, 'json', json_encode(array($data)) );
        }

        //3.  GET PUBLIC SURVEY URL WITH FIELDS LINKED
        $this->emDebug("is the survey link failing?");
        $event_id       = \REDCap::getEventIdFromUniqueEvent("baseline_arm_1");
        $survey_link    = \REDCap::getSurveyLink($record=$next_id, $instrument='language_select', $event_id, $instance=1, $project_id=$this->main_project);
        $this->emDebug("survey link" , $survey_link);
        // Return result
        header("Content-type: application/json");
        echo json_encode(array("survey_url" => $survey_link));
    }

    /**
     * Verifies the invitation access code and marks it as used, and creates a record in the main project with all the answers supplied via voice
     * @return bool survey url link
     */
    public function IVRHandler($call_vars) {
        // $call_vars = array(
        //     [lang] => en
        //     [speaker] => Polly.Joanna
        //     [accent] => en-US
        //     [action] => invitation-phone
        //     [language] => 1
        //     [code] => 123456
        //     [zip] => 94123
        //     [fingerprick] => 1
        //     [testpeople] => 3
        //     [smartphone] => 1
        //     [sms] => 1
        //     [phone] => 14158469192
        // )

        $this->access_code   = $call_vars["code"];
        $this->zip_code      = $call_vars["zip"];

        // Match INCOMING AccessCode Attempt and Verify ZipCode , find the record in the AC DB
        $address_data = $this->getTertProjectData("access_code_db");
        if (!$address_data){
            $this->returnError("Error, no matching AC/ZIP combination found");
        }


        $data = array();
        if(!empty($address_data["participant_used_id"])){
            // AC ALREADY USED, bUT SEND THE SURVEY URL ANYWAY
            $next_id = $address_data["participant_used_id"];
        }else{
           //AT THIS POINT WE HAVE THE ACCESS CODE RECORD, IT HASNT BEEN ABUSED, IT HASNT YET BEEN CLAIMED
            //0.  GET NEXT AVAIL ID IN MAIN PROJECT
            $next_id = $this->getNextAvailableRecordId($this->main_project);

            if($address_data){
                foreach($address_data as $k => $v){
                    if(in_array($k, array("record_id","participant_used_id","participant_used_date","usage_attempts","ca_facts_access_codes_complete"))){
                        continue;
                    }
                    $data[$k] = $v;
                }
            }

            //2.  UPDATE AC DB record with time stamp and "claimed" main record project
            $data_ac = array(
                "record_id"             => $this->access_code_record,
                "participant_used_id"   => $next_id,
                "participant_used_date" => date("Y-m-d H:i:s")

            );
            $r    = \REDCap::saveData($this->access_code_project, 'json', json_encode(array($data_ac)) );
        }

        $data["record_id"] = $next_id;

        foreach($call_vars as $rc_var => $rc_val){
            if(in_array($rc_var, array("lang","speaker","accent","action","zip"))){
                continue;
            }
            $data[$rc_var] = $rc_val;
        }
        $data["ivr_intake"] = 1;

        $r    = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
        $this->emDebug("DID IT REALLY SAVE IVR ???", $r, $data);

        return false;
    }

    /**
     * Processes the KIT submission from GAUSS
     * @return bool survey url link
     */
    public function KitSubmitHandler($household_id, $participant_id) {
        $this->getAllSupportProjects();

        // Match INCOMING HOUSEHOLD ID + TEST KIT #
        $kit_submit_record_id = $this->getKitSubmissionId($household_id, $participant_id);
        if (!$kit_submit_record_id){
            $this->returnError("Error, no matching household id found");
        }

        // AT THIS POINT WE SHOULD HAVE THE RECORD_ID OF THE KITSUBMISSION THAT MATCHES THE INPUT
        $record_id          = $kit_submit_record_id["main_id"];
        $bc_event_arm       = $kit_submit_record_id["event_arm"];
        $is_hh              = $kit_submit_record_id["is_hh"];

        $event_id           = \REDCap::getEventIdFromUniqueEvent($bc_event_arm);

        $instrument         = $is_hh ? 'language_select_hh' : 'language_select_bc'; //english_adultchild_survey

        //GET PUBLIC SURVEY URL FOR THAT RECORD TO SEND BACK TO GAUSS TO DISPLAY TO THE USER
        $survey_link        = \REDCap::getSurveyLink($record_id, $instrument, $event_id, $instance=1, $project_id=$this->main_project);

        // TODO BETTER WAY TO WRANGLE THIS DATA? WAIT FOR REDCAP DONE FOR CLEAR PICTURE
        if($survey_link){
            $instrument_check = "blood_collection";
            $params = array(
                "project_id"    => $this->main_project,
                "return_format" => "json",
                "records"       => array($record_id),
                "fields"        => array($instrument_check . "_complete"),
                "events"        => $event_id,
                "filterLogic"   => ""
            );
            $res    = \REDCap::getData($params);
            $sq     = json_decode($res,1);

            if(!empty($sq)){
                $cur            = current(current($sq));
                $survey_q       = $cur[$instrument_check . "_complete"];
            }
        }
        $survey_complete        = empty($survey_q) ? false : true;
        $return_data = array(
            "error"        => "",
            "participant" => array("survey_url" => $survey_link, "complete" => $survey_complete)
        );

        $this->emDebug("returning blood collection survey url", $return_data);
        header("Content-type: application/json");
        echo json_encode($return_data);
    }

    /**
     * GET the KIT submission Record
     * @return bool record_id
     */
    public function getKitSubmissionId($household_id, $participant_id) {
        if(!empty($household_id)){
            $part_id    = $participant_id;
            $hh_id      = $household_id;

            // GET MAIN PROJECT RECORD ID
            // EVERY OUT GOING KIT MUST HAVE HAD an hh_id LINKED TO a single record.
            $event_id       = \REDCap::getEventIdFromUniqueEvent("baseline_arm_1");

            $filter         = "[kit_household_code] = '" . $hh_id . "'";
            $fields         = array("record_id","hhd_participant_id","dep_1_participant_id","dep_2_participant_id");
            $q              = \REDCap::getData($this->main_project, 'json', null , $fields  , $event_id, null, false, false, false, $filter);
            $main_results   = json_decode($q,true);

            $this->emDebug($household_id, $participant_id,$main_results);
            if(!empty($main_results)){
                // there should only be one.
                $main_record  = current($main_results);
                if(count($main_results) > 1){
                    //TODO DELETE THIS WHEN CONFIDENT
                    $this->emDebug("main results, should only be 1", count($main_results) );
                }

                $upc_var            = null;
                $qr_var             = null;

                $age_prefix         = "age";
                $sex_prefix         = "sex";
                $codename_prefix    = "codename";
                $covid_prefix       = "covid1";

                //this just means no participant survey was filled out, but we still have an HHID! SO we can still update main record linkage
                // FIND FIRST AVAILABLE EMPTY
                $hhd    = strtoupper($main_record["hhd_participant_id"]);
                $dep1   = strtoupper($main_record["dep_1_participant_id"]);
                $dep2   = strtoupper($main_record["dep_2_participant_id"]);

                $is_hh  = false;
                if( empty($hhd) || (!empty($hhd) && $hhd == $part_id) ){
                    $matching_var   = "hhd_participant_id";
                    $upc_var        = "hhd_test_upc";
                    $qr_var         = "hhd_test_qr";
                    $bc_event_arm   = "head_of_household_arm_1";
                    $is_hh          = true;
                }else if( empty($dep1) || (!empty($dep1) && $dep1 == $part_id) ){
                    $matching_var   = "dep_1_participant_id";
                    $upc_var        = "dep_1_test_upc";
                    $qr_var         = "dep_1_test_qr";
                    $bc_event_arm   = "dependent_1_arm_1";
                }else if( empty($dep2) || (!empty($dep2) && $dep2 == $part_id) ){
                    $matching_var   = "dep_2_participant_id";
                    $upc_var        = "dep_2_test_upc";
                    $qr_var         = "dep_2_test_qr";
                    $bc_event_arm   = "dependent_2_arm_1";
                }else{
                    $matching_var = null;
                }


                if($matching_var){
                    // SAVE TO REDCAP
                    $data   = array(
                        "record_id"        => $main_record["record_id"] ,
                        $matching_var      => $part_id
                    );
                    $result = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
                    // $this->emDebug("No KS result found for $part_id save to matching $hh_id");
                }

                $matched_result = array("upc_var" => $upc_var, "qr_var" => $qr_var, "is_hh" => $is_hh ,"participant_id" => $part_id, "main_id" => $main_record["record_id"], "all_matches" => $ks_results, "event_arm" => $bc_event_arm);
                return $matched_result;  // can be null
            }else{
                //every HH_id shoudl be accounted for since its required to ship out
                $this->emDebug("NO matching records in Main Project", $hh_id);
            }
        }else{
            $this->emDebug("API didn't return anything for this qrscan", $household_id, $participant_id);
        }
        return null;
    }

    /**
     * Get records of completed invitation questionaires that have not had kits shipped yet
     * @return array of records
     */
    public function getPendingInvites(){
        $params = array(
            "return_format" => "json",
            "fields" => ["record_id","testpeople", "code", "access_code", "address_1" ,"address_2","city", "state", "zip", "kit_household_code", "xps_booknumber", "language", "smartphone", "smartphone_s", "smartphone_v", "smartphone_m", "testpeople_s","testpeople_v", "testpeople_m"],
            "filterLogic" => "([access_code] != '' AND [kit_household_code] = '' AND ([testpeople] != '' OR [testpeople_s] != '' OR [testpeople_v] != '' OR [testpeople_m] != '' )) OR ([xps_booknumber] != '' AND [kit_shipped_date] = '')",
        );

        $q          = \REDCap::getData($params);
        $results    = json_decode($q,true);

        return $results;
    }

    /**
     * generate per participant report
     * @return array of recordids created
     */
    public function sendOneMonthFollowUps(){
        // put the proper project ids into class vars
        $this->getAllSupportProjects();

        $days_to_follow_up = 30;

        // GET ALL SURVEYS THAT A FOLLOW UP HAS NOT BEEN SENT OUT
        $filter_logic = "[follow_up_link] = '' and [participant_id] <> '' and [household_id] <> ''";
        $params	= array(
            "project_id"    => $this->kit_submission_project,
            'return_format' => 'json',
			'fields'        => array(     "record_id",
                                          "household_id",
                                          "participant_id",
                                          "email", "email_s", "email_v", "email_m",
                                          "txt","txt_s", "txt_v","txt_m",
                                          "ks_timestamp","follow_up_link"
                            ),
            'filterLogic'   => $filter_logic
		);
        $q 	        = \REDCap::getData($params);
        $results    = json_decode($q, true);
        $this->emDebug("get all surveys with no followup", count($results));

        //FIND ALL THAT ARE 30 days old (or more)
        $save_to_follow_up  = array();
        $map_fu_ks          = array();
        $next_fu_id         = $this->getNextAvailableRecordId($this->follow_up_project);
        $i                  = 0;
        $now                = time();

        foreach($results as $result){
            $ks_record_id   = $result["record_id"];
            $household_code = $result["household_id"];
            $part_id        = $result["participant_id"];
            $ks_timestamp   = $result["ks_timestamp"];

            $check_date     = strtotime($ks_timestamp);
            $datediff       = $now - $check_date;
            $num_days       = floor($datediff / (60 * 60 * 24));

            if($num_days >= $days_to_follow_up){
                $map_fu_ks[$next_fu_id] = $ks_record_id;
                $temp = array(
                    "record_id"         => $next_fu_id,
                    "household_id"      => $household_code,
                    "participant_id"    => $part_id
                );
                array_push($save_to_follow_up, $temp);
                $next_fu_id++;
            }
        }

        //SAVE TO FOLLOW UP PROJECT
        $result = \REDCap::saveData($this->follow_up_project ,'json', json_encode($save_to_follow_up));
        $this->emDebug("save to follow up project", count($save_to_follow_up));

        //NOW GET THE SURVEY LINKS FOR EACH OF THE NEWLY ADDED FOLLOW UP RECORDS AND SAVE IT BACK TO THE KS project
        $update_ks_fu = array();
        if(empty($result["errors"])){
            foreach($result["ids"] as $id){
                $survey_link = \REDCap::getSurveyLink($id, $instrument='cafacts_followup_survey', $event_id='', $instance=1, $project_id=$this->follow_up_project);

                $ks_id  = $map_fu_ks[$id];
                $temp   = array(
                    "record_id"         => $ks_id,
                    "follow_up_link"    => $survey_link
                );
                array_push($update_ks_fu, $temp);
            }
            $result = \REDCap::saveData($this->kit_submission_project ,'json', json_encode($update_ks_fu));
            $this->emDebug("update kit submission project", $result, $update_ks_fu);
        }
        return array($update_ks_fu, $result);
    }

    /**
     * generate per participant report
     * @return array of recordids created
     */
    public function householdPerParticipantReport(){
        $params	= array(
            "project_id"    => $this->access_code_project,
            'return_format' => 'json',
			'fields'        => array(     "record_id",
                                          "access_code"
                            ),
            'filterLogic'   => ""
		);
        $q 	    = \REDCap::getData($params);
        $acs    = json_decode($q, true);
        $county_map = array();
        foreach($acs as $ac){
            $county_map[$ac["access_code"]] = $ac["record_id"];
        }

        //SEARCH kit submission, GET ALL Records and their linked/null main record id
        $params	= array(
            "project_id"    => $this->main_project,
            'return_format' => 'json',
			'fields'        => array(     "record_id",
                                          "access_code"
                                        , "kit_household_code"
                                        , "hhd_participant_id"
                                        , "hhd_test_upc"
                                        , "hhd_test_result"
                                        , "hhd_sex"
                                        , "hhd_age"
                                        , "hhd_record_id"


                                        , "dep_1_participant_id"
                                        , "dep_1_test_upc"
                                        , "dep_1_test_result"
                                        , "dep_1_sex"
                                        , "dep_1_age"
                                        , "dep_1_record_id"

                                        , "dep_2_participant_id"
                                        , "dep_2_test_upc"
                                        , "dep_2_test_result"
                                        , "dep_2_sex"
                                        , "dep_2_age"
                                        , "dep_2_record_id"

                                        , "city"
                                        , "census_tract"

                            ),
            'filterLogic'   => ""
		);
        $q 		    = \REDCap::getData($params);
        $records    = json_decode($q, true);

        $data       = array();
        foreach($records as $record){
            $record_id          = $record["record_id"];
            $kit_household_code = $record["kit_household_code"];
            $access_code        = $record["access_code"];
            $city               = $record["city"];
            $census_tract       = $record["census_tract"];
            $county             = $county_map[$access_code] < 10001 ? "Santa Clara" : "Placer";

            foreach(array("hhd_participant_id", "dep_1_participant_id", "dep_2_participant_id") as $i => $part_id_var){
                $temp       = array();
                $part_id    = $record[$part_id_var];
                $prefix     = "hhd";
                $is_hhd     = true;
                if($i > 0){
                    $prefix = "dep_".$i;
                    $is_hdd = false;
                }

                if(!empty($part_id)){
                    $part_ks    = $record[$prefix . "_record_id"];
                    $part_upc   = $record[$prefix . "_test_upc"];
                    $part_res   = $record[$prefix . "_test_result"];
                    $part_age   = $record[$prefix . "_age"];
                    $part_sex   = $record[$prefix . "_sex"];
                    $temp = array($record_id, $access_code, $kit_household_code, $part_id, $prefix, $part_upc, $part_res, $part_ks, $part_sex, $part_age, $city, $census_tract, $county);
                }

                if(!empty($temp)){
                    array_push($data, $temp);
                }
            }
        }
        return $data;
    }

    /**
     * link submission surveys to main project records
     * @return array of recordids created
     */
    public function linkKits(){
        // Set all appropriate project IDs
        $this->getAllSupportProjects();

        $hh_id          = array();
        $dep_1_id       = array();
        $dep_2_id       = array();
        $all_submission         = array();
        $unlinked_submission    = array();

        $save_data_submission   = array();
        $save_data_mp           = array();

        $no_match_ks            = array();
        $no_match_mp            = array();

        //SEARCH kit submission, GET ALL Records and their linked/null main record id
        $params	= array(
            "project_id"    => $this->kit_submission_project,
            'return_format' => 'json',
			'fields'        => array("record_id", "household_id", "participant_id", "survey_type", "household_record_id"),
            'filterLogic'   => "[participant_id] <> ''"
		);
        $q 			= \REDCap::getData($params);
        $records 	= json_decode($q, true);
        foreach($records as $record){
            $part_id    = $record["participant_id"];
            $s_type     = $record["survey_type"];
            $all_submission[$part_id] = array(
                "record_id"     => $record["record_id"],
                "survey_type"   => $s_type,
                "household_record_id"   => $record["household_record_id"] ,
                "household_id"          => $record["household_id"]
            ) ;
        }
        $unlinked_submission = array_filter($all_submission, function($v){
            return empty($v["household_record_id"]);
        });
        $this->emDebug("ALL kit submission where participant id != empty", count($all_submission) );
        $this->emDebug("UNLINKED kit submission where household_record_id is empty",count($unlinked_submission) );

        //head of household id but no linking submission id (they maynot exist)
        $params	= array(
            'return_format' => 'json',
			'fields'        => array("record_id","kit_household_code", "hhd_participant_id", "hhd_record_id", "hhd_test_upc"),
            'filterLogic'   => "[kit_household_code] <> '' AND ( ([hhd_participant_id] <> '' AND [hhd_record_id] = '' ) )"
		);
        $q 			= \REDCap::getData($params);
        $records 	= json_decode($q, true);
        foreach($records as $record){
            $part_id    = $record["hhd_participant_id"];
            $rec_id     = $record["record_id"];
            $test_upc   = $record["hhd_test_upc"];

            $hh_id[] = $rec_id;

            if(array_key_exists($part_id, $all_submission)){
                // $this->emDebug("found matching kit submission , linking KS record_id", $all_submission[$part_id]["household_record_id"]);
                $save_data_mp[$rec_id] = array(
                    "record_id" => $rec_id,
                    "hhd_record_id" => $all_submission[$part_id]["record_id"]
                );
            }else{
                // $this->emDebug("no matching kit submission , record_id for " . $rec_id);
                $no_match_mp[$rec_id] = array( array(
                    "participant"       => "hhd",
                    "participant_id"    => $part_id,
                    "test_upc"          => $test_upc
                ));
            }
        }
        $this->emDebug("All head of householdl in main project without a submission linked id", count($hh_id), count($no_match_mp) );

        //dep 1 id but no linking submission id
        $params	= array(
            'return_format' => 'json',
			'fields'        => array("record_id","kit_household_code", "dep_1_participant_id", "dep_1_record_id", "dep_1_test_upc"),
            'filterLogic'   => "[kit_household_code] <> '' AND ( ([dep_1_participant_id] <> '' AND [dep_1_record_id] = '' ) )"
		);
        $q 			= \REDCap::getData($params);
        $records 	= json_decode($q, true);
        foreach($records as $record){
            $part_id    = $record["dep_1_participant_id"];
            $rec_id     = $record["record_id"];
            $test_upc   = $record["dep_1_test_upc"];

            $dep_1_id[$part_id] = $rec_id;

            if(array_key_exists($part_id, $all_submission)){
                // $this->emDebug("found matching kit submission , linking KS record_id", $all_submission[$part_id]["household_record_id"]);
                $temp = array(
                    "record_id" => $rec_id,
                    "dep_1_record_id" => $all_submission[$part_id]["record_id"]
                );
                if(array_key_exists($rec_id, $save_data_mp)){
                    $this->emDebug("dupe!", $rec_id);
                    $temp = array_unique(array_merge($save_data_mp[$rec_id], $temp));
                }
                $save_data_mp[$rec_id] = $temp;
            }else{
                // $this->emDebug("no matching kit submission , record_id for " . $rec_id);
                $temp = array(
                    "participant" => "dep_1",
                    "participant_id" => $part_id,
                    "test_upc" => $test_upc
                );

                if(array_key_exists($rec_id, $no_match_mp)){
                    array_push($no_match_mp[$rec_id], $temp);
                }else{
                    $no_match_mp[$rec_id] = array($temp);
                }
            }
        }
        $this->emDebug("All head of dep 1 in main project without a submission linked id", count($dep_1_id), count($no_match_mp) );

        //dep 2 id but no linking submission id
        $params	= array(
            'return_format' => 'json',
			'fields'        => array("record_id","kit_household_code", "dep_2_participant_id", "dep_2_record_id", "dep_2_test_upc"),
            'filterLogic'   => "[kit_household_code] <> '' AND ( ([dep_2_participant_id] <> '' AND [dep_2_record_id] = '' ) )"
		);
        $q 			= \REDCap::getData($params);
        $records 	= json_decode($q, true);
        foreach($records as $record){
            $part_id    = $record["dep_2_participant_id"];
            $rec_id     = $record["record_id"];
            $test_upc   = $record["dep_2_test_upc"];

            $dep_2_id[$part_id] = $rec_id;

            if(array_key_exists($part_id, $all_submission)){
                // $this->emDebug("found matching kit submission , linking KS record_id", $all_submission[$part_id]["household_record_id"], $rec_id);
                $temp = array(
                    "record_id" => $rec_id,
                    "dep_2_record_id" => $all_submission[$part_id]["record_id"]
                );
                if(array_key_exists($rec_id, $save_data_mp)){
                    $this->emDebug("dupe!", $rec_id);
                    $temp = array_unique(array_merge($save_data_mp[$rec_id], $temp));
                }
                $save_data_mp[$rec_id] = $temp;
            }else{
                // $this->emDebug("no matching kit submission , record_id for " . $rec_id);

                $temp = array(
                    "participant" => "dep_2",
                    "participant_id" => $part_id,
                    "test_upc" => $test_upc
                );

                if(array_key_exists($rec_id, $no_match_mp)){
                    array_push($no_match_mp[$rec_id], $temp);
                }else{
                    $no_match_mp[$rec_id] = array($temp);
                }
            }
        }
        $this->emDebug("All head of dep 2 in main project without a submission linked id", count($dep_2_id), count($no_match_mp) );

        $this->emDebug("found some matches", $save_data_mp);
        $r = \REDCap::saveData($this->main_project ,'json', json_encode($save_data_mp) );
        if(empty($r["errors"])){
            $this->emDebug("Main Project Linkage Made : " . count($save_data_mp) . " records saved");
            $mp_saved = true;
        }else{
            $this->emDebug("ERRORS saving Main Project Linkage: " ,  $r["errors"] );
        }

        // combine dep 1 and 2
        $problem_dep_id = array_merge($dep_1_id, $dep_2_id);

        //link submission records with participant id with a main record id
        foreach($unlinked_submission as $part_id => $unlinked){
            $submission_record_id = $unlinked["record_id"];
            if($unlinked["survey_type"] == 1){
                if(array_key_exists($part_id, $hh_id)){
                    $save_data_submission[] = array(
                        "record_id"             => $submission_record_id,
                        "household_record_id"   => $hh_id[$part_id]
                    );
                }else{
                    // $this->emDebug("no household match");
                    $no_match_ks[$submission_record_id] = array("participant_id" => $part_id, "head_of_household" => true);
                }
            }else{
                //type 2
                if(array_key_exists($part_id, $problem_dep_id)){
                    $save_data_submission[] = array(
                        "record_id"             => $submission_record_id,
                        "household_record_id"   => $problem_dep_id[$part_id]
                    );
                }else{
                    // $this->emDebug("no dependent match");
                    $no_match_ks[$submission_record_id] =  array("participant_id" => $part_id, "household_id"=> $unlinked["household_id"], "head_of_household" => false);
                }
            }
        }
        $r = \REDCap::saveData($this->kit_submission_project ,'json', json_encode($save_data_submission) );
        if(empty($r["errors"])){
            $this->emDebug("Kit Submssion Linkage Made : " . count($save_data_submission) . " records saved");
            $ks_saved = true;
        }else{
            $this->emDebug("ERRORS saving Kit Submission Linkage: " ,  $r["errors"] );
        }

        return array(
            // "all_submission"        => $all_submission,
            "savedata_submission"   => $save_data_submission,
            "savedata_mp"           => $save_data_mp,
            "no_match_ks"           => $no_match_ks ,
            "no_match_mp"           => $no_match_mp ,
        );

    }
    /**
     * Takes the result of a scan and sends off to Gauss to return ID
     * @return varchar unique house hold id
     */

    public function getHouseHoldId($qrscan){
        // remove URL SCheme
        $url        = "https://c19.exahealth.com/artemis/decryptqr";
        $key        = "hRDauDM9We2B3YfQSMzRA7WowaHaOhv98b54LStQ";
        $headers    = array(
            "x-api-key: " . $key,
            "cache-control: no-cache",
            "content-type: application/json" ,
            "User-Agent: PHPCurlFromREDCapWebServer/1.2.3"
        );
        $data       = json_encode( array("encrypted_qrcode_data" => $qrscan) );
        try {
            $process = curl_init($url);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($process, CURLOPT_HTTPHEADER, $headers );
            curl_setopt($process, CURLOPT_POST, 1);
            curl_setopt($process, CURLOPT_POSTFIELDS, $data);
            curl_setopt($process, CURLINFO_HEADER_OUT, TRUE);

            $result     = curl_exec($process);
            $curlinfo   = curl_getinfo($process);
            $curlerror  = curl_error($process);

            curl_close($process);
        } catch (Exception $e) {
            $this->emDebug("what happened",  $e->getMessage());
            exit( 'Decrypt API request failed: ' . $e->getMessage() );
        }
        $j = json_decode($result,1);
        $this->emDebug("decoded QR",$qrscan, $j);
        return array("survey_id" => $j['survey_id'] , "household_id" => $j['household_id']);
    }

    public function getPendingResultsShipping(){
        $results = array();

        $params = array(
            "project_id"    => $this->main_project,
            "return_format" => "json",
            "fields"        => array("record_id","age1","sex1","age","sex","codename","address_1","address_2","city","state","zip","hhd_test_result","dep_1_test_result","dep_2_test_result","hhd_test_result_class","dep_1_test_result_class","dep_2_test_result_class", "hhd_test_upc","dep_1_test_upc", "dep_2_test_upc"),
            "events"        => array("baseline_arm_1","head_of_household_arm_1","dependent_1_arm_1","dependent_2_arm_1"),
            "filterLogic"   => '(([hhd_result_sent] = "" OR [hhd_result_sent] = "0") )
            OR (([dep_1_result_sent] = "" OR [dep_1_result_sent] = "0") )
            OR (([dep_2_result_sent] = "" OR [dep_2_result_sent] = "0") )'
        );
        $res        = \REDCap::getData($params);
        $results    = json_decode($res,1);

        $addy_cash      = array();
        $result_cash    = array();
        $participants   = array();
        $p2             = array();

        foreach($results as $result){
            $record_id  = $result["record_id"];
            $event      = $result['redcap_event_name'];
            $dep1       = false;

            if(!array_key_exists($record_id, $p2)){
                $p2[$record_id] = array();
            }
            switch($event){
                case "baseline_arm_1":
                    if(!array_key_exists($record_id,$addy_cash) ){
                        $addy_cash[$record_id]      = array();
                        $result_cash[$record_id]    = array();
                    }

                    $test_upc               = $result["hhd_test_upc"];
                    $test_result_raw        = $result["hhd_test_result"];
                    $test_result_cls        = $result["hhd_test_result_class"];

                    $test_upc_d1            = $result["dep_1_test_upc"];
                    $test_result_raw_d1     = $result["dep_1_test_result"];
                    $test_result_cls_d1     = $result["dep_1_test_result_class"];

                    $test_upc_d2            = $result["dep_2_test_upc"];
                    $test_result_raw_d2     = $result["dep_2_test_result"];
                    $test_result_cls_d2     = $result["dep_2_test_result_class"];

                    $result_cash[$record_id]["hhd"]     = array(
                        "UPC" => $test_upc,
                        "Test Result" => $test_result_raw,
                        "Result" => $test_result_cls
                    );
                    $result_cash[$record_id]["dep1"]    = array(
                        "UPC" => $test_upc_d1,
                        "Test Result" => $test_result_raw_d1,
                        "Result" => $test_result_cls_d1
                    );
                    $result_cash[$record_id]["dep2"]    = array(
                        "UPC" => $test_upc_d2,
                        "Test Result" => $test_result_raw_d2,
                        "Result" => $test_result_cls_d2
                    );

                    $temp = array(
                        "Address 1"  => $result["address_1"],
                        "Address 2"  => $result["address_2"],
                        "City"       => $result["city"],
                        "State"      => $result["state"],
                        "Zip"        => $result["zip"]
                    );
                    $addy_cash[$record_id] = $temp;
                break;

                case "head_of_household_arm_1":
                    $age                = $result["age1"];
                    $sex                = $result["sex1"];
                    $who                = "hhd";
                break;

                case "dependent_1_arm_1":
                    $dep1 = true;
                case "dependent_2_arm_1":
                    $age = $result["age"];
                    $sex = $result["sex"];
                    $who = $dep1 ? "dep1" : "dep2";
                break;
            }

            $codename   = $result["codename"];

            if($event !== "baseline_arm_1"){
                if(isset($p2[$record_id][$who])){
                    $temp = array(
                        "Age"       => $age,
                        "Gender"    => $sex,
                    );

                    $p2[$record_id][$who] = array_merge($p2[$record_id][$who], $temp);
                }
            }else{
                $temp = array(
                    "Record ID" => $record_id,
                    "Codename"  => $codename,
                );
                if(!empty($test_result_raw)){
                    $p2[$record_id]["hhd"] = $temp;
                }
                if(!empty($test_result_raw_d1)){
                    $p2[$record_id]["dep1"] = $temp;
                }
                if(!empty($test_result_raw_d2)){
                    $p2[$record_id]["dep2"] = $temp;
                }
            }
        }

        foreach($p2 as $record_id => $p){
            foreach ($p as $who => $part){
                if(isset($addy_cash[$record_id])){
                    $temp = array_merge($part, $addy_cash[$record_id]);

                    if(!isset($result_cash[$record_id][$who])){
                        unset($p2[$record_id][$who]);
                        continue;
                    }

                    $full = array_merge($temp, $result_cash[$record_id][$who]);
                    unset($full["who"]);
                    $p2[$record_id][$who] = $full;
                }
            }
        }

        foreach($p2 as $ps){
            foreach($ps as $p){
                $participants[] = $p;
            }
        }


//        $this->emDebug($participants);
        return $participants;
    }

     /**
     * GET DATA FROM PROJECT DATA TIED TO THIS EM
     * @return bool
     */
    public function getTertProjectData($p_type) {
        foreach ($this->enabledProjects as $project_mode => $project_data) {
            $pid = $project_data["pid"];
            if($project_mode == $p_type){
                if($p_type == "access_code_db"){
                    $filter     = "[access_code] = '" . $this->access_code . "'"; //AND [zip] = '". $this->zip_code ."'
                    $q          = \REDCap::getData($pid, 'json', null , null  , null, null, false, false, false, $filter);
                    $results    = json_decode($q,true);

                    $for_test   = in_array($this->access_code, array(123456,234567,345678,456789,567890));

                    foreach ($results as $result) {
                        $ac_code_record             = $result["record_id"];
                        $current_attempt            = $result["usage_attempts"] ?? 0;
                        $redeemed_participant_id    = $result["participant_used_id"];
                        $redeemed_participant_date  = $result["participant_used_date"];

                        // LIMIT ATTEMPTS
                        // NERF THIS RETURN SOMETHING EVERYTIME
                        if($current_attempt > 5 && !$for_test && false){
                            $this->emDebug("Too many attempts to redeem this Access Code.", $this->access_code, $this->zip_code);
                            return false;
                        }

                        //INCREMENT USAGE ATTEMPTS
                        $data   = array(
                            "record_id"      => $ac_code_record,
                            "usage_attempts" => $current_attempt + 1
                        );
                        $r      = \REDCap::saveData($pid, 'json', json_encode(array($data)) );


                        //VERIFIY THAT THE CODE USED MATCHES ZIPCODE OF ADDRESS FOR IT
                        if($result['zip'] == $this->zip_code){
                            if(!empty($redeemed_participant_id) && !empty($redeemed_participant_date) && !$for_test){
                                $this->emDebug("This Access Code has already been claimed on ", $redeemed_participant_date);

                                // NO LONGER BLOCK ATTEMPTS BUT ADD INDICATOR THAT ITS BEEN CLAIMED
                                // return false;
                            }

                            $this->emDebug("Found a matching AC/ZIP for: ", $this->access_code, $this->zip_code);
                            $this->access_code_record   = $ac_code_record;
                            $this->access_code_project  = $pid;
                            return $result;
                        }
                    }

                    $this->emDebug("No match found for in Access Code DB for : ", $this->access_code );
                }

                if($p_type == "kit_submission"){
                    $filter     = "[household_id] = '" . $this->household_id . "' AND [participant_id] = '". $this->testkit_number ."'";
                    $q          = \REDCap::getData($pid, 'json', null , null  , null, null, false, false, false, $filter);
                    $results    = json_decode($q,true);

                    foreach ($results as $result) {
                        $record_id = $result["record_id"];
                        return $record_id;
                    }

                    $this->emDebug("No match found for in HouseHold Id : ", $this->household_id );
                }
            }
        }
        return false;
    }

    /**
     * Set Temp Store Proj Settings
     * @param $key $val pare
     */
    public function setTempStorage($storekey, $k, $v) {
        if(!is_null($storekey)){
            $temp = $this->getTempStorage($storekey);
            $temp[$k] = $v;

            // THIS IS CAUSING TWILIO TO FAIL WHY?
            $this->setProjectSetting($storekey, json_encode($temp));
        }
        return;
    }

    /**
     * Get Temp Store Proj Settings
     * @param $key $val pare
     */
    public function getTempStorage($storekey) {
        if(!is_null($storekey)){
            $temp = $this->getProjectSetting($storekey);
            $temp = empty($temp) ? array() : json_decode($temp,1);
        }else{
            $temp = array();
        }
        return $temp;
    }

    /**
     * rEMOVE Temp Store Proj Settings
     * @param $key $val pare
     */
    public function removeTempStorage($storekey) {
        $this->removeProjectSetting($storekey);
        return;
    }

    /**
     * Make a new redirect Action url, NOT IN USE NOW, BUT COULD POSSILBY BE USEFUL LATER
     * @param $action
     */
    public function makeActionUrl($action){
        $scheme             = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
        $curURL             = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url          = parse_url($curURL);
        $qsarr              = explode("&", urldecode($parse_url["query"]) );
        if(isset($_GET["action"]) ){
            foreach($qsarr as $i => $str){
                if(strpos($str,"action") > -1){
                    $this->emDebug("found action, remove it:", $str);
                    unset($qsarr[$i]);
                    break;
                }
            }
            array_unshift($qsarr,"action=".$action);
        }
        return $scheme . $parse_url["host"] . $parse_url["path"] . "?" . implode("&",$qsarr);
    }

    /**
     * Parse IVR Script + Translations
     * @param $filename
     */
    public function parseTextLanguages($filename) {
        $file       = fopen($filename, 'r');
        $dict       = array();
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $var_key    = trim($data[0]);
            $en_val     = trim($data[1]);
            $sp_val     = trim($data[2]);
            $zh_val     = trim($data[3]);
            $vi_val     = trim($data[4]);

            $dict[$var_key] = array(
                "en" => $en_val,
                "es" => $sp_val,
                "zh" => $zh_val,
                "vi" => $vi_val
            );
        }
        fclose($file);
        return $dict;
    }

    /**
     * GET Next available RecordId in a project
     * @return bool
     */
    public function getNextAvailableRecordId($pid){
        $next_id = \REDCap::reserveNewRecordId($pid);
        return $next_id;
    }

    /*
        Pull static files from within EM dir Structure
    */
    function getAssetUrl($audiofile = "v_languageselect.mp3", $hard_domain = ""){
        $audio_file = $this->framework->getUrl("getAsset.php?file=".$audiofile."&ts=". $this->getLastModified() , true, true);

        if(!empty($hard_domain)){
            $audio_file = str_replace("http://localhost",$hard_domain, $audio_file);
        }

        return $audio_file;
    }

    function setLastModified(){
        $ts = time();
        $this->setSystemSetting("last_modified",$ts);
        $this->LAST_MODIFIED = $ts;
    }

    function getLastModified(){
        return time();

        if(empty($this->LAST_MODIFIED)){
	        $ts = $this->getSystemSetting("last_modified");
	        if(empty($ts)){
                $this->setLastModified();
            }else{
                $this->LAST_MODIFIED = $ts;
            }
        }

	    return $this->LAST_MODIFIED;
    }

    /**
     * Return an error
     * @param $msg
     */
    public function returnError($msg) {
        $this->emDebug($msg);
        header("Content-type: application/json");
        echo json_encode(array("error" => $msg));
        exit();
    }

    /*
        USE mail func
    */
    public function sendEmail($subject, $msg, $from="Twilio VM", $to="ca-factstudy@stanford.edu"){
        //boundary
        $semi_rand = md5(time());
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

        //headers for attachment
        //header for sender info
        $headers = "From: "." <".$from.">";
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

        //multipart boundary
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
            "Content-Transfer-Encoding: 7bit\n\n" . $msg . "\n\n";

        if (!mail($to, $subject, $message, $headers)) {
            $this->emDebug("Email NOT sent");
            return false;
        }
        $this->emDebug("Email sent");
        return true;
    }

    /*
        Parse CSV to batchupload test Results
    */
    public function parseResultsCSVtoDB($file){
        $header_row = true;
        $file       = fopen($file['tmp_name'], 'r');

        $headers    = array();
        $results    = array();

        if($file){
            while (($line = fgetcsv($file)) !== FALSE) {
                if($header_row){
                    // adding extra column to determine which file the data came from
                    $headers 	= $line;
                    $header_row = false;
                }else{
                    // adding extra column to determine which csv file the data came from
                    array_push($results, $line);
                }
            }
            fclose($file);
        }

        $success            = array();
        $failed             = array();

        $main_data_buffer   = array();
        $main_data_update   = array();
        $kit_data_update    = array();

        foreach($results as $rowidx => $result){
            $upc            = $result[0];
            $test_returned  = Date("Y-m-d", strtotime($result[1]));
            $test_date      = Date("Y-m-d", strtotime($result[2]));
            $test_batch     = $result[3];
            $test_result    = $result[4];

            //FIND THE kit_submission_record(s) by UPC , POSSIBLE ITS NOT IN THERE if participant didnt comlete a survey, MORE LIKELY IN main Project, Also Possible there will be multiple
            $filter         = "[kit_upc_code] = '" . $upc . "'";
            $fields         = array("record_id");
            $q              = \REDCap::getData($this->kit_submission_project, 'json', null , $fields  , null, null, false, false, false, $filter);
            $kit_results    = json_decode($q,true);
            foreach($kit_results as $result){
                // SAVE RESULTS TO REDCAP
                $data   = array(
                    "record_id"     => $result["record_id"],
                    "test_returned" => $test_returned,
                    "test_date"     => $test_date,
                    "test_batch"    => $test_batch,
                    "test_result"   => $test_result
                );
                $r = \REDCap::saveData($this->kit_submission_project, 'json', json_encode(array($data)) );
                if(!empty($r["errors"])){
                    $this->emDebug("Error saving to kit_sub record " . $result["record_id"] , $r);
                }
            }

            //FIND THE Main Record by UPC, Then figure out WHICH one it belongs to... and update the test result
            $filter         = "[hhd_test_upc] = '" . $upc . "' OR [dep_1_test_upc] = '" . $upc . "' OR [dep_2_test_upc] = '" . $upc . "'";
            $fields         = array("record_id","hhd_test_upc","dep_1_test_upc","dep_2_test_upc");
            $q              = \REDCap::getData($this->main_project, 'json', null , $fields  , null, null, false, false, false, $filter);
            $main_results   = json_decode($q,true);
            if(!empty($main_results)){
                $main_record  = current($main_results);
                if(count($main_results) > 1){
                    //TODO DELETE THIS WHEN CONFIDENT
                    $this->emDebug("main results, should only be 1", count($main_results) );
                }

                $result_var         = null;
                $complete_date_var  = null;
                if($main_record["hhd_test_upc"] == $upc){
                    $result_var         = "hhd_test_result";
                    $complete_date_var  = "hhd_complete_date";
                }else if($main_record["dep_1_test_upc"] == $upc){
                    $result_var         = "dep_1_test_result";
                    $complete_date_var  = "dep_1_complete_date";
                }else if($main_record["dep_2_test_upc"] == $upc){
                    $result_var         = "dep_2_test_result";
                    $complete_date_var  = "dep_2_complete_date";
                }

                if($result_var && $complete_date_var){
                    $data = array(
                        "record_id"         => $main_record["record_id"],
                        $result_var         => $test_result,
                        $complete_date_var  => $test_date
                    );
                    $r  = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
                    if(empty($r["errors"])){
                        $success[] = $upc;
                    }else{
                        $this->emDebug("Error saving to main record " . $main_record["record_id"] , $r);
                    }
                }
            }else{
                //Couldnt find the UPC in the main project?
                $failed[] = $upc;
                $this->emDebug("Couldnt find $upc in main project");
            }
        }

        $return = array( "total" => count($results), "success" => count($success), "failed" => $failed );
        return $return;
    }

    /*
        Parse CSV to batchupload test Results
    */
    public function parseResultsSentCSV($file){
        $header_row = true;
        $file       = fopen($file['tmp_name'], 'r');

        $headers    = array();
        $results    = array();

        if($file){
            while (($line = fgetcsv($file)) !== FALSE) {
                if($header_row){
                    // adding extra column to determine which file the data came from
                    $headers 	= $line;
                    $header_row = false;
                }else{
                    // adding extra column to determine which csv file the data came from
                    array_push($results, $line);
                }
            }
            fclose($file);
        }

        $results_sent_date  = Date("Y-m-d");
        $data               = array();
        $this->emDebug("results sent date", $results_sent_date);
        foreach($results as $rowidx => $result){
            $which_var          = null;
            $main_record_id     = $result[0];

            if( in_array("Head of Household Test UPC", $headers) ){
                $which_var = "hhd_result_sent";
            }else if( in_array("Dependent 1 Test UPC", $headers) ){
                $which_var = "dep_1_result_sent";
            }else if( in_array("Dependent 2 Test UPC", $headers) ){
                $which_var = "dep_2_result_sent";
            }

            if($which_var){
                $temp = array(
                    "record_id"         => $main_record_id,
                    $which_var          => 1
                );
                // $temp[$which_var_date] = $results_sent_date;
                $data[]  = $temp;
            }else{
                $this->emDebug("wtf couldnt find headers label?", $headers);
            }
        }
        $r  = \REDCap::saveData($this->main_project, 'json', json_encode($data) );
        if(empty($r["errors"])){
            $success = $r["item_count"];
            $this->emDebug("All Records Saved", $r);
        }else{
            $this->emDebug("Error saving some(?) records", $r);
            $success = 0;  //? are there partial succcess is it all or none?
        }
        $return = array( "success" => $success , "errors" => $r["errors"], "total_rows" => count($results) );
        return $return;
    }

    /*
        Parse CSV to batchupload test Results
    */
    public function parseUPCLinkCSVtoDB($file){
        $header_row = true;
        $file       = fopen($file['tmp_name'], 'r');

        $headers    = array();
        $results    = array();

        //now we parse the CSV, and match the QR -> UPC
        if($file){
            while (($line = fgetcsv($file)) !== FALSE) {
                if($header_row){
                    // adding extra column to determine which file the data came from
                    $headers 	= $line;
                    $header_row = false;
                }else{
                    // adding extra column to determine which csv file the data came from
                    array_push($results, $line);
                }
            }
            fclose($file);
        }

        $success        = array();
        $failed         = array();
        foreach($results as $rowidx => $result){
            $qrscan     = $result[0];
            $upcscan    = $result[1];

            usleep( 500000 );
            $hh_part    = $this->getHouseHoldId($qrscan);
            $api_result = $this->getKitSubmissionId($hh_part[self::HOUSEHOLD_ID],$hh_part[self::SURVEY_ID]);

            // SAVE linkage to Main Project
            if(!empty($api_result["main_id"])){
                $mainid         = $api_result["main_id"];
                $which_upc      = $api_result["upc_var"];
                $which_qr       = $api_result["qr_var"];


                $temp   = array(
                    "record_id"             => $mainid,
                    $which_upc              => $upcscan,
                    $which_qr               => $qrscan
                );

                if($which_upc && $which_qr){
                    $r  = \REDCap::saveData($this->main_project, 'json', json_encode(array($temp)) );
                    if(!empty($r["errors"])){
                        $this->emDebug("ERROR saving to main project, the UPC and main record_id", $rowidx, $r);
                        $failed[]   = array("row" => $rowidx+1, "qrscan" => $qrscan, "upcscan" => $upcscan, "hh_id" => $hh_part[self::HOUSEHOLD_ID], "survey_id" => $hh_part[self::SURVEY_ID], "kitsub" => $api_result);
                    }else{
                        $success[]  = $mainid;
                    }
                }else{
                    $this->emDebug("no upc_var , or qr_var for record $mainid", $hh_part[self::SURVEY_ID], $api_result);
                    $failed[]   = array("row" => $rowidx+1, "qrscan" => $qrscan, "upcscan" => $upcscan, "hh_id" => $hh_part[self::HOUSEHOLD_ID], "survey_id" => $hh_part[self::SURVEY_ID], "kitsub" => $api_result);
                }
            }

            if(!empty($api_result["participant_id"])){
                $record_id      = $api_result["record_id"];
                $records        = $api_result["all_matches"];
                $mainid         = $api_result["main_id"];

                foreach($records as $result){
                    // SAVE TO REDCAP
                    $temp   = array(
                        "record_id"             => $result["record_id"],
                        "kit_upc_code"          => $upcscan,
                        "kit_qr_input"          => $qrscan,
                        "household_record_id"   => $mainid
                    );
                    $r  = \REDCap::saveData($this->kit_submission_project, 'json', json_encode(array($temp)) );
                }
            }else{
                // $this->emDebug("No API results for qrscan for row $rowidx");
                $failed[]   = array("row" => $rowidx+1, "qrscan" => $qrscan, "upcscan" => $upcscan, "hh_id" => $hh_part[self::HOUSEHOLD_ID], "survey_id" => $hh_part[self::SURVEY_ID], "kitsub" => $api_result);
            }
        }

        $this->emDebug(array( "total" => count($results), "success" => count($success), "failed" => $failed ));
        $return = array( "total" => count($results), "success" => count($success), "failed" => $failed );
        return $return;
    }

    /*
        CURL function to interact with SHipping APIs
        List of Services : GET https://xpsshipper.com/restapi/v1/customers/[]/services
        Create new Order : PUT  https://xpsshipper.com/restapi/v1/customers/[]/integrations/[]/orders/:orderId   (:orderId = Household id)
        Get Shipping Label : GET  https://xpsshipper.com/restapi/v1/customers/[]/shipments/:bookNumber/label/PNG   (:bookNumber = :orderId ???= Household id)
    */
    public function xpsCurl($api_url, $method="GET", $data=array()){
        $api_key = $this->getProjectSetting('xpsship-api-key');


        $ch = curl_init($api_url);
        $header_data = array( 'Authorization: RSIS ' . $api_key );

        if($method == "PUT" || $method == "POST"){
            array_push($header_data, 'Content-Type: application/json');
            array_push($header_data, 'Content-Length: ' . strlen($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if($method == "PUT"){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }else if($method == "POST"){
            curl_setopt($ch, CURLOPT_POST, true);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);

        $info 	= curl_getinfo($ch);
		$result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }


    /*
        Create ORDER for XPS
    */
    public function xpsData($hh_id, $testkits, $shipping_addy){
        $weight_in_lb = array("0.325", "0.388", "0.475");

        $data = array(
            "orderId"               => $hh_id
           ,"orderDate"             => date("Y-m-d")
           ,"shippingService"       => "usps_first_class"
           ,"shipperReference"      => "CA-FACTS / RC" . $shipping_addy["recordid"]
           ,"contentDescription"    => $testkits . " Test Kits"
           ,"weightUnit"            => "lb"
           ,"orderNumber"           => $hh_id
           ,"fulfillmentStatus"     => "pending"
           ,"shippingTotal"         => null
           ,"dimUnit"               => null
           ,"orderGroup"            => null
           ,"dueByDate"             => null
           ,"items"                 => null
           ,"sender"                => array(
                "name"          => "CA-FACTS"
               ,"company"       => "Stanford University"
               ,"address1"      => "1291 WELCH RD"
               ,"address2"      => "GRANT BLDG L134"
               ,"city"          => "Stanford"
               ,"state"         => "CA"
               ,"zip"           => "94305"
               ,"country"       => "US"
               ,"phone"         => "6507244947"
           )
           ,"receiver"              => array(
                "name"          => $shipping_addy["name"]
               ,"company"       => ""
               ,"address1"      => $shipping_addy["address1"]
               ,"address2"      => $shipping_addy["address2"]
               ,"city"          => $shipping_addy["city"]
               ,"state"         => $shipping_addy["state"]
               ,"zip"           => $shipping_addy["zip"]
               ,"country"       => "US"
           )
           ,"packages" => array(
               array(
                    "weight" => $weight_in_lb[$testkits-1]
                   ,"insuranceAmount"   => null
                   ,"declaredValue"     => null
                   ,"length"            => "6"
                   ,"width"             => "6"
                   ,"height"            => "2"
                )
           )
        );

       return $data;
    }

    /*
        CREATE USPS RETURN LABEL
        returns base64 Return Label, PostalROuting and Tracking Number
    */
    public function uspsReturnLabel($hh_id="CA Facts 2.0 Participant", $shipping_addy=array()){
        $merchant_id    = $this->getProjectSetting('usps-merchant-id');
        $mid            = $this->getProjectSetting('usps-mid');

        $usps_apiurl    = "https://returns.usps.com/Services/ExternalCreateReturnLabel.svc/ExternalCreateReturnLabel?externalReturnLabelRequest=";

        $xml_arr        = array(
             "MerchantAccountID"            => $merchant_id
            ,"MID"                          => $mid
            ,"BlankCustomerAddress"         => TRUE
            ,"LabelFormat"                  => "NOI"
            ,"LabelDefinition"              => "4X6"
            ,"ServiceTypeCode"              => "020"
            ,"MerchandiseDescription"       => "Exempt Human Specimen"
            ,"PackageInformation"           => "CAFacts2"
            ,"AddressOverrideNotification"  => TRUE
            ,"CallCenterOrSelfService"      => "Customer"
            ,"ImageType"                    => "PDF"
        );

        if(!empty($shipping_addy)){
            $xml_arr["PackageInformation"]  = "RC".$shipping_addy["record_id"];
            $xml_arr["CustomerName"]        = $hh_id;
            $xml_arr["CustomerAddress1"]    = $shipping_addy["address_1"];
            $xml_arr["CustomerCity"]        = $shipping_addy["city"];
            $xml_arr["CustomerState"]       = $shipping_addy["state"];
            $xml_arr["CustomerZipCode"]     = $shipping_addy["zip"];

            if(!empty($shipping_addy["address_2"])){
                $xml_arr["CustomerAddress2"] = $shipping_addy["address_2"];
            }

            $xmlDoc = new \DOMDocument();
            $root   = $xmlDoc->appendChild($xmlDoc->createElement("ExternalReturnLabelRequest"));
            foreach($xml_arr as $key => $val){
                $root->appendChild($xmlDoc->createElement($key,$val));
            }

            //make the output pretty
            $qs_params = urlencode( $xmlDoc->saveHTML() );

            $ch = curl_init($usps_apiurl.$qs_params);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);

            $info 	= curl_getinfo($ch);
            $result = curl_exec($ch);
            curl_close($ch);

            $new    = simplexml_load_string($result);

            // Convert into json
            $con    = json_encode($new);

            // Convert into associative array
            return json_decode($con, true);
        }else{
            $this->emDebug("seriously what the fuck now? 10 too many? how about 5");

            $bulk   = 10;
            $con    = array();

            $xmlDoc = new \DOMDocument();
            $root   = $xmlDoc->appendChild($xmlDoc->createElement("ExternalReturnLabelRequest"));
            foreach($xml_arr as $key => $val){
                $root->appendChild($xmlDoc->createElement($key,$val));
            }
            $saved_xml = $xmlDoc->saveHTML();
            for($i = 0; $i < $bulk; $i++){
                //make the output pretty
                $qs_params = urlencode( $saved_xml );

                $ch = curl_init($usps_apiurl.$qs_params);

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_VERBOSE, 0);

                $info 	= curl_getinfo($ch);
                $result = curl_exec($ch);
                curl_close($ch);

                $new    = simplexml_load_string($result);

                // Convert into json
                $wut    = json_encode($new);


                // Convert into json
                $con[]  = json_decode($wut, true);
            }

            // Convert into associative array
            return $con;
        }
    }

    /*
        Generate UNIQUE AC, Exclude already used ones.
        returns base64 Return Label, PostalROuting and Tracking Number
    */
    public function UniqueRandomNumbersWithinRange($min, $max, $quantity) {

        $fields     = array("access_code");
        $q          = \REDCap::getData('json', null , $fields);
        $results    = json_decode($q,true);

        $blacklist = array();
        if(!empty($results)){
            foreach($results as $result){
                array_push($blacklist, $result["access_code"]);
            }
        }

        $numbers    = range($min, $max);
        $uniques    = array_diff($numbers, $blacklist);

        shuffle($uniques);
        return array_slice($uniques, 0, $quantity);
    }

    // Project Crons
	public function projectCron($urls){
		$projects 	= $this->framework->getProjectsWithModuleEnabled();

		foreach($projects as $index => $project_id){
			foreach($urls as $url){
				$thisUrl 	= $url . "&pid=$project_id"; //project specific
				$client 	= new \GuzzleHttp\Client();
				$response 	= $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
				$this->emDebug("running cron for $url on project $project_id");
			}
		}
	}

	public function daily_month_followups(){
		// This will run hourly. Check Server time (PST) if it is called between 7 and 8 am PST then run it
		$server_hour = Date("H");
		echo $server_hour;
		if($server_hour > 7 && $server_hour < 8){
			$urls 		= array(
				$this->getUrl('cron/one_month_followup.php', true),
			); //has to be page
			$this->projectCron($urls);
		}
	}

}
?>
