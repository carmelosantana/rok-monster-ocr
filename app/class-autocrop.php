<?php
/*!*****************************************************************************************************
 *
 *	@file		autocrop.php
 *	@author		Jos Pape
 *	@version	1.0
 *	@brief		Contains a class that auto crops images
 *  @example	new AutoCrop('scan.jpg'); outputs the image directly
 *  @example	new AutoCrop('scan.jpg', 'croped.jpg'); outputs the image to the file
 ******************************************************************************************************/

/*!**************************************************************************
 *	@class 		AutoCrop
 *	@author		Jos Pape
 *	@version	1.0
 *	@brief 		Facilitates automaticly cropping a image
 **************************************************/ 

class AutoCrop
{
	/*!******************************
	 * @brief	holds the original image
	 */
	var $originalImage = false;
	
	/*!******************************
	 * @brief	holds the original image location
	 */
	var $originalSource = false;
	
	/*!******************************
	 * @brief	holds the destination location
	 */
	var $destinationSource = false;
	
	/*!******************************
	 * @brief	holds the destination image
	 */
	var $destinationImage = false;
	
	/*!******************************
	 * @brief	holds the edges
	 */
	var $edge = array();
	
	/*!******************************
	 * @brief	holds the default margin
	 */
	var $margin = 30;
	
	/*!***********
	 * @brief	holds the default correction value
	 */
	var $correction = 5;
	
	/**
	 * @param 	$source (location to the image)
	 * @param 	$destination (default false means inline display)
	 * @return 	boolean
	 */
	public function __construct($source, $destination=false)
	{
		if(is_file($source))
		{
			$this->originalSource = $source;
			if($destination)
			{
				$this->destinationSource = $destination;
			}
			
			return $this->cropImage();
		}
		else return false;
	}
	
	/**
	 * @return 	boolean
	 */
	private function cropImage()
	{
		// first we get the filetype
		$fileType = mime_content_type($this->originalSource);
		if(substr($fileType, 0, 5) == "image")
		{
			switch($fileType)
			{
				case "image/jpeg":
					$this->originalImage = imagecreatefromjpeg($this->originalSource);
					$this->destinationImage = imagecreatefromjpeg($this->originalSource);
					break;
				
				case "image/gif":
					$this->originalImage = imagecreatefromgif($this->originalSource);
					$this->destinationImage = imagecreatefromgif($this->originalSource);
					break;
					
				case "image/png":
					$this->originalImage = imagecreatefrompng($this->originalSource);
					$this->destinationImage = imagecreatefrompng($this->originalSource);
					break;
					
				default:
					return false;
					break;
			}
			if($this->originalImage)
			{
				$this->imagecolorswap($this->destinationImage);
				$this->edge = $this->imageGetEdges($this->destinationImage, 250, 250, 250);
				imagedestroy($this->destinationImage);
				
				$newWidth = $this->edge[0]-$this->edge[2];
				$newHeight = $this->edge[1]-$this->edge[3];
				$this->destinationImage = imagecreatetruecolor($newWidth, $newHeight);
				imagecopyresized(
					$this->destinationImage, 
					$this->originalImage, 
					0, 
					0, 
					$this->edge[2], 
					$this->edge[3], 
					$newWidth, 
					$newHeight, 
					$newWidth, 
					$newHeight
				);
				
				return $this->outputImage();
			}
			else return false;
		}
		else return false;
	}
	
	/**
	 * @return boolean
	 */
	private function outputImage()
	{
		if(count($this->edge) == 4)
		{
			// output the image....as what?
			if($this->destinationSource)
			{	// save to file
				// get the destination extention so we know how to save this image
				$fileType = strtolower(substr($this->destinationSource,strripos($this->destinationSource, ".")));
				switch($fileType)
				{
					case ".jpg":
					case ".jpeg":
						imagejpeg($this->destinationImage, $this->destinationSource);
						break;
						
					case ".png":
						imagepng($this->destinationImage, $this->destinationSource);
						break;
						
					case ".gif":
						imagegif($this->destinationImage, $this->destinationSource);
						break;	
				}
			}
			else
			{	// inline display
				$fileType = mime_content_type($this->originalSource);
				header('Content-type: '.$fileType);
				switch($fileType)
				{
					case "image/jpeg":
						imagejpeg($this->destinationImage);
						break;
						
					case "image/png":
						imagepng($this->destinationImage);
						break;
						
					case "image/gif":
						imagegif($this->destinationImage);
						break;	
				}
			}
			return true;
		}
		else return false;
	}
	
