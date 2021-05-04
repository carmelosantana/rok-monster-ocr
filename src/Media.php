<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use carmelosantana\RoKMonster\AutoCrop;
use carmelosantana\TinyCLI\TinyCLI;
use jamesheinrich\getid3\getID3;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Treinetic\ImageArtist\lib\Image as ImageArtist;

class Media
{
	private object $hasher;

	// apply scale factor to crop points
	public static function applyScale(array $crop, float $scale): array
	{
		$out = [];
		foreach ($crop as $n)
			$out[] = round($n * $scale);

		return $out;
	}

	// compare images
	// https://stackoverflow.com/questions/37581147/percentage-of-pixels-that-have-changed-in-an-image-in-php#37594159
	// https://stackoverflow.com/questions/4684023/how-to-check-if-an-integer-is-within-a-range-of-numbers-in-php
	public static function getCompareDistortion(string $image_path_1, string $image_path_2, bool $resize = true)
	{
		// check for errors
		foreach ([$image_path_1, $image_path_2] as $image) {
			if (!$image or !file_exists($image)) {
				TinyCLI::echo('File not found. ' . $image, ['header' => 'warning', 'function' => __FUNCTION__]);
				return false;
			}
		}

		// load up
		$image1 = new \Imagick($image_path_1);
		$image2 = new \Imagick($image_path_2);

		$w1 = $image1->getImageWidth();
		$h1 = $image1->getImageHeight();
		$h2 = $image2->getImageHeight();
		$diff = 100;

		if ($resize and !(($h1 - $diff <= $h2) and ($h2 <= $h1 + $diff))) {
			$image2->scaleImage($w1, $h1);
		}

		// compare
		$result = $image1->compareImages($image2, \Imagick::METRIC_MEANABSOLUTEERROR);
		$p1 = $image1->getImageProperties();
		return (float) $p1['distortion'];
	}

	public static function crop(string $file, string $output, array $crop): string
	{
		$image = new ImageArtist($file);

		if (!$crop or empty($crop))
			return false;

		$image->crop($crop[0], $crop[1], $crop[2], $crop[3]);

		$image->save($output, exif_imagetype($file), 100);

		return $output;
	}

    public function fingerprint(string $path)
    {
        $this->hasher = new ImageHash(new DifferenceHash());

        return $this->hasher->hash($path);
    }

	public function fingerprintDistance($input_1, $input_2){
		return $this->hasher->distance($input_1, $input_2);		
	}

	public static function getMIMEContentType(string $file)
	{
		foreach (['image', 'video'] as $type)
			if (self::isMIMEContentType($file, $type))
				return $type;

		return false;
	}

	// get scale factor between 2 images
	public static function getScaleFactor($img1, $img2): float
	{
		list($img1_width, $img1_height) = is_array($img1) ? $img1 : getimagesize($img1);
		list($img2_width, $img2_height) = is_array($img2) ? $img2 : getimagesize($img2);

		return round(($img1_height / $img2_height), 5);
	}

	public static function isMIMEContentType(string $file, string $type = 'image'): bool
	{
		if (!is_file($file))
			return false;

		$mime = mime_content_type($file);

		if (substr($mime, 0, strlen($type)) == $type)
			return true;

		return false;
	}

	public static function scale(string $file, string $output, int $scale): string
	{
		$image = new Image($file);

		if ($scale)
			$image->scale($scale);

		$image->save($output, exif_imagetype($file), 100);

		return $output;
	}

	public static function ffmpeg_cmd(array $args): void {
		$def = [
			// task
			'action' => null,
	
			// file
			'input' => null,
			'output_path' => null,
	
			// ffmpeg
			'fps_multiplier' => 1.5,
			'ss' => null,
			'threshold' => '0.4',
		];
		
		// args to vars
		$args = array_merge($def, $args);
		extract($args);
	
		// output file
		$output_file = $output_path . '/' . pathinfo($input)['basename'];
	
		// frames
		$getID3 = new \getID3;
		$input_info = $getID3->analyze($input);
		$frames = round(round($input_info['video']['frame_rate'])*round($fps_multiplier));
	
		// start command
		$vf_scale = ' scale='.$input_info['video']['resolution_x'].':-1';
		$output_file_d = '"' . $output_file.'-%d.png" ';
	
		$cmd = 'ffmpeg -i "'.$input.'" ';
	
		switch ($action) {		
			// single frame
			case 'single':
				$cmd.= '-ss '.($ss ?? '00:00:01.00');
			break;
	
			// https://video.stackexchange.com/questions/19725/extract-key-frame-from-video-with-ffmpeg
			case 'interesting':
				$cmd.= '-ss '.($ss ?? '50').' -vf "'.$vf_scale.', thumbnail='.$frames.'" -vsync 0 '.$output_file_d;
			break;
	
			// https://stackoverflow.com/questions/35675529/using-ffmpeg-how-to-do-a-scene-change-detection-with-timecode
			case 'scene_change':
				$cmd.= ' -vf  "'.$vf_scale.', select=gt(scene\,'.$threshold.')" -vsync vfr ' . $output_file_d;
			break;
	
			default:
				// multiple frames
				$cmd.= '-y';
			break;
		}
	
		// command
		TinyCLI::cli_debug_echo($cmd, ['header' => 'FFmpeg']);	
		$output = exec($cmd);
	}

	public static function video_find_scene_change(string $file, string $output_path): bool
	{
		// skip non video formats
		if (!Media::isMIMEContentType($file, 'video'))
			return false;

		self::ffmpeg_cmd([
			'action' => 'interesting',
			'input' => $file,
			'output_path' => $output_path,
		]);

		return true;
	}
}
