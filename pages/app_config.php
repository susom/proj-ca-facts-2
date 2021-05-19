<?php
namespace Stanford\ProjCaFacts2;
/** @var \Stanford\ProjCaFacts2\ProjCaFacts2 $module */

require APP_PATH_DOCROOT . "ControlCenter/header.php";

$XML_AC_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTS20ACCESSCODES_2021-05-19_1435.REDCap.xml");
$XML_KO_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTS20MAINPROJECT_2021-05-19_1539.REDCap.xml");
?>
<div style='margin:20px 0;'>

	<h3>CA-FACTS 2.0 Project EM Requirements</h3>
	<p>This EM will coordinate between <b>2 REDcap projects</b> to intake and track conversions of direct mail invitations for participation in home COVID antibody testing.</p>
	<p>Each of the 2 projects will need to have the <b>CA Facts 2.0 Project EM</b> installed.</p>
	<p>One of each of the following modes should be set for the 2 respective project's' EM configurations:</p>
	<ul>
		<li>Access Code DB Project - <?php echo "<a href='$XML_AC_PROJECT_TEMPLATE'>XML project creation template</a>" ?></li>
		<li>Main Project - <?php echo "<a href='$XML_KO_PROJECT_TEMPLATE'>XML project creation template</a>" ?></li>
	</ul>

	<br>
	<br>
	
	<h4>Enabled Projects (2 Required)</h4>
	<div>
		<?php echo $module->displayEnabledProjects( array("access_code_db" => $XML_AC_PROJECT_TEMPLATE, "kit_order" => $XML_KO_PROJECT_TEMPLATE)  ) ?>
	</div>
	
</div>

