<?php
function fixEnc( $string ) {
	$string = iconv("CP1251", "UTF-8//TRANSLIT", charset_x_win( $string ) );
	return $string;
}
function cleanTmp() {
	foreach ( ListFiles( dirname(__FILE__) . '/tmp/' ) as $key=>$file){
		unlink( $file );
	}
}
function dropNow() {

	set_time_limit(0);
	error_reporting(E_ALL);
	ini_set("max_execution_time", "3000000000");
	$options = get_option('drop_drop_options');
	ini_set('memory_limit','128M');
	ini_set('output_buffering', 0);
	ini_set('implicit_flush', 1);
	try { while( @ob_end_flush() ); } catch( Exception $e ) {}
	ob_start();
	
	$uploader = new DropboxUploader($options['drop_drop_email'], $options['drop_drop_pwd']);
	$uploader->setCaCertificateFile(dirname(__FILE__) . '/certificate.cer');
	
	$rem_dir = trim( $options['drop_drop_rem_dir'], "/\\" );
	$loc_dir = rtrim( $options['drop_drop_loc_dir'], "/\\" );
	$loc_dir = charset_x_win( $loc_dir );
	
	if ( ListFiles($loc_dir) != FALSE ) {
		foreach ( ListFiles( $loc_dir ) as $key=>$file){
			
			$file = str_replace( '\\', '/', $file );
			$file_base = str_replace( dirname( $file ) . '/', '', $file );
			$file_new_base = fixEnc( $file_base );
			$i_dir = substr( $file, 0, strlen( $file ) - strlen( $file_base ) ); 
			//$i_dir = str_replace( $file_base, '', $file );
			$i_dir = substr( $i_dir, strlen( $loc_dir ), strlen( $i_dir ) );
			$temp_file = dirname(__FILE__) . '/tmp/' . $file_new_base;
			$temp_name_ext = $file_new_base;

			if( strlen( $file_new_base ) > 200 ) {
				$info = pathinfo($file_new_base);
				$file_name = substr( $file_new_base, 0, strlen( $file_new_base ) - strlen( $info['extension'] ) );
				$file_name = fixEnc( substr( $file_name, 0, 179 ) ) . "[...]";
				$temp_file = dirname( $temp_file ) . '/' . $file_name . '.' . $info['extension'];
				$temp_name_ext = $file_name . '.' . $info['extension'];
			}

			full_copy( $file, $temp_file );
			$up_dir = $rem_dir . fixEnc( $i_dir );
			echo '<span style="color: green">UPLOADING: </span><code>' . fixEnc( $i_dir ) . $temp_name_ext . '</code> <span style="color: green">...</span>';
			ob_flush(); flush();
			$uploader -> upload( $temp_file, $up_dir );
			unlink( $temp_file );
			echo '<span style="color: green"> DONE</span><br />';
			ob_flush(); flush();
		} 
		echo '<br /><span style="color: green"><strong>All files have been uploaded successfully!</strong></span><br /><br />';
		cleanTmp();
	}
}

function full_copy( $source, $target ) {
	if ( is_dir( $source ) ) {
		@mkdir( $target );
		$d = dir( $source );
		while ( FALSE !== ( $entry = $d->read() ) ) {
			if ( $entry == '.' || $entry == '..' ) {
				continue;
			}
			$Entry = $source . '/' . $entry; 
			if ( is_dir( $Entry ) ) {
				full_copy( $Entry, $target . '/' . $entry );
				continue;
			}
			copy( $Entry, $target . '/' . $entry );
		}
 
		$d->close();
	} else {
		copy( $source, $target );
	}
}

function ListFiles($dir) {
	if( is_dir( $dir ) ) { 
		if($dh = opendir($dir)) {

			$files = Array();
			$inner_files = Array();

			while($file = readdir($dh)) {
				if($file != "." && $file != ".." && $file[0] != '.') {
					if(is_dir($dir . "/" . $file)) {
						$inner_files = ListFiles($dir . "/" . $file);
						if(is_array($inner_files)) $files = array_merge($files, $inner_files); 
					} else {
						array_push($files, $dir . "/" . $file);
					}
				}
			}

			closedir($dh);
			return $files;
		}
	} else {
		echo '<p style="color:red"><strong>Error message: No such directory.</strong></p>';
		return FALSE;
	}
}

?>