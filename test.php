<?php
require_once "screens.php";

$config = getPluginConfig();
$config[0]["localhost"] = array("cpu-0", "cpu-1");
$config[1]["main"] = array( array("cpu-0", "cpu-1"), "Multifon summary" );
print_r($config);
setPluginConfig($config);

?>
