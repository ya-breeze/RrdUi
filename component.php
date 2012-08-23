<?php
require_once "config.php";
require_once "utils.php";

class Component {
	var $items = array();
	var $name = "<unknown>";
	var $host = "<unknown>";
	
	var $drawStacked = FALSE;
	function init($root, $host, $name) {
		$this->name = $name;
		$this->host = $host;
		$this->items = getFiles("$root/$host/$name");
	}
	
	function prepareTypes($collectionConf) {
		$types = array();
		$typeColors = array();
		foreach ($this->items as $key => $value) {
			$fname = substr($value, 0, -4);
			$typeName = $fname;
			$instance = $fname;
			$idx = strpos($typeName, "-");
			if( !($idx===false) ) {
				$instance = substr($typeName, $idx+1);
				$typeName = substr($typeName, 0, $idx);
			}
			// append type
			if( !array_key_exists($typeName, $types) )
				$types[$typeName] = "";
// 			if( $typeName==="cpu" && $this->name=="cpu-0" )
// 					echo "$type:$instance:$DSvalue<br>";
				
			$type = $collectionConf->getType($typeName);
			if( $type && array_key_exists("DataSources", $type) ) {
				$dsList = explode(" ", $type["DataSources"]);
				foreach ($dsList as $DSkey => $DSvalue) {
			
					$name = $fname . "_" . $DSvalue;
					$itemTitle = $name;
					if( array_key_exists("DSName", $type) && array_key_exists($DSvalue, $type["DSName"]) )
						$itemTitle = $type["DSName"][$DSvalue];
					$color = "";
					if( array_key_exists("Color", $type) && array_key_exists($instance, $type["Color"]) )
						$color = $type["Color"][$instance];
					else if( array_key_exists("Color", $type) && array_key_exists($DSvalue, $type["Color"]) )
						$color = $type["Color"][$DSvalue];
					
					if( empty($color) ) {
						$idx = 0;
						if(array_key_exists($typeName, $typeColors))
							$idx = (int)$typeColors[$typeName];
						$color = getNextColor($idx);
						$typeColors[$typeName] = $idx + 1;
					}
					$draw = "LINE1";
					
					if( array_key_exists("Module", $type) && $type["Module"]=="GenericStacked" )
						if( empty($types[$typeName]) )
							$draw = "AREA";
						else
							$draw = "STACK";
					
					$types[$typeName] .= "DEF:$name=$GLOBALS[rrddir]/$this->host/$this->name/$value:$DSvalue:AVERAGE\n";
					$types[$typeName] .= "$draw:$name#$color:\"" . $itemTitle. "\" GPRINT:$name:LAST:\"    %10.2lf %s\" GPRINT:$name:MIN:\"%10.2lf %s\"  GPRINT:$name:MAX:\"%10.2lf %s\\n\"\n";
				}
			} else {
				$idx = 0;
				if(array_key_exists($typeName, $typeColors))
					$idx = $typeColors[$typeName];
				$color = getNextColor($idx);
				$typeColors[$typeName] = $idx + 1;
				$draw = "LINE1";
				if( $type["Module"]=="GenericStacked" )
					if( empty($types[$typeName]) )
						$draw = "AREA";
					else
						$draw = "STACK";
				$types[$typeName] .= "DEF:$fname=$GLOBALS[rrddir]/$this->host/$this->name/$value:value:AVERAGE\n";
				$types[$typeName] .= "$draw:$fname#$color:\"" . $instance . "\" GPRINT:$fname:LAST:\"    %10.2lf %s\" GPRINT:$fname:MIN:\"%10.2lf %s\"  GPRINT:$fname:MAX:\"%10.2lf %s\\n\"\n";
			}
		}
		
		return $types;
	}
	
	function prepareBody($collectionConf) {
		$result = "";
		$types = $this->prepareTypes($collectionConf);
		foreach ($types as $key => $value) {
			$result .= 			
						"<H2>$key</H2>
						<RRD::GRAPH
							--imginfo '<IMG SRC=/$GLOBALS[wwwdir]/images/%s WIDTH=%lu HEIGHT=%lu >'
							$GLOBALS[rootdir]/images/$this->host-$this->name-$key.png
						-t '$this->name'
						-v 'y axis title'
						-w 600  -h200
				 		-s end-<RRD::CV::PATH RRD_TIME>hour
						COMMENT:\"                 Cur\:           Min\:        Max\:           Avg\:\\n\"\n";
			$result .= $value;
			$result .= ">";
		}
		
		
// 		foreach ($this->items as $key => $value) {
// 			$fname = substr($value, 0, -4);
// 			$typeName = $fname;
// 			$instance = "";
// 			$idx = strpos($typeName, "-");
// 			if( !($idx===false) ) {
// 				$instance = substr($typeName, $idx+1);
// 				$typeName = substr($typeName, 0, $idx);
// 			}	
// 			$type = $collectionConf->getType($typeName);
// // 			echo "!!! $typeName - $type @@@";
// 			if( $type ) {
// 				$dsList = explode(" ", $type["DataSources"]);
// 				echo "!!!! $type[DataSources]";
// 				foreach ($dsList as $DSkey => $DSvalue) {
// 					$name = $fname . "_" . $DSvalue;
// 		 			$result .= "DEF:$name=$GLOBALS[rrddir]/$this->host/$this->name/$value:$DSvalue:AVERAGE\n";
// 					$result .= "LINE1:$name#0000FF:\"" . $fname. "\" GPRINT:$name:LAST:\"    %10.2lf %s\" GPRINT:$name:MIN:\"%10.2lf %s\"  GPRINT:$name:MAX:\"%10.2lf %s\\n\"\n";
// 				}
// 			} else {
// 	 			$result .= "DEF:$fname=$GLOBALS[rrddir]/$this->host/$this->name/$value:value:AVERAGE\n";
// 				$result .= "LINE1:$fname#0000FF:\"" . $fname. "\" GPRINT:$fname:LAST:\"    %10.2lf %s\" GPRINT:$fname:MIN:\"%10.2lf %s\"  GPRINT:$fname:MAX:\"%10.2lf %s\\n\"\n";
// 			}
// 		}

		return $result;
	}
}
?>
