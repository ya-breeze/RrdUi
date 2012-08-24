<?php
	require_once "config.php";
	require_once "utils.php";
	require_once "screens.php";
	
	$hosts = array();
	$comps = array();
	$dirs = getDirectories($GLOBALS['rrddir']);
	foreach ($dirs as $k => $v) {
		$components = array();
		$componentDirs = getDirectories("$GLOBALS[rrddir]/$v");
		foreach ($componentDirs as $kC => $vC) {
			$component = getFiles("$GLOBALS[rrddir]/$v/$vC");
			$components[$vC] = $component;
			$comps[$vC] = "";
		}
		$hosts[$v] = $components;
	}
	
	$groupfiles = getFiles("$GLOBALS[rootdir]/plugins/host");
	$groupcomps = array(); 
	foreach ($groupfiles as $key => $value) {
		$group_plugins = parse_ini_file("$GLOBALS[rootdir]/plugins/host/$value", true);
		foreach ($group_plugins as $pkey => $pvalue) {
			$groupcomps[] = $pkey;
		}
	}
	
	$systemfiles = getFiles("$GLOBALS[rootdir]/plugins/system");
	$systemcomps = array();
	foreach ($systemfiles as $key => $value) {
		$system_plugins = parse_ini_file("$GLOBALS[rootdir]/plugins/system/$value", true);
		foreach ($system_plugins as $pkey => $pvalue) {
			$systemcomps[] = $pkey;
		}
	}
	
	$pluginconfig = getPluginConfig();
	$screens = getScreens($pluginconfig);
	
	echo "<form action=\"generate.php\" METHOD=POST>";
	echo "<h1>Default plugins</h1>";
	echo "<table border=1>";
	echo "<tr><td></td>";
	foreach ($hosts as $hK => $hV)
		echo "<td>$hK</td>";
// 	foreach ($screens as $hK => $hV)
// 		echo "<td>$hV[0]</td>";
	echo "</tr>";
	
	foreach ($comps as $key => $value) {
		echo "<tr><td>$key</td>";
		foreach ($hosts as $hK => $hV) {
			$checked = "";
			if( array_key_exists($key, $hosts[$hK]) )
				$checked = "checked";
			echo "<td><input type='checkbox' name='default_${hK}_$key' $checked/></td>\n";
		}
// 		foreach ($screens as $hK => $hV) {
// 			$checked = "";
// 			echo "<td><input type='checkbox' name='screen_${hK}_$key' $checked/></td>";
// 		}
		echo "</tr>";
	}
	echo "<tr><td><strong>Host plugins</strong></td></tr>";
	foreach ($groupcomps as $key => $value) {
		echo "<tr><td>$value</td>";
		foreach ($hosts as $hK => $hV) {
			$checked = "checked";
			echo "<td><input type='checkbox' name='host_${hK}_$value' $checked/></td>";
		}
// 		foreach ($screens as $hK => $hV) {
// 			$checked = "";
// 			echo "<td><input type='checkbox' name='screen_${hK}_$value' $checked/></td>";
// 		}
		echo "</tr>";
	}
	echo "</table>";
	
	echo "<h1>System plugins</h1>\n";
	echo "<table border=1><tr><td></td>";
	foreach ($screens as $hK => $hV)
		echo "<td>$hV[0]</td>";
	echo "</tr>";
	foreach ($systemcomps as $key => $value) {
		echo "<tr><td>$value</td>";
		foreach ($screens as $hK => $hV) {
			$checked = "checked";
			echo "<td><input type='checkbox' name='screen_${hK}_$value' $checked/></td>";
		}
	}
	echo "</table>";
	echo "<INPUT TYPE=SUBMIT></form>";
?>
