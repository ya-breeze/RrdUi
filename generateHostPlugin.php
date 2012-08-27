<?php
	require_once "config.php";
	require_once "utils.php";

function generateHostPlugin($pluginName, $plugin, $host) {
	$GLOBALS['host'] = $host;
	
	$hosts = getAllRrd();

		$result = "";
		$defs = array();
		$rawVariables = array();
		$variables = array();
		
		foreach($plugin as $configItem) {
			$configType = $configItem[0]; 
			$configValue = $configItem[1]; 
			if( $configType=="DEF") {
				$tokens = explode(":", $configValue);
				if( count($tokens)!=4 )
					die("Wrong DEF - there should be 4 items: $pluginName:'$configValue'");
					
				$filemasks = explode("/", $tokens[1]);
				if( count($filemasks)!=3 )
					die("Wrong DEF - there should be 3 path elements: $pluginName:'$configValue'");
				$filemasks[0] = str_replace("\$host", $GLOBALS['host'], $filemasks[0]);
				
				$defK = trim($tokens[0]);
				$filemasks = trim(implode("/", $filemasks));
				$dsName = trim($tokens[2]);
				$dsType = trim($tokens[3]);
				$defs[] = array($defK, $filemasks, $dsName, $dsType);

				// matching files against DEFs
				foreach ($hosts as $hK => $hV) {
					foreach ($hV as $cK => $cV) {
						foreach ($cV as $iK => $iV) {
							$file = "$hK/$cK/$iV";
							$def  = str_replace("/", "\/", $filemasks);
							$def  = "/$def.rrd/";
							if( preg_match($def, $file, $matches) ) {
								$varName = $defK;
								foreach($matches as $mK => $mV)
									$varName = str_replace("\$$mK", $mV, $varName);
								$rawVariables[$varName] = "$hK/$cK/$iV:$dsName:$dsType";
								$variables[] = $varName;
							}
						}
					}
				}
			} elseif ($configType=="FUNC") {
				$tokens = explode(":", $configValue);
				if( count($tokens)!=4 )
					die("Wrong FUNC - there should be 4 items: '$configValue'");
				
				$pattern = trim($tokens[2]);
				$pattern = str_replace("/", "\/", $pattern);
				$pattern = "/$pattern/";
				
				$groupby = trim($tokens[3]);
				$groupby = str_replace("/", "\/", $groupby);
				$groupby = "/$groupby/";
				
				$outputVariables = array();
				$equalParts = array();
				$matchVariables = array();
				foreach ($variables as $v) {
					if( preg_match($pattern, $v, $matches) && preg_match($groupby, $v, $groupMatches) ) {
						// If there is an item with the same $groupMatches - it's will be the same output variable
						array_shift($groupMatches);
						$glued = implode(":", $groupMatches);
						// 						echo "glued $glued\n";
						if( array_key_exists($glued, $matchVariables) ) {
							$matchVariables[$glued][] = $v;
						} else {
							// It's the new group
								
							// Build variable name based on first matched variable
							$outputVariable = trim($tokens[0]);
							array_unshift($groupMatches, $v);
							foreach($groupMatches as $mK => $mV)
								$outputVariable = str_replace("\$$mK", $mV, $outputVariable);
							$outputVariables[$glued] = $outputVariable;
							$matchVariables[$glued] = array();
							$matchVariables[$glued][] = $v;
						}
					}
				}
				foreach ($outputVariables as $glued => $variable) {
					$funcs[] = array($variable, trim($tokens[1]), $matchVariables[$glued]);
					$variables[] = $variable;
				}				
			} elseif ($configType=="CDEF") {
				$tokens = explode(":", $configValue);
				if( count($tokens)!=3 )
					die("Wrong CDEF - there should be 3 items: '$configValue'");

				// Remove spaces and trying find variables
				$is_variables = false;
				$expr = explode(",", $tokens[1]);
				foreach ($expr as $k => $v) {
					$v = trim($v);
					$expr[$k] = $v;
					if( $v[0]=="[" )
						$is_variables = true;
				}
				if( !$is_variables ) {
					$cdefs[] = array(trim($tokens[0]), implode(",", $expr));
					continue;
				}

				$groupby = trim($tokens[2]);
				$groupby = str_replace("/", "\/", $groupby);
				$groupby = "/$groupby/";

				$outputVariables = array();
				$matchVariables = array();

				foreach ($expr as $k => $exprItem) {
					if( $exprItem[0]=="[" ){
						$pattern = str_replace("/", "\/", substr($exprItem, 1, strlen($exprItem)-2));
						$pattern = "/$pattern/";
						// 						echo "pattern - $pattern\n";

						foreach ($variables as $v) {
							if( preg_match($pattern, $v) && preg_match($groupby, $v, $groupMatches) ) {
								// If there is an item with the same $groupMatches - it's will be the same output variable
								array_shift($groupMatches);
								$groupMatches = array_filter($groupMatches);
								$glued = implode(":", $groupMatches);
								// 								echo "glued $glued\n";

								if( array_key_exists($glued, $matchVariables) ) {
									$matchVariables[$glued][] = $v;
								} else {
									// It's the new group

									// Build variable name based on first matched variable
									$outputVariable = trim($tokens[0]);
									array_unshift($groupMatches, $v);
									foreach($groupMatches as $mK => $mV)
										$outputVariable = str_replace("\$$mK", $mV, $outputVariable);
									$outputVariables[$glued] = $outputVariable;
									$matchVariables[$glued] = array();
									$matchVariables[$glued][] = $v;
								}
							}
						}

					}
				}

				foreach ($outputVariables as $glued => $variable) {
					$finalExpr = array();
					foreach ($expr as $k => $exprItem) {
						if( $exprItem[0]=="[" ){
							$pattern = str_replace("/", "\/", substr($exprItem, 1, strlen($exprItem)-2));
							$pattern = "/$pattern/";
							foreach($matchVariables[$glued] as $mK => $mV) {
								if( preg_match($pattern, $mV) ) {
									$exprItem = $mV;
									unset($matchVariables[$glued][$mK]);
									break;
								}
							}
						}
						$finalExpr[] = $exprItem;
					}


					$cdefs[] = array($variable, implode(",", $finalExpr));
					$variables[] = $variable;
				}
			} elseif ($configType=="DRAW") {
				$tokens = explode(":", $configValue);
				if( count($tokens)!=3 )
					die("Wrong DRAW - there should be 3 items: '$configValue'");
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
		if( count($defs)==0 )
			die("There is no DEF params in $pluginName");

		////// Prepare result
		$result .= "<H2><a name=\"$pluginName\"/>$pluginName</H2>";
		$result .= "
		<RRD::GRAPH
        	--imginfo '<IMG SRC=/$GLOBALS[wwwdir]/images/%s WIDTH=%lu HEIGHT=%lu >'
			$GLOBALS[rootdir]/images/$pluginName.png
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
			if( $func[1]=="SUM" || $func[1]=="DIV" || $func[1]=="MUL" ) {
				$operation = "";
				if( $func[1]=="SUM" ) {
					if( count($func[2])<2 )
						die("Unable sum less than two variables");
					$operation = "+";
				} elseif( $func[1]=="DIV" ) {
					if( count($func[2])!=2 )
						die("Can perform division only on two variables");
					$operation = "/";
				} elseif( $func[1]=="MUL" ) {
					if( count($func[2])<2 )
						die("Unable multiple less than two variables");
					$operation = "*";
				}
				$cdef = array_pop($func[2]) . "," . array_pop($func[2]) . ",$operation";
				foreach ($func[2] as $input) {
					$cdef .= "," . $input . ",$operation";
				}
				$result .= "CDEF:$func[0]=$cdef\n";
			}
		}

		// Adding cdefs
		foreach ($cdefs as $cdefItem) {
			$result .= "CDEF:$cdefItem[0]=$cdefItem[1]\n";
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
		
		return $result;
}	
?>
