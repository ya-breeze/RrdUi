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
	
	foreach($group_plugins as $pName => $plugin) {
// 		echo "$pName<br>";
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
			$defs[] = array(trim($tokens[0]), trim($tokens[1]), trim($tokens[2]), trim($tokens[3]));
		}
		
		// matching files against DEFs
		$rawVariables = array();
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
							$rawVariables[$varName] = "$hK/$cK/$iV:$defV[2]:$defV[3]";						
							$variables[] = $varName;						
						}
					}
				}
			}
		}
// 		print_r($variables);

		////// Processing FUNC
		$funcs = array();
		if( array_key_exists("FUNC", $plugin) ) {
			foreach ($plugin["FUNC"] as $key => $value) {
				$tokens = explode(":", $value);
				if( count($tokens)!=3 )
					die("Wrong FUNC - there should be 3 items: '$value'");
				
				$outputVariable = "";
				$matchVariables = array();
				$pattern = str_replace("/", "\/", trim($tokens[2]));
				$pattern = "/$pattern/";
				foreach ($variables as $v) {
					if( preg_match($pattern, $v, $matches) ) {
						// Build variable name based on first matched variable
						if( empty($outputVariable) ) {
							$outputVariable = trim($tokens[0]);
							foreach($matches as $mK => $mV)
								$outputVariable = str_replace("\$$mK", $mV, $outputVariable);
							echo "$outputVariable\n";
						}
						$matchVariables[] = $v;
					}
				}
				$funcs[] = array($outputVariable, trim($tokens[1]), $matchVariables);
				$variables[] = $outputVariable;						
			}
		}
		print_r($funcs);
		
		////// Processing DRAW
		$draws = array();
		if( array_key_exists("DRAW", $plugin) ) {
			foreach ($plugin["DRAW"] as $key => $value) {
				$tokens = explode(":", $value);
				if( count($tokens)!=3 )
					die("Wrong DRAW - there should be 3 items: '$value'");
				foreach ($variables as $v) {
					$varPattern = str_replace("/", "\/", trim($tokens[1]));
					$varPattern = "/$varPattern/";
					if( preg_match($varPattern, $v, $matches) ) {
						$title = trim($tokens[2]);
						foreach($matches as $mK => $mV)
							$title = str_replace("\$$mK", $mV, $title);
						$draws[] = array(trim($tokens[0]), $v, $title);
					}
				}
			}
		}
		
		////// Prepare result
		$result .= "#!/usr/bin/rrdcgi
		<RRD::GOODFOR 60>
		
			<FORM>
		    <SELECT NAME=\"RRD_TIME\">	
			<OPTION SELECTED=\"true\" value=1>1 hour</OPTION>
			<OPTION value=2>2 hours</OPTION>
			<OPTION value=3>3 hours</OPTION>
			<OPTION value=6>6 hours</OPTION>
			<OPTION value=24>1 day</OPTION>
			<OPTION value=72>3 day</OPTION>
			<OPTION value=168>1 week</OPTION>
		    </SELECT>
            <INPUT TYPE=SUBMIT>
		</FORM>	
		
		<RRD::GRAPH
        	--imginfo '<IMG SRC=/$GLOBALS[wwwdir]/images/%s WIDTH=%lu HEIGHT=%lu >'
			$GLOBALS[rootdir]/images/$pName.png
			-w 800 -h200
			-s end-<RRD::CV::PATH RRD_TIME>hour
			-t \"Requests per second\"
			-v \"y axis title\"
			-l 0\n";
		
		// Adding DEF
		foreach ($rawVariables as $vK => $vV) {
			$result .= "DEF:$vK=$GLOBALS[rrddir]/$vV\n";
		}
		
		// Adding functions
		foreach ($funcs as $func) {
			if( $func[1]=="SUM" ) {
				if( count($func[2])<2 )
					die("Unable sum less than two variables");
				$cdef = array_pop($func[2]) . "," . array_pop($func[2]) . ",+";
				foreach ($func[2] as $input) {
					$cdef .= "," . $input . ",+";
				}
				$result .= "CDEF:$func[0]=$cdef\n";
			}
		}
		
		
		// Getting maximum title length
		$maxTitleLength = 0;
		foreach ($draws as $dK => $dV) {
			if( !empty($dV[2]) )
				$maxTitleLength = max($maxTitleLength, strlen($dV[2]));
			else
				$maxTitleLength = max($maxTitleLength, strlen($dV[1]));
		}
		$align = sprintf("%1\$$maxTitleLength"."s", " ");
		$result .= "COMMENT:\"$align"."          Cur\:          Min\:          Max\:          Avg\:\\n\"\n";
		
		$idx = 0;
		// There should be a AREA or LINE before STACK
		$canStack = false;
		foreach ($draws as $dK => $dV) {
			$line = "";
			if( $dV[0]=="STACK" && $canStack==false )
				$line .= "AREA";
			else
				$line .= $dV[0];
			if( $line=="AREA" || !(strpos("LINE", $line)===false) )
				$canStack = true;
			
			$line .= ":$dV[1]";
			
			if( strpos("#", $dV[1])==0 ) {
				$line .= "#" . getNextColor($idx);
				$idx += 1;
			}
			$alignChar = 0;
			if( !empty($dV[2]) ) {
				$line .= ":\"$dV[2]\"";
				$alignChar = $maxTitleLength - strlen($dV[2]); 
			} else {
				$line .= ":\"$dV[1]\"";
				$alignChar = $maxTitleLength - strlen($dV[1]); 
			}

			$result .= "$line\n";
			
			// Adding some values
			$align = sprintf("%1\$$alignChar"."s", " ");
				
			$result .= "GPRINT:$dV[1]:LAST:\"$align%10.2lf %s\"\n";
			$result .= "GPRINT:$dV[1]:MIN:\"%10.2lf %s\"\n";
			$result .= "GPRINT:$dV[1]:MAX:\"%10.2lf %s\"\n";
			$result .= "GPRINT:$dV[1]:AVERAGE:\"%10.2lf %s\\n\"\n";
		}
		$result .= ">";

		$fname = "$GLOBALS[rootdir]/$GLOBALS[cgidir]/$pName.cgi";
		$fh = fopen($fname, 'w') or die ("can't write file '$fname'");
		fwrite($fh, $result);
		fclose($fh);
		chmod($fname, 0755);

		echo "<a href='$cgidir/$pName.cgi'>$pName</a><br>";
// 		echo "$result\n";
	}
?>
