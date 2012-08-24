<?php
// 	print_r($_POST);

	require_once "screens.php";
	require_once "utils.php";
	require_once "component.php";
	require_once "collection.php";
	
	$pluginconfig = getPluginConfig();
	$screens = getScreens($pluginconfig);
	
	// Read configuration
	$collectConfig = new CollectionConfig();
	$collectConfig->init($GLOBALS['collectionconf']);
	$collectConfig->init("$GLOBALS[rootdir]/plugins/$GLOBALS[defaultdir]/plugins.conf");
		
	// Read templates
	$template = array();
	$tmplfiles = getFiles($templatedir);
	foreach ($tmplfiles as $key => $value) {
		$template[$value] = file_get_contents("$templatedir/$value");
	}

	// Get graph list
	$hosts   = array();
	$screens = array();
	foreach ($_POST as $variable => $value) {
		$tokens = explode("_", $variable);
		if( $tokens[0]=="default") {
			$host = $tokens[1];
			$comp = $tokens[2];
			if( !array_key_exists($host, $hosts) )
				$hosts[$host] = array( array(), array());
			$hosts[$host][0][] = $comp;
		} elseif ( $tokens[0]=="host" ) {
			$host = $tokens[1];
			$comp = $tokens[2];
			if( !array_key_exists($host, $hosts) )
				$hosts[$host] = array( array(), array());
			$hosts[$host][1][] = $comp;
		} elseif ( $tokens[0]=="screen" ) {
			;
		}
	}

	// Prepare TOC
	$tocs = array();
	foreach ($hosts as $host => $graphs) {
		$hostFile = "/$GLOBALS[wwwdir]/$cgidir/$GLOBALS[defaultdir]/$host.cgi";
		$toc = "<a href=\"$hostFile\"><strong>$host</strong></a>[";
		foreach ($graphs[0] as $key => $value) {
			$toc .= "&nbsp;<a href=\"$hostFile#$value\">$value</a>";
		}
		foreach ($graphs[1] as $key => $value) {
			$toc .= "&nbsp;<a href=\"$hostFile#$value\">$value</a>";
		}
		$toc .= "&nbsp;]";
		$tocs[$host] = $toc;
	}
	
	/////////// Recreate default graph templates ////////////
	// Directories
	if(is_dir("$rootdir/$cgidir"))
		rrmdir("$rootdir/$cgidir");
	mkdir("$rootdir/$cgidir", 0777);
	mkdir("$rootdir/$cgidir/$defaultdir", 0777);
	
	// Generate orerview
	$body = "";
	foreach ($tocs as $key => $value) {
		$body .= "$value<br>";
	}
	generateFile("$GLOBALS[rootdir]/$GLOBALS[cgidir]/index.cgi", array("@BODY@"=>$body), $template["overview.tmpl"]);
	
	// Generate host's files
	$hostGraphs = array();
	foreach ($hosts as $host => $graphs) {
		foreach ($graphs[0] as $key => $value) {
			$component = new Component();
			$component->init($GLOBALS["rrddir"], $host, $value);
			$graph = $component->prepareBody($collectConfig);
			
			if( !array_key_exists($host, $hostGraphs) )
				$hostGraphs[$host] = "";
			$hostGraphs[$host] .= $graph;
		}
		foreach ($graphs[1] as $key => $value) {
		}
	}
	foreach ($hostGraphs as $host => $value) {
		generateFile("$GLOBALS[rootdir]/$cgidir/$GLOBALS[defaultdir]/$host.cgi",
				array("@BODY@"=>$value, "@TOC@"=>$tocs[$host], "@HOST@"=>$host),
				$template["host.tmpl"]);
	}	
	
	// Proceed to statisics
	echo "<a href='/$GLOBALS[wwwdir]/$cgidir/index.cgi'>Start</a>";
?>