<?php
//setup configs
$urllist = "./urllist.txt";
$phantom = "/Users/tim/Documents/devapps/phantomnslimer/phantomjs-1.9.1-macosx/bin/phantomjs";
$before_path = "./before";
$after_path = "./after";
$compare_path = "./compare";
$raster = "./rasterize.js";

//constants
define("BEFORE",     "before");
define("AFTER",     "after");

//are we doing before or after (or comparing)
$before = false;
$after = false;	
$compare = false;
$help = false;
$debug = false;

//f = filepath
//p = phantom path
//l = beforepath
//n = afterpath
//d = debug
$options = getopt("bachf::p::l::n::");

//print_r($options);
if (!is_array($options) || empty($options) ) { 
     print "There was a problem reading in the options.\n\n"; 
	 do_help();
     exit(1); 
 } 
 if(array_key_exists('h',$options)){
	 do_help();
	 exit(0);
 }
 if(array_key_exists('d',$options)){
	 $debug = TRUE;
 }
 //override variables
 if(array_key_exists('f',$options)){
	 $urllist = $options['f'];
 }
 if(array_key_exists('p',$options)){
	 $phantom = $options['p'];
 }
 if(array_key_exists('l',$options)){
	 $before_path = $options['l'];
 }
 if(array_key_exists('n',$options)){
	 $after_path = $options['n'];
 }

//override defaults with command line args, specifying action
if(array_key_exists('b',$options)){
	$before = TRUE;
}
if(array_key_exists('a',$options)){
	$after = TRUE;
}
if(array_key_exists('c',$options)){
	$compare = TRUE;
}

//do the do as specified
if($before){
	do_capture(BEFORE);
	
}
if($after){
	do_capture(AFTER);
	
	
}
if($compare){
	do_compare();
}


function do_help(){
	echo "imgsite -bachfp\n";
	echo "-f=\"test.txt\" reads from the file test.txt\n";
	echo "-p=\"/path/to/phantom\" uses that phantom binary\n";
	
}
//capture urls to images
function do_capture($to = NULL){
	global $debug;
	global $before_path;
	global $after_path;
	if($to == NULL){
		echo "must specify capture mode";
		exit(-1);
	}
	//its either before or after, assume before
	//check if to is after
	//also make sure directories exist
	$path = $before_path;
	if(!is_dir($before_path)){
		mkdir($before_path);
	}
	if($to === AFTER){
		if(!is_dir($after_path)){
			mkdir($after_path);
		}
		$path = $after_path;
	}
	$urls = get_urls();
	
	echo "found :".count($urls)." urls\n";
	
	foreach ($urls as $url){
		echo "capturing $url\n";
		$url = str_replace(array("\n", "\r"), '', $url);
		cap($url,$path."/".md5($url).".jpg");
		sleep(10);
	}
}

//compare images and report changes
function do_compare($before = BEFORE,$after = AFTER){
	global $debug;
	global $before_path;
	global $after_path;
	global $compare_path;
	if(!is_dir($compare_path)){
		mkdir($compare_path);
	}
	echo "comparing..\n";
	$urls = get_urls();
	foreach ($urls as $url){
		$url = str_replace(array("\n", "\r"), '', $url);
		$bi = $before_path."/".md5($url).".jpg";
		$ai = $after_path."/".md5($url).".jpg";
		$report = $compare_path."/".md5($url).".txt";
		$ci = $compare_path."/".md5($url).".jpg";
		img_compare($bi,$ai,$report,$ci);
	}
	
}

//our urls to test against
function get_urls(){
	global $urllist;
	$list = file($urllist);
	//print_r($list);
	return $list;
}

//capture an image of page $url to file $path_to_file
function cap($url,$path_to_file){
	global $phantom;
	global $raster;
		//echo "capturing $url to $path_to_file\n";
		$cmd = "$phantom {$raster} $url $path_to_file";
		echo $cmd;
		$s =  cmd_exec($cmd,&$code);
		//echo $s;
		//echo $code;
}

