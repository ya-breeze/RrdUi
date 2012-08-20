<a href="?update">Update default graphs</a>

<?php
	require_once "config.php";
	require_once "utils.php";
	require_once "component.php";
	
	if ( isset($_GET["update"]) ) {
		echo "<h2>Available files</h2>";
		$dirs = getDirectories($rrddir);
		$hosts = array();
		foreach ($dirs as $k => $v) {
			echo "<a href='$cgidir/$defaultdir/$v'>$v</a><br>";
			$components = array();
			$componentDirs = getDirectories("$rrddir/$v");
			foreach ($componentDirs as $kC => $vC) {
				echo "----<a href='?component=$cgidir/$defaultdir/$v-$vC'>$vC</a><br>";
				$component = new Component();
				$component->init("$rrddir/$v", $vC);
				foreach ($component->items as $kI => $vI) {
					echo "--------$vI<br>";
				}
				$components[$vC] = $component;
			}
			$hosts[$v] = $components;
			echo count($component);
		}
		
		/////////// Recreate default graph templates ////////////
		// Directories
		if(is_dir("$cgidir/$defaultdir"))
			rrmdir("$cgidir/$defaultdir");
		mkdir("$cgidir/$defaultdir", 0777);
		
		// Read templates
		$template = array();
		$tmplfiles = getFiles($templatedir);
		foreach ($tmplfiles as $key => $value) {
			$template[$value] = file_get_contents("$templatedir/$value");
		}	

		// Write files
		foreach ($hosts as $k => $v) {
			mkdir("$cgidir/$defaultdir/$k", 0777);
			foreach($v as $kC => $vC) {
				$fname = "$cgidir/$defaultdir/$k/$kC.cgi";
				$fh = fopen($fname, 'w') or die ("can't write file '$fname'");
				fwrite($fh, "");
				fclose($fh);
				
				chmod($fname, 0555);
			}
		}
	}
?>
