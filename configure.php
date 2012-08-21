<a href="?update">Update default graphs</a>
<form action="?generate">
<?php
	require_once "config.php";
	require_once "utils.php";
	require_once "component.php";
	require_once "collection.php";
	
	if ( isset($_GET["update"]) ) {
		echo "<h2>Available files</h2>";
		$dirs = getDirectories($rrddir);
		$hosts = array();
		foreach ($dirs as $k => $v) {
			echo "<a href='$cgidir/$defaultdir/$v.cgi'>$v</a><br>";
			$components = array();
			$componentDirs = getDirectories("$rrddir/$v");
			foreach ($componentDirs as $kC => $vC) {
				$component = new Component();
				$component->init($rrddir, $v, $vC);
				
				$hint = "";
				foreach ($component->items as $kI => $vI) {
					$hint .= "$vI\n";
				}
				echo "----<input type='checkbox' name='$v-$vC' checked/><a href='$cgidir/$defaultdir/$v-$vC.cgi?RRD_TIME=1' title='$hint'>$vC</a><br>";
				$components[$vC] = $component;
			}
			$hosts[$v] = $components;
		}
		
		/////////// Recreate default graph templates ////////////
		// Directories
		if(is_dir("$rootdir/$cgidir/$defaultdir"))
			rrmdir("$rootdir/$cgidir/$defaultdir");
		mkdir("$rootdir/$cgidir/$defaultdir", 0777);
		
		// Read configuration
		$collectConfig = new CollectionConfig();
		$collectConfig->init($GLOBALS['collectionconf']);
		$collectConfig->init("$GLOBALS[rootdir]/plugins.conf");
		
		// Read templates
		$template = array();
		$tmplfiles = getFiles($templatedir);
		foreach ($tmplfiles as $key => $value) {
			$template[$value] = file_get_contents("$templatedir/$value");
		}	

		// Write component files
		foreach ($hosts as $k => $v) {
			$fHostname = "$rootdir/$cgidir/$defaultdir/$k.cgi";
			$fHost     = fopen($fHostname, 'w') or die ("can't write file '$fHostname'");
			$hostData = "";
			
			foreach($v as $kC => $vC) {
				$fname = "$rootdir/$cgidir/$defaultdir/$k-$kC.cgi";
				$fh = fopen($fname, 'w') or die ("can't write file '$fname'");
				$graph = $vC->prepareBody($collectConfig);
				$hostData .= "<h1>$kC</h1>$graph";
				
				$data = str_replace("@BODY@", $graph, $template["component.tmpl"]);
				$data = str_replace("@COMPONENT@", $kC, $data);
				
				fwrite($fh, $data);
				fclose($fh);
				chmod($fname, 0555);
			}

			$hostData = str_replace("@BODY@", $hostData, $template["host.tmpl"]);
			$hostData = str_replace("@HOST@", $k, $hostData);
				
			fwrite($fHost, $hostData);
			fclose($fHost);
			chmod($fHostname, 0555);
		}
		// Write host files
	}
?>
