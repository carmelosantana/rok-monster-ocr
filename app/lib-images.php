<?php
use Treinetic\ImageArtist\lib\Image;
use Treinetic\ImageArtist\lib\PolygonShape;
use Treinetic\ImageArtist\lib\Text\TextBox;
use Treinetic\ImageArtist\lib\Text\Color;
use Treinetic\ImageArtist\lib\Text\Font;
use Treinetic\ImageArtist\lib\Overlays\Overlay;
use Treinetic\ImageArtist\lib\Text\Write\WriteFactory;
use Treinetic\ImageArtist\lib\Text\Write\GDWritingStrategy;
use Treinetic\ImageArtist\lib\Text\Write\ImagickWritingStrategy;

/**
 * Imagick
 */
// https://code.i-harness.com/en/q/3e3588
function imagick_get_dpi(string $file){
    $cmd = 'identify -quiet -units PixelsPerInch -format "%x" "'.$file.'"';       
    $data = @shell_exec(escapeshellcmd($cmd));
    if ( $data == '1' )
        return 72;

    return round((int)$data);
}

function imagick_convert_dpi(string $file, string $output, $dpi=72){
    $cmd = 'convert "'.$file.'" -set units PixelsPerInch -density '.$dpi.' "'.$output.'"';
    $data = @shell_exec(escapeshellcmd($cmd));
    return $data;
}

function imagick_convert_gray(string $file, string $output){
	$cmd = 'convert -colorspace gray -modulate 120 -gaussian-blur 1 -negate -modulate 120 "'.$file.'" "'.$output.'"';
    $data = @shell_exec(escapeshellcmd($cmd));
    return $data;
}

// http://bubble.ro/How_to_create_the_histogram_of_an_image_using_PHP.html
function image_get_histogram(){
	$source_file = "test_image.jpg";

	// histogram options
	$maxheight = 300;
	$barwidth = 2;

	$im = ImageCreateFromJpeg($source_file); 
	$imgw = imagesx($im);
	$imgh = imagesy($im);

	// n = total number or pixels

	$n = $imgw*$imgh;

	$histo = array();

	for ($i=0; $i<$imgw; $i++) {
        for ($j=0; $j<$imgh; $j++) {
            // get the rgb value for current pixel	                
            $rgb = ImageColorAt($im, $i, $j); 
            
            // extract each value for r, g, b
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // get the Value from the RGB value
            $V = round(($r + $g + $b) / 3);
            
            // add the point to the histogram
            $histo[$V] += $V / $n;
        }
	}

	// find the maximum in the histogram in order to display a normated graph
	$max = 0;
	for ($i=0; $i<255; $i++) {
        if ($histo[$i] > $max) {
            $max = $histo[$i];
        }
	}

	echo "<div style='width: ".(256*$barwidth)."px; border: 1px solid'>";
	for ($i=0; $i<255; $i++) {
        $val += $histo[$i];	        
        $h = ( $histo[$i]/$max )*$maxheight;
        echo "<img src=\"img.gif\" width=\"".$barwidth."\" height=\"".$h."\" border=\"0\">";
	}
	echo "</div>";
}

// compare images
function image_compare_get_distortion($image_path_1=null, $image_path_2=null){
	// https://stackoverflow.com/questions/37581147/percentage-of-pixels-that-have-changed-in-an-image-in-php#37594159
	if ( !$image_path_1 or !file_exists($image_path_1) ){
		echo '!' . $image_path_1;
		return false;
	}
	if ( !$image_path_2 or !file_exists($image_path_2) ){
		echo '!' . $image_path_2;
		return false;
	}

	// load up
	$image1 = new Imagick($image_path_1);
	$image2 = new Imagick($image_path_2);

	// compare
	$result = $image1->compareImages($image2,Imagick::METRIC_MEANABSOLUTEERROR);
	$p1 = $image1->getImageProperties();
	return $p1['distortion'];
}

function image_add_mask_ia($file1, $file2, $output, $scale=null){
	$img1 = new Image($file1);
	$img2 = new Image($file2);

	$img2->merge($img1, 0, 0);
	
	$img2->save($output, IMAGETYPE_PNG);	
}

function image_scale_crop(string $file, string $output, int $scale){
	$image = new Image($file);

	$org_width = $image->getWidth();
	$org_height = $image->getHeight();

	$image->crop(950, 250, 1120, 700);

	if ( $scale )
		$image->scale($scale);

	$image->save($output, IMAGETYPE_PNG);	
}