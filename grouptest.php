<?php
	require_once "config.php";
	require_once "utils.php";
	
	$hosts = array();
	$dirs = getDirectories($GLOBALS['rrddir']);
	foreach ($dirs as $k => $v) {
		$components = array();
		$componentDirs = getDirectories("$GLOBALS[rrddir]/$v");
		foreach ($componentDirs as $kC => $vC) {
			$component = getFiles("$GLOBALS[rrddir]/$v/$vC");
			$components[$vC] = $component;
		}
		$hosts[$v] = $components;
	}

// 	echo "<h1>Available files</h1>";
// 	foreach ($hosts as $hK => $hV) {
// 		foreach ($hV as $cK => $cV) {
// 			foreach ($cV as $iK => $iV) {
// 				echo "$hK/$cK/$iV<br>";
// 			}
// 		}
// 	}
	
	$group_plugins = parse_ini_file("$GLOBALS[rootdir]/group_plugins.ini", true);
// 	print_r($group_plugins); echo "<br>";
	
	foreach($group_plugins as $pName => $plugin) {
		echo "$pName<br>";
		$result = "";
		
		///// Get files to plugin
		// parse DEF's
		$defs = array();
		if( !array_key_exists("DEF", $plugin) )
			die("There is no DEF params in $pName");
		foreach ($plugin["DEF"] as $key => $value) {
			$tokens = explode(":", $value);
			if( count($tokens)!=4 )
				die("Wrong DEF - there should be 4 items: '$value'");
			
			$filemasks = explode("/", $tokens[1]);
			if( count($filemasks)!=3 )
				die("Wrong DEF - there should be 3 path elements: '$value'");
				
// 			$defs[] = array($tokens[0], $filemasks, $tokens[2], $tokens[3]);
			$defs[] = array($tokens[0], $tokens[1], $tokens[2], $tokens[3]);
		}
		
		// matching files against DEFs
// 		$files = array();
		$variables = array();
		foreach ($hosts as $hK => $hV) {
			foreach ($hV as $cK => $cV) {
				foreach ($cV as $iK => $iV) {
					// matching each DEF
					foreach ($defs as $defK => $defV) {
						$file = "$hK/$cK/$iV";
						$def  = str_replace("/", "\/", $defV[1]);
						$def  = "/$def.rrd/";
						if( preg_match($def, $file, $matches) ) {
							$varName = $defV[0];
							foreach($matches as $mK => $mV)
								$varName = str_replace("\$$mK", $mV, $varName);
							$variables[$varName] = "$hK/$cK/$iV:$defV[2]:$defV[3]";						
						}
					}
				}
			}
		}
// 		print_r($variables);

		////// Processing LINE
		$draws = array();
		if( array_key_exists("DRAW", $plugin) ) {
			foreach ($plugin["DRAW"] as $key => $value) {
				$tokens = explode(":", $value);
				if( count($tokens)!=3 )
					die("Wrong DRAW - there should be 3 items: '$value'");
				foreach ($variables as $v => $vValue) {
					$varPattern = str_replace("/", "\/", $tokens[1]);
					$varPattern = "/$varPattern/";
					if( preg_match($varPattern, $v, $matches) ) {
						$title = $tokens[2];
						foreach($matches as $mK => $mV)
							$title = str_replace("\$$mK", $mV, $title);
						$draws[] = array($tokens[0], $v, $title);
					}
				}
			}
		}
		
		////// Prepare result
		$result .= "#!/usr/bin/rrdcgi
		<RRD::GRAPH
        	--imginfo '<IMG SRC=/$GLOBALS[wwwdir]/images/%s WIDTH=%lu HEIGHT=%lu >'
			$GLOBALS[rootdir]/images/$pName.png
			-w 800 -h200
			-s end-<RRD::CV::PATH RRD_TIME>hour
			-t \"Requests per second\"
			-v \"y axis title\"
			-l 0\n";
		foreach ($variables as $vK => $vV) {
			$result .= "DEF:$vK=$GLOBALS[rrddir]/$vV\n";
		}
		$idx = 0;
		foreach ($draws as $dK => $dV) {
			$line = "$dV[0]:$dV[1]";
			if( strpos("#", $dV[1])==0 ) {
				$line .= "#" . getNextColor($idx);
				$idx += 1;
			}
			if( !empty($dV[2]) )
				$line .= ":$dV[2]";
			$result .= "$line\n";
		}
		$result .= ">";

		$fname = "$GLOBALS[rootdir]/$GLOBALS[cgidir]/$pName.cgi";
		$fh = fopen($fname, 'w') or die ("can't write file '$fname'");
		fwrite($fh, $result);
		fclose($fh);
		chmod($fname, 0755);

		echo "<a href='$cgidir/$pName.cgi'>$pName</a><br>";
		echo "$result\n";
	}
?>
