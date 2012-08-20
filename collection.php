<?php
require_once "config.php";

class CollectionConfig {
	var $types = array();
	var $width = 400;
	
	function init() {
		$file = fopen($GLOBALS['collectionconf'], 'r') or die ("can't read file '$GLOBALS[collectionconf]'");
		
		$type = "";
		$typeData = array();
		while (($buffer = fgets($file, 65535)) !== false) {
			$buffer = trim($buffer);
			if( empty($buffer[0]) || $buffer[0]=="#" )
				continue;
			
			if( substr($buffer, 0, 6)=="<Type ") {
				if( $type!="" )
					die("Wrong collection config - type inside type '$buffer'");
				$tokens = explode(" ", $buffer);
				$type = substr($tokens[1], 0, -1);
				if( $type=="" )
					die("Wrong collection config - empty type '$buffer'");
				
				$typeData = array();
			} else if( substr($buffer, 0, 6)=="</Type") {
				$this->types[$type] = $typeData;
				$type = "";
			} else if( $buffer=="GraphWidth") {
				$this->width = (int)substr($buffer, 12);
			} else if( $type!="" ) {
				$tokens = explode(" ", $buffer, 2);
				if( $tokens[0]=="DSName" || $tokens[0]=="Color" ) {
					if( !array_key_exists($tokens[0], $typeData) )
							$typeData[$tokens[0]] = array();
					$tokens2 = explode(" ", $tokens[1], 2);
					$typeData[$tokens[0]][trim($tokens2[0])] = trim($tokens2[1]);
					echo "!!!!!!! ".$type . ":".trim($tokens2[0]) ."=". trim($tokens2[1]). " !!!!!!!!!<br>";
					echo "!!! " .implode(",", $typeData[$tokens[0]])."!!!<br>";
				} else {
					$typeData[$tokens[0]] = $tokens[1];
				}
			}
		}
		fclose($file);
	}
	
	function getType($name) {
		if( array_key_exists($name, $this->types) )
			return $this->types[$name];
		return FALSE;
	}
}

?>
