<?php
	require_once "config.php";
	require_once "utils.php";
	require_once "screens.php";
	require_once "pluginsconfig.php";
	
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
	ksort($hosts);
	
	$pluginconfig = getPluginConfig();
	$screens = getScreens($pluginconfig);

	$group_plugins = new PluginsConfig();
	$group_plugins->init("$GLOBALS[rootdir]/plugins/host");
	$system_plugins = new PluginsConfig();
	$system_plugins->init("$GLOBALS[rootdir]/plugins/system");
	
	echo "<form action=\"generate.php\" METHOD=POST>";
	echo "<h1>Default plugins</h1>";
	echo "<table border=1>";
	echo "<tr><td></td>";
	foreach ($hosts as $hK => $hV)
		echo "<td>$hK</td>";
// 	foreach ($screens as $hK => $hV)
// 		echo "<td>$hV[0]</td>";
	echo "</tr>";
	
	ksort($comps);
	foreach ($comps as $key => $value) {
		echo "<tr><td>$key</td>";
		foreach ($hosts as $hK => $hV) {
			if( array_key_exists($key, $hosts[$hK]) ) {
				$checked = "";
				$checked = "checked";
				echo "<td><input type='checkbox' name='default_${hK}_$key' $checked/></td>\n";
			} else {
				echo "<td>disable</td>\n";
			}
		}
		echo "</tr>";
	}
	echo "<tr><td colspan=".(count($hosts)+1)."><strong>Host plugins</strong></td></tr>";
	foreach ($group_plugins->getPlugins() as $key => $value) {
		echo "<tr><td>$key</td>";
		foreach ($hosts as $hK => $hV) {
			$checked = "checked";
			echo "<td><input type='checkbox' name='host_${hK}_$key' $checked/></td>";
		}
		echo "</tr>";
	}
	echo "</table>";
	
	echo "<h1>System plugins</h1>\n";
	echo "<table border=1><tr><td></td>";
	foreach ($screens as $hK => $hV)
		echo "<td>$hV[0]</td>";
	echo "</tr>";
	foreach ($system_plugins->getPlugins() as $key => $value) {
		echo "<tr><td>$key</td>";
		foreach ($screens as $hK => $hV) {
			$checked = "checked";
			echo "<td><input type='checkbox' name='screen_${hK}_$key' $checked/></td>";
		}
	}
	echo "</table>";
	echo "<INPUT TYPE=SUBMIT></form>";
?>
