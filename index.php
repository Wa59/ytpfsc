<?php
ini_set('max_execution_time', '300');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="robots" content="none" />
        <meta name="googlebot" content="noarchive" />
        <title>YouTube Parser for Slow Computers</title>
    </head>

    <body>
    	<h1>YouTube Parser for Slow Computers</h1>
			<form action="" method="get">
				<label for="url">Enter YouTube video URL:</label>
				<input type="text" name="url" id="url" value="<?php echo isset($_GET['url']) ? $_GET['url'] : '' ?>" />
				<input type="submit" value="Submit" />
			</form>

			<?php if (isset($_GET['url']) && $_GET['url'] != '') {

				echo "<h2>Parsing ".$_GET['url']."</h2>";

				//TODO do a better job at cleaning input to prevent messing with the command line
				$url = str_replace(' ','',$_GET['url']);
				$url = str_replace(';','',$url);

				$file_name = md5($url);

				exec('youtube-dl -f 133 -o '.$file_name.'/video.mp4 '.$url, $output, $ret);
				if ($ret == 1) { echo "<p><strong>Error downloading video (check URL)</strong><br><pre>".var_dump($output)."</pre></p>"; }

				if ($ret != 1 && !empty($output)) {
					exec('ffmpeg -i '.$file_name.'/video.mp4 -vf "scale=320:-1,fps=1/30" '.$file_name.'/i_%d.jpg', $output, $ret);

					$imagecount = count(glob($file_name.'/*.jpg'));
					
					//TODO Language selector option
					exec('youtube-dl --write-sub --write-auto-sub --sub-lang en --skip-download '.$url, $output, $ret);

					$regex = '/\\d\\d:\\d\\d:\\d\\d\\.\\d\\d\\d/i';
					$sub_arr = array();
					// use mv on linux
					exec('move *.vtt '.$file_name.'/sub.vtt', $output, $ret);
					if ($ret == 0) {
						$sub = fopen($file_name.'/sub.vtt', "r") or die ('Error opening subtitle file!');
						while(($line=fgets($sub))!==false) { 
							$line = preg_replace($regex, '', $line);
							$line = str_replace(' --> ', '', $line);
							$line = str_replace('align:start position:0%', '', $line);
							$line = str_replace('<c>', '', $line);
							$line = str_replace('</c>', '', $line);
							$line = str_replace('<>', '', $line);
							array_push($sub_arr, $line);
						}
						fclose($sub);
					}
					$sub_arr = array_unique($sub_arr);

					if (sizeof($sub_arr) == 0) {
						echo "<p>No subtitles found! Showing only images.</p>";
						for ($i=1;$i<=$imagecount;$i++) { 
							echo '<p><img alt="" src="'.$file_name.'/i_'.$i.'.jpg" /></p>';
						}
					} else {
						$i = 1;
						$c = 0; 
						$a = (int) sizeof($sub_arr)/$imagecount;
						echo '<p>';
						foreach ($sub_arr as $line) {
							if ($c > 4) {
								echo $line."<br>";
							}
							if ($c > 10 && $c % $a == 0 && $i <= $imagecount) {
								echo '<p><img alt="" src="'.$file_name.'/i_'.$i.'.jpg" /></p>';
								$i++;
							}
							
							$c++;
						}
						echo '</p>';
					}
				}
			} ?>
    </body>
</html>