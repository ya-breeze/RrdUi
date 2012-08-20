<?php 

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

?>
