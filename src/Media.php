<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use carmelosantana\RoKMonster\AutoCrop;
use carmelosantana\RoKMonster\TinyCLI;
use Treinetic\ImageArtist\lib\Image as ImageArtist;

class Media
{
	// apply scale factor to crop points
	public static function apply_scale_factor(array $crop, float $scale): array
	{
		$out = [];
		foreach ($crop as $n)
			$out[] = round($n * $scale);

		return $out;
	}

	// compare images
	// https://stackoverflow.com/questions/37581147/percentage-of-pixels-that-have-changed-in-an-image-in-php#37594159
	// https://stackoverflow.com/questions/4684023/how-to-check-if-an-integer-is-within-a-range-of-numbers-in-php
	public static function compare_get_distortion(string $image_path_1, string $image_path_2, bool $resize = true)
	{
		// check for errors
		foreach ([$image_path_1, $image_path_2] as $image) {
			if (!$image or !file_exists($image)) {
				TinyCLI::cli_echo('File not found. ' . $image, ['header' => 'warning', 'function' => __FUNCTION__]);
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

	public static function get_mime_content_type(string $file)
	{
		foreach (['image', 'video'] as $type)
			if (self::is_mime_content_type($file, $type))
				return $type;

		return false;
	}

	// get scale factor between 2 images
	public static function get_scale_factor(string $img1, string $img2): float
	{
		list($img1_width, $img1_height) = getimagesize($img1);
		list($img2_width, $img2_height) = getimagesize($img2);

		return round(($img1_height / $img2_height), 5);
	}

	public static function image_prep_ocr(string $file, string $output_path, array $profile): string
	{
		$def = [
			'autocrop' => null,
		];
		$profile = array_merge($def, $profile);

		if ($profile['autocrop']) {
			$file_crop = $output_path . '/' . pathinfo($file)['basename'];
			new AutoCrop($file, $file_crop);
			return $file_crop;
		}

		return $file;
	}

	public static function is_mime_content_type(string $file, string $type = 'image'): bool
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

	// find interesting scenes
	public static function video_find_scene_change(string $file, string $output_path): bool
	{
		// skip non video formats
		if (!Media::is_mime_content_type($file, 'video'))
			return false;

		self::ffmpeg_cmd([
			'action' => 'interesting',
			'input' => $file,
			'output_path' => $output_path,
		]);

		return true;
	}
}
