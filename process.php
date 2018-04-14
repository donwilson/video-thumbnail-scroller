<?php
	set_time_limit(0);
	
	define('FFMPEG_PATH', "/usr/bin/ffmpeg");
	define('MAX_NUM_SCREENS', 10);
	
	
	
	if(!isset($_REQUEST['file']) || !file_exists("./videos/". $_REQUEST['file']) || !preg_match("#^([^/]+?)\.(mp4|wmv|mpe?g|flv|mov|webm|mkv|avi|m4v|3gp)$#si", $_REQUEST['file'], $match)) {
		die("file not found");
	}
	
	$video = array(
		'file'	=> $match[0],
		'path'	=> __DIR__ ."/videos/". $match[0],
		'name'	=> $match[1],
		'ext'	=> $match[2],
		'hash'	=> md5($match[1] .".". $match[2]),
	);
	
	define('THUMB_PATH', __DIR__ ."/thumbs/". $video['hash'] ."/");
	
	if(is_dir(THUMB_PATH)) {
		foreach(glob(THUMB_PATH ."*.jpg") as $existing_thumb) {
			if(file_exists($existing_thumb)) {
				@unlink($existing_thumb);
			}
		}
	} else {
		@mkdir(THUMB_PATH, 0777);
	}
	
	// find duration
	$command = FFMPEG_PATH ." -i \"". $video['path'] ."\" 2>&1";
	
	print "<p>Running: <b>". $command ."</b></p>\n";flush();
	
	$return = exec($command, $output, $return_var);
	
	$info = array();
	
	if(!empty($output)) {
		foreach($output as $output_line) {
			if(preg_match("#^\s*Duration\:\s*([0-9\:\.]+),#si", $output_line, $match)) {
				$info['duration'] = array(
					'raw'	=> trim($match[1]),
				);
				
				if(preg_match("#^([0-9]+)\:([0-9]{2})\:([0-9\.]{2,})$#si", $info['duration']['raw'], $match2)) {
					$info['duration']['hours'] = (int)$match2[1];
					$info['duration']['minutes'] = (int)$match2[2];
					$info['duration']['seconds'] = rtrim((float)$match2[3], ".");
				}
				
				$info['duration']['total_seconds'] = 0;
				
				if(!empty($info['duration']['hours'])) {
					$info['duration']['total_seconds'] += ($info['duration']['hours'] * (60 * 60));
				}
				
				if(!empty($info['duration']['minutes'])) {
					$info['duration']['total_seconds'] += ($info['duration']['minutes'] * (60));
				}
				
				if(!empty($info['duration']['seconds'])) {
					$info['duration']['total_seconds'] += $info['duration']['seconds'];
				}
			}
			
			if(preg_match("#Video\:\s*(?:.+?)\s+([0-9]+)x([0-9]+),#si", $output_line, $match)) {
				$info['width'] = $match[1];
				$info['height'] = $match[2];
			}
			
			if(preg_match("#([0-9\.]+) tbr#si", $output_line, $match)) {
				$info['frame_rate'] = $match[1];
			}
		}
	}
	
	
	
	
	function secondsToFFMPEGTime($seconds) {
		$hours = 0;
		$minutes = 0;
		
		while($seconds > (60 * 60)) {
			$seconds -= (60 * 60);
			$hours++;
		}
		
		while($seconds > 60) {
			$seconds -= 60;
			$minutes++;
		}
		
		$dec_seconds = ($seconds - floor($seconds));
		$seconds = floor($seconds);
		
		return str_pad($hours, 2, "0", STR_PAD_LEFT) .":". str_pad($minutes, 2, "0", STR_PAD_LEFT) .":". str_pad($seconds, 2, "0", STR_PAD_LEFT) . (!empty($dec_seconds)?".". preg_replace("#^0\.#si", "", round($dec_seconds, 2)):"");
	}
	
	
	/*$info['screen_every'] = 1;   // default to every 1 second
	
	if($info['duration']['total_seconds'] > MAX_NUM_SCREENS) {
		$info['screen_every'] = floor(($info['duration']['total_seconds'] / MAX_NUM_SCREENS));
	}
	
	if(empty($info['frame_rate'])) {
		die("frame_rate not set");
	}
	
	//$command = FFMPEG_PATH ." -i \"". $video['path'] ."\" -r ". (1 / $info['screen_every']) ." -f image2 %0". strlen(MAX_NUM_SCREENS) ."d.jpg";
	$command = FFMPEG_PATH ." -i \"". $video['path'] ."\" -framerate ". ($info['frame_rate'] * $info['screen_every']) ." -f image2 %0". strlen(MAX_NUM_SCREENS) ."d.jpg";
	
	print "<p>Running: <b>". $command ."</b></p>\n";flush();
	print "<p>Building Screens every ". $info['screen_every'] ." seconds</p>\n";flush();
	
	$return = exec($command, $output, $return_var);
	
	print "<pre>". htmlentities(print_r(array(
		'info'			=> $info,
		'return'		=> $return,
		'output'		=> $output,
		'return_var'	=> $return_var,
	), true)) ."</pre>\n";*/
	
	$info['screen_times'] = array();
	
	// pad MAX_NUM_SCREENS with 1 second before for first screen and 1 second after for last screen
	if($info['duration']['total_seconds'] >= (1 + MAX_NUM_SCREENS + 1)) {
		// safe to go full MAX_NUM_SCREENS
		
		$screen_every = ((floor($info['duration']['total_seconds']) - 2) / (MAX_NUM_SCREENS - 1));
		
		// first screen
		$info['screen_times'][] = array(
			'at'	=> secondsToFFMPEGTime(1),
		);
		
		for($i = 1; $i < (MAX_NUM_SCREENS - 1); $i++) {
			$info['screen_times'][] = array(
				'at'	=> secondsToFFMPEGTime( (1 + ($screen_every * $i)) ),
			);
		}
		
		// last screen
		$info['screen_times'][] = array(
			'at'	=> secondsToFFMPEGTime( (floor($info['duration']['total_seconds']) - 1) ),
		);
		
	} elseif($info['duration']['total_seconds'] >= 3) {
		// first screen
		$info['screen_times'][] = array(
			'at'	=> secondsToFFMPEGTime(1),
		);
		
		// last screen
		$info['screen_times'][] = array(
			'at'	=> secondsToFFMPEGTime( (floor($info['duration']['total_seconds']) - 1) ),
		);
	} else {
		$info['screen_times'][] = array(
			'at'	=> secondsToFFMPEGTime(1),
		);
	}
	
	if(!empty($info['screen_times'])) {
		$screens = 0;
		
		foreach($info['screen_times'] as $st_key => $screen_time) {
			$thumb_name = str_pad($screens, strlen(MAX_NUM_SCREENS), "0", STR_PAD_LEFT);
			
			$command = FFMPEG_PATH ." -i \"". $video['path'] ."\" -ss ". $screen_time['at'] ." -vframes 1 \"". THUMB_PATH . $thumb_name .".jpg\"";
			
			//print "<p>Running: <b>". $command ."</b></p>\n";flush();
			print "<p>Building <b>Screen #". $i ."</b></p>\n";flush();
			
			$return = exec($command, $output, $return_var);
			
			$info['screen_times'][ $st_key ]['thumb'] = $thumb_name .".jpg";
			
			$screens++;
		}
	}
	
	print "<pre>". htmlentities(print_r(array(
		'info'			=> $info,
	), true)) ."</pre>\n";
	
	
	
	print "<p><strong>Great Success</strong></p>\n";
	print "<p><a href=\"index.php\">Return</a></p>\n";