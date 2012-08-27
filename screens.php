<?php
require_once "config.php";

function getPluginConfig() {
	$result = array();
	$result[0] = array();
	$result[1] = array();
	$filename = "$GLOBALS[rootdir]/plugins.cfg";
	$file = fopen($filename, 'r');
	if( !$file ) {
		$result[1]["main"] = array(array(), "Main screen");
		return $result;
	}

	while (($buffer = fgets($file, 65535)) !== false) {
		$buffer = trim($buffer);
		if( empty($buffer[0]) || $buffer[0]=="#" )
			continue;
		$tokens = explode(":", $buffer);
		$type=trim($tokens[0]);
		if( $type=="host" ) {
			if( count($tokens)!=3 )
				die("Host description should have 3 tokens in '$filename' - '$buffer'");
			
			$comps = explode(",", trim($tokens[2]));
			foreach ($comps as $key => $value) {
				$comps[$key] = trim($value);
			}
			$result[0][trim($tokens[1])] = $comps;
		} elseif( $type=="screen" ) {
			if( count($tokens)!=4 )
				die("Screen description should have 4 tokens in '$filename' - '$buffer'");

			$comps = explode(",", trim($tokens[3]));
			foreach ($comps as $key => $value) {
				$comps[$key] = trim($value);
			}
			$result[1][trim($tokens[1])] = array( $comps, trim($tokens[2]) );
		}
	}
	fclose($file);

// 	print_r($result);
	return $result;
}

function setPluginEnabled4Host($pluginConfig, $host, $plugin) {
	if( !array_key_exists($host, $pluginConfig[0]) )
		$pluginConfig[0][$host] = array();
	if( !in_array($plugin, $pluginConfig[0][$host]))
		$pluginConfig[0][$host][] = $plugin;
	return $pluginConfig;
}
function setPluginEnabled4System($pluginConfig, $screen, $plugin) {
	if( !array_key_exists($screen, $pluginConfig[1]) ) {
		$pluginConfig[1][$screen] = array( array(), "");
	}
	if( !in_array($plugin, $pluginConfig[1][$screen][0]))
		$pluginConfig[1][$screen][0][] = $plugin;
	return $pluginConfig;
}

function isPluginEnabled4Host($pluginConfig, $host, $plugin) {
	if( !array_key_exists($host, $pluginConfig[0]) )
		return false;
	return in_array($plugin, $pluginConfig[0][$host]);
}
function isPluginEnabled4Screen($pluginConfig, $screen, $plugin) {
	if( !array_key_exists($screen, $pluginConfig[1]) )
		return false;
	return in_array($plugin, $pluginConfig[1][$screen][0]);
}

function setPluginConfig($pluginConfig) {
	$filename = "$GLOBALS[rootdir]/plugins.cfg";
	
	$body = "";
	foreach ($pluginConfig[0] as $key => $value) {
		$body .= "host : $key : ". implode(", ", $value) . "\n";
	}
	foreach ($pluginConfig[1] as $key => $value) {
		$body .= "screen : $key : $value[1] : ". implode(", ", $value[0]) . "\n";
	}
	
	$file = fopen($filename, 'w+') or die ("can't write file '$filename'");
 	fwrite($file, $body);
 	fclose($file);
}

function getScreens($pluginconfig) {
	return $pluginconfig[1];
}

?>
