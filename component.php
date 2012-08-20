<?php
require_once "utils.php";

class Component {
	var $items = array();
	var $name = "<unknown>";
	function init($root, $name) {
		$this->items = getFiles("$root/$name");
	}
}
?>
