<?php
namespace Stanford\ProjCaFacts2;
/** @var \Stanford\ProjCaFacts2\ProjCaFacts2 $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$XML_AC_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTS20ACCESSCODES_2021-05-19_1435.REDCap.xml");
$XML_KO_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTS20MAINPROJECT_2021-05-19_1539.REDCap.xml");
?>

<div style='margin:20px 0;'>
    <h4>CA-FACTS EM Requirements</h4>
    <p>This EM will coordinate between <b>3 REDcap projects</b> to intake and track conversions of direct mail invitations for participation in home COVID testing.</p>
    <p>Once created, all three projects must have the <b>CA Facts Project EM</b> installed and configured to be identified as<br> <b>[ACCESS CODE DB], [KIT ORDER (MAIN)], and [KIT SUBMISSION]</b> respectively</p>

    <br>
    <br>

    <h5>Download CA-FACTS Project XML Templates:</h5>
    <ul>
    <li><?php echo "<a href='$XML_AC_PROJECT_TEMPLATE'>CA-FACTS 2.0 Access Code XML Project Template</a>" ?></li>
    <li><?php echo "<a href='$XML_KO_PROJECT_TEMPLATE'>CA-FACTS 2.0 Main Project XML Project Template</a>" ?></li>
    </ul>

    <br>
    <br>

    <h4>Enabled Projects (2 Required)</h4>
    <div>
        <?php echo $module->displayEnabledProjects(array("access_code_db" => $XML_AC_PROJECT_TEMPLATE, "kit_order" => $XML_KO_PROJECT_TEMPLATE)  ) ?>
    </div>

    <br>
    <br>

    <?php
        if($module->getProjectSetting("em-mode") == "kit_order"){
    ?>
        <h4>Invitation Sign Up Endpoint</h4>
        <p>Please configure the external app to use the following url:</p>
        <pre><?php echo $module->getUrl("endpoint/signup.php",true, true ) ?></pre>

        <br>
        <br>

        <h4>Blood Collection (Kit Submission) Survey Endpoint</h4>
        <p>Please configure the external app to use the following url:</p>
        <pre><?php echo $module->getUrl("endpoint/kitsubmission.php",true, true ) ?></pre>

        <br>
        <br>

        <h4>Twilio Callback Endpoint</h4>
        <p>Please configure the twilio phone number to callback the following url:</p>
        <pre><?php echo $module->getUrl("endpoint/signup-ivr.php",true, true ) ?></pre>
    <?php
        }
    ?>
</div>