	/**
	 * @param $img (image resource)
	 * @param $red (color red)
	 * @param $green (color green)
	 * @param $blue (color blue)
	 * @return array(X1, Y1, X2, Y2) (right, bottom, left, top)
	 * @brief	X1,Y1 are the bottom right positions and X2,Y2 are the top left position
	 */
	private function imageGetEdges(&$img, $red, $green, $blue)
	{
		// where the color starts thats te edge
		$margin = 30;
		$width = imagesx($img);
		$height = imagesy($img);
		
		$returnX = $this->margin;
		$returnY = $this->margin;
		$returnXmin = $width-($this->margin);
		$returnYmin = $height-($this->margin);
		$Xvalues = array();
		$Yvalues = array();
		
		$searchColor = imagecolorclosest($img, $red, $green, $blue);
		for($x = $margin; $x<($width-$this->margin); $x=$x+1)
		{
			for($y = $margin; $y<($height-$this->margin); $y=$y+1)
			{
				$color = imagecolorat($img, $x, $y);
				if($color != $searchColor)
				{
					$aprocX = floor($x/$this->correction)*$this->correction;
					$aprocY = floor($y/$this->correction)*$this->correction;
					if(isset($Xvalues[$aprocX]))
						$Xvalues[$aprocX] += 1;
					else
						$Xvalues[$aprocX] = 0;
						
					if(isset($Yvalues[$aprocY]))
						$Yvalues[$aprocY] += 1;
					else
						$Yvalues[$aprocY] = 0;
				}
			}
		}
		// average X and Y
		$returnX = $this->margin;
		$returnY = $this->margin;
		$returnXmin = $width-($this->margin);
		$returnYmin = $height-($this->margin);
		foreach($Xvalues AS $X => $aantal)
		{
			if($X > $returnX && $aantal > 8)
			{
				$returnX = $X;
			}
			if($X < $returnXmin && $aantal > 8)
			{
				$returnXmin = $X;
			}
		}
		foreach($Yvalues AS $Y => $aantal)
		{
			if($Y > $returnY && $aantal > 8)
			{
				$returnY = $Y;
				$maxAantalY = $aantal;
			}
			if($Y < $returnYmin && $aantal > 8)
			{
				$returnYmin = $Y;
			}
		}
		
		$testmargin = $this->margin + $this->correction;
	
		if($returnX >= $width-($testmargin))
			$returnX = $width;
		else
			$returnX += $this->correction;
			
		if($returnY >= $height-($testmargin))
			$returnY = $height;
		else
			$returnY += $this->correction;
			
		if($returnXmin <= $testmargin)
			$returnXmin  = 0;
		else
			$returnXmin -= $this->correction;
			
		if($returnYmin <= $testmargin)
			$returnYmin = 0;
		else
			$returnYmin -= $this->correction;
			
		return array($returnX, $returnY, $returnXmin, $returnYmin);
	}
	
	private function imagecolorswap(&$img) 
	{	
		// First we remove the background
		imagefilter($img, IMG_FILTER_SMOOTH, 10);
		imagefilter($img, IMG_FILTER_EDGEDETECT);
		imagefilter($img, IMG_FILTER_CONTRAST, -90);
		imagefilter($img, IMG_FILTER_BRIGHTNESS, -20);
		//imagefilter($img, IMG_FILTER_BRIGHTNESS, -40);
		imagefilter($img, IMG_FILTER_CONTRAST, 20);
		
		// remove color: 120 120 120 TO 180 180 180
		if (!($t = imagecolorstotal($img))) 
		{
			$t = 250;
			imagetruecolortopalette($img, 1, $t);
		}
		for ($c = 0; $c < $t; $c++) 
		{ 
			$cc = imagecolorclosest($img, 150, 150, 150);
			imagecolorset($img, $cc, 250, 250, 250);
		}
		imagefilter($img, IMG_FILTER_COLORIZE, 0, 0, 0);
	}
}