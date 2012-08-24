<?php
require_once "config.php";

function getPluginConfig() {
	return parse_ini_file("$GLOBALS[rootdir]/plugins.ini", true);
}


function getScreens($pluginconfig) {
	$screens = array();
	$screenNames = explode(",", $pluginconfig["screen"]["names"]);
	foreach ($screenNames as $sK => $sV) {
		$sV = trim($sV);
		$screens[$sV] = array($pluginconfig["screen"][$sV."_title"], explode(",", $pluginconfig["screen"][$sV."_graphs"]));
	}
	
	return $screens;
}
?>
