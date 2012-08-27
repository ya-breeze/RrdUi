<?php
require_once "config.php";

class PluginsConfig {
	var $types = array();
	var $width = 400;
	var $plugins = array();
	
	function init($dir) {
		$groupfiles = getFiles("$dir");
		foreach ($groupfiles as $key => $value)
			$this->append("$dir/$value");
		ksort($this->plugins);
	}
	
	
	function append($filename) {
		$file = fopen($filename, 'r') or die ("can't read file '$filename'");

		$plugin = "";
		$typeData = array();
		while (($buffer = fgets($file, 65535)) !== false) {
			$buffer = trim($buffer);
			if( empty($buffer[0]) || $buffer[0]=="#" )
				continue;
				
			if( substr($buffer, 0, 6)=="<Type ") {
				if( $plugin!="" )
					die("Wrong plugin config '$filename' - type inside type '$buffer'");
				$tokens = explode(" ", $buffer);
				$plugin = substr($tokens[1], 0, -1);
				if( $plugin=="" )
					die("Wrong plugin config '$filename' - empty type '$buffer'");

				$typeData = array();
			} else if( substr($buffer, 0, 6)=="</Type") {
				$this->plugins[$plugin] = $typeData;
				$plugin = "";
			} else if( $plugin!="" ) {
				$tokens = explode("=", $buffer, 2);
				if( count($tokens)<2 )
					die("Wrong plugin config '$filename' - wrong syntax '$buffer'");
				
				$type = trim($tokens[0]);
				$value = trim($tokens[1]);
				if( $type=="DEF" || $type=="FUNC" || $type=="CDEF" || $type="DRAW" )
					$typeData[] = array($type, $value);
			}
		}
		fclose($file);
	}

	function getPlugin($name) {
		if( array_key_exists($name, $this->plugins) )
			return $this->plugins[$name];
		return FALSE;
	}
	function getPlugins() {
		return $this->plugins;
	}
}

?>