//run a barrage of crap to compare before and after
function img_compare($before,$after,$reportfile,$ci){
	echo $before."\n";
	echo $after."\n";
	$write = false;
	$report = "# Report \n";
	//size
	$bs = filesize($before);
	$as = filesize($after);
	$report.= "## filesize\n";
	if($as != $bs){
		$diff = $as - $bs;
		$report.=" file is $diff bytes different after\n\n";
		$write = true;
	}
	
	//md5
	if(md5_file($before) != md5_file($after)){
		$report.="## md5\n hashes of before and after are different\n\n";
	}
	
	$report.="## IMAGE PROCESSING\n";
	//try some GD stuff
	$img = imagecreatefromjpeg($before); 
    $img2 = imagecreatefromjpeg($after); 
	   $w = imagesx($img); 
	   $h = imagesy($img); 

	   $w2 = imagesx($img2); 
	   $h2 = imagesy($img2); 
   	   $write = true;
	   if($w != $w2 || $h!=$h2){
		   $report.="image dimensions are different $w x $h versus $w2 x $h2\n";
	   }else{
	   		$report.="image dimensions are the same $w x $h \n";
	   }
   
   
   	   //something to write different pixels to
	   $cim = @imagecreatetruecolor($w, $h)
	   or die('Cannot Initialize new GD image stream');
   
   	   $totalpix = 0;
	   $diffpix = 0;
	   
	   //whip through the pixels, compare and write differences
	   //to $cim
	   for($y=0;$y<$h;$y++) { 
	      for($x=0;$x<$w;$x++) { 
	         $rgb = imagecolorat($img, $x, $y); 
	         $r = ($rgb >> 16) & 0xFF; 
	         $g = ($rgb >> 8) & 0xFF; 
	         $b = $rgb & 0xFF;    
			 $pixelcolor =  "#".str_repeat("0",2-strlen(dechex($r))).dechex($r). 
	str_repeat("0",2-strlen(dechex($g))).dechex($g). 
	str_repeat("0",2-strlen(dechex($b))).dechex($b);
			 
			 $rgb2 = imagecolorat($img2, $x, $y); 
	         $r2 = ($rgb2 >> 16) & 0xFF; 
	         $g2 = ($rgb2 >> 8) & 0xFF; 
	         $b2 = $rgb2 & 0xFF;    
			 $pixelcolor2 =  "#".str_repeat("0",2-strlen(dechex($r2))).dechex($r2). 
	str_repeat("0",2-strlen(dechex($g2))).dechex($g2). 
	str_repeat("0",2-strlen(dechex($b2))).dechex($b2);
			 if($pixelcolor != $pixelcolor2){
				 $diffpix++;
				 //write to cim
				 imagesetpixel($cim, $x,$y, $rgb2);
				 
				 
			 }
			 $totalpix++;
	         //echo $pixelcolor.","; 
	      } 

			  
	   } 
      $report.="total pixels = $totalpix\n";
	  $report.="pixels different = $diffpix\n";
	  $diff = ($diffpix/$totalpix)*100;
	  $report.="percentage diff = ".round($diff,2); //might be good to get a %ag threshold for this
	  
	  //write cim to jpeg, path $ci
	  $report.="\ndifferences shown in ![diffs]($ci \"Page Diffs\")\n";
	  //put url in file - only write if X percent different ?
	  imagejpeg($cim, $ci);

	  // Free up memory
	  imagedestroy($cim);
	
	  //some kind of magic merge with before really light here?
	
	//image magic test/diff
	//if (!extension_loaded('imagick')){
	//	   echo 'imagick not installed\n';
	//}else{
		//do tests
	//}
	
	if($write){
		file_put_contents($reportfile, $report);
	}
}

//yoinked from php.net
function cmd_exec($cmd,&$code) {
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w") // stderr is a file to write to
        );
        
        $pipes= array();
        $process = proc_open($cmd, $descriptorspec, $pipes);
        
        $output= "";
        
        if (!is_resource($process)) return false;
        
        #close child's input imidiately
        fclose($pipes[0]);
        
        stream_set_blocking($pipes[1],false);
        stream_set_blocking($pipes[2],false);
        
        $todo= array($pipes[1],$pipes[2]);
        
        while( true ) {
            $read= array(); 
            if( !feof($pipes[1]) ) $read[]= $pipes[1];
            if( !feof($pipes[2]) ) $read[]= $pipes[2];
            
            if (!$read) break;
            
            $ready= stream_select($read, $write=NULL, $ex= NULL, 2);
            
            if ($ready === false) {
                break; #should never happen - something died
            }
            
            foreach ($read as $r) {
                $s= fread($r,1024);
                $output.= $s;
            }
        }
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $code= proc_close($process);
        
        return $output;
    }
?>