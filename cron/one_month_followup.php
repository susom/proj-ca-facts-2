<?php
namespace Stanford\ProjCaFacts2;
/** @var \Stanford\ProjCaFacts\ProjCaFacts2 $module */

$return = $module->sendOneMonthFollowUps();

echo "<pre>";
print_r($return);