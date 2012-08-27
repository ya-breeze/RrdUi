<?php 
require_once "config.php";

function getDirectories( $path ){ 
    $result = array();

    $ignore = array( 'cgi-bin', '.', '..' ); 
    // Directories to ignore when listing output. Many hosts 
    // will deny PHP access to the cgi-bin. 

    $dh = @opendir( $path );
    if( $dh==FALSE )
    	die("Unable opendir $path"); 
    // Open the directory to the handle $dh 
     
    while( false !== ( $file = readdir( $dh ) ) ){ 
    // Loop through the directory 
     
        if( !in_array( $file, $ignore ) && is_dir( "$path/$file" ) ){ 
        // Check that this file is not to be ignored 
            $result[] = $file;
        } 
    } 
     
    closedir( $dh ); 
    // Close the directory handle 
    return $result;
} 

function getFiles( $path ){ 
    $result = array();

    $dh = @opendir( $path ); 
    if( $dh==FALSE )
    	die("Unable opendir $path"); 
    while( false !== ( $file = readdir( $dh ) ) ){ 
    	if( !is_dir( "$path/$file" ) ){
            $result[] = $file;
        } 
    } 
     
    closedir( $dh ); 
    return $result;
} 

/// recursively remove a directory
function rrmdir($dir) {
	foreach(glob($dir . '/*') as $file) {
		if(is_dir($file))
			rrmdir($file);
		else
			unlink($file);
	}
	rmdir($dir);
}

function getNextColor($idx) {
	if( !is_int($idx) && !is_string($idx) )
		$idx = 0;
	if( array_key_exists($idx, $GLOBALS['colors']) )
		return $GLOBALS['colors'][$idx];
	$color = sprintf("%1$02X%2$02X%3$02X", rand(0, 255), rand(0, 255), rand(0, 255));
	return $color;
}

function getAllRrd() {
	$hosts = array();
// 	$comps = array();
	$dirs = getDirectories($GLOBALS['rrddir']);
	foreach ($dirs as $k => $v) {
		$components = array();
		$componentDirs = getDirectories("$GLOBALS[rrddir]/$v");
		foreach ($componentDirs as $kC => $vC) {
			$component = getFiles("$GLOBALS[rrddir]/$v/$vC");
			$components[$vC] = $component;
// 			$comps[$vC] = "";
		}
		$hosts[$v] = $components;
	}
	return $hosts;
}

function generateFile($outputName, $replace, $template) {
	$fh = fopen($outputName, 'w') or die ("can't write file '$outputName'");

	foreach($replace as $k => $v) {
		$template = str_replace($k, $v, $template);
	}
	
	fwrite($fh, $template);
	fclose($fh);
	chmod($outputName, 0775);
}

function getHostPlugins() {
	$groupfiles = getFiles("$GLOBALS[rootdir]/plugins/host");
	$groupcomps = array();
	foreach ($groupfiles as $key => $value) {
		$group_plugins = parse_ini_file("$GLOBALS[rootdir]/plugins/host/$value", true);
		foreach ($group_plugins as $pkey => $pvalue) {
			$groupcomps[$pkey] = $pvalue;
		}
	}
	
	return $groupcomps;
}

function getSystemPlugins() {
	$groupfiles = getFiles("$GLOBALS[rootdir]/plugins/system");
	$groupcomps = array();
	foreach ($groupfiles as $key => $value) {
		$group_plugins = parse_ini_file("$GLOBALS[rootdir]/plugins/system/$value", true);
		foreach ($group_plugins as $pkey => $pvalue) {
			$groupcomps[$pkey] = $pvalue;
		}
	}

	return $groupcomps;
}

// function 

?>
