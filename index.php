<?php
	$videos = array();
	$raw_videos = glob("videos/*");
	
	foreach(glob("videos/*") as $raw_video) {
		if(("." === $raw_video) || (".." === $raw_video) || !preg_match("#(?:^|/)([^/]+?)\.(mp4|wmv|mpe?g|flv|mov|webm|mkv|avi|m4v|3gp)$#si", $raw_video, $match)) {
			continue;
		}
		
		$videos[] = array(
			'file'	=> $match[1] .".". $match[2],
			'path'	=> __DIR__ ."/videos/". $match[1] .".". $match[2],
			'name'	=> $match[1],
			'ext'	=> $match[2],
			'hash'	=> md5($match[1] .".". $match[2]),
		);
	}
	
	if(empty($videos)) {
		die("videos/ doesn't contain any videos");
	}
	
	$i = 0;
	
	foreach($videos as $video) {
		if(++$i > 1) {
			print "<hr />\n";
		}
		
		print "<h3>". $video['file'] ."</h3>\n";
		print "<p>". $video['path'] ."</p>\n";
		print "<p>". $video['hash'] ."</p>\n";
		
		
		$thumbs = glob("thumbs/". $video['hash'] ."/*.jpg");
		
		if(!empty($thumbs)) {
			sort($thumbs);
			
			print "<div class=\"thumb_viewer\"><img src=\"". $thumbs[0] ."\" class=\"thumb_cycle\" data-hash=\"". $video['hash'] ."\" data-frames=\"". htmlentities(json_encode($thumbs)) ."\" style=\"width: 300px;\" /><div class=\"percentage\"></div></div>\n";
			
			print "<p><a href=\"process.php?file=". urlencode($video['file']) ."\">Reprocess Video</a></p>\n";
		} else {
			print "<p>Not Processed. <a href=\"process.php?file=". urlencode($video['file']) ."\">Process Video</a></p>\n";
		}
	}
?>
<style type="text/css">
	.thumb_viewer { position: relative; display: inline-block; }
		.thumb_viewer .percentage { visibility: hidden; position: absolute; z-index: 99; bottom: 0; left: 0; width: 0; height: 3px; background-color: #105ca5; }
			.thumb_viewer.thumb_animating .percentage { visibility: visible; }
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script type="text/javascript">
	;jQuery(document).ready(function($) {
		$(".thumb_viewer .thumb_cycle").on({
			'mouseenter':	function(e) {
				var num_frames = $(this).attr('data-num-frames') || false,
					frames;
				
				$(".thumb_viewer.thumb_animating").removeClass("thumb_animating");
				$(this).parent().addClass("thumb_animating");
				
				if(false === num_frames) {
					frames = JSON.parse( $(this).attr('data-frames') );
					$(this).attr('data-num-frames', frames.length);
				}
			},
			'mousemove':	function(e) {
				var elOffset = $(this).offset(),
					elWidth = $(this).width(),
					offX = (e.pageX - elOffset.left),
					elFrame = $(this).attr('data-at-frame') || 0,
					elNumFrames = $(this).attr('data-num-frames') || 0,
					elFrames = false,
					atPercent, atFrame;
				
				elFrame = parseInt(elFrame, 10);
				
				if(!elNumFrames) {
					elFrames = JSON.parse( $(this).attr('data-frames') );
					
					elNumFrames = elFrames.length;
					$(this).attr('data-num-frames', elNumFrames);
				}
				
				atPercent = Math.round( ((offX / elWidth) * 100) );
				atFrame = Math.floor( (offX / (elWidth / elNumFrames)) );
				
				console.log("atPercent = "+ atPercent);
				console.log("atFrame = "+ atFrame);
				
				$(this).parent().addClass("thumb_animating").find(".percentage").css({'width': atPercent +"%"});
				
				if(atFrame === elFrame) {
					return;
				}
				
				if(false === elFrames) {
					elFrames = JSON.parse( $(this).attr('data-frames') );
				}
				
				$(this).attr({
					'src':	elFrames[ atFrame ],
					'data-at-frame':	atFrame
				});
				
				console.log("Showing Frame #"+ atFrame +": "+ elFrames[ atFrame ]);
			},
			'mouseleave':	function(e) {
				var elFrames = JSON.parse( $(this).attr('data-frames') );
				
				$(this).attr('src', elFrames[0]);
				$(this).parent().removeClass("thumb_animating").find(".percentage").css({'width': "0%"});
			}
		});
	});
</script>