<?php

namespace App\Api;
use Luracast\Restler\RestException;
use Nette\Utils\Validators;

/**
 * Class Api
 * @package App\Api
 */
class Api
{
	protected $allowedTypes = array(
		'image/jpeg' => 'jpeg',
		'image/png' => 'png',
	);

	public static $colors = array(
		0 => array('r' => 0, 'g' => 0, 'b' => 0),
		1 => array('r' => 255, 'g' => 0, 'b' => 0),
		2 => array('r' => 0, 'g' => 255, 'b' => 0),
		3 => array('r' => 0, 'g' => 0, 'b' => 255),
		4 => array('r' => 255, 'g' => 128, 'b' => 255),
		5 => array('r' => 255, 'g' => 0, 'b' => 255),
		6 => array('r' => 0, 'g' => 255, 'b' => 255),
		7 => array('r' => 128, 'g' => 38, 'b' => 246),
	);

	/**
	 * @url GET imagesquaredrawing
	 *
	 * @param string $imageUrl
	 * @param string $squares square configuration array in json
	 * @return array
	 * @throws RestException
	 */
	function imageSquareDrawing($imageUrl, $squares) {
		$temp = tmpfile();
		if(!Validators::isUrl($imageUrl)) {
			throw new RestException(400, "imageUrl must be a valid url address");
		}

		$squares = json_decode($squares, true);

		foreach($squares as $key => $square) {
			if(!isset($square['positionX'])) {
				throw new RestException(400, "positionX parameter missing for square " . $key);
			} else if(!isset($square['positionY'])) {
				throw new RestException(400, "positionY parameter missing for square " . $key);
			} else if(empty($square['width'])) {
				throw new RestException(400, "width parameter missing for square " . $key);
			} else if(empty($square['height'])) {
				throw new RestException(400, "height parameter missing for square " . $key);
			}
		}

		$remoteFile = file_get_contents($imageUrl);
		if($remoteFile === false) {
			throw new RestException(400, "image not accessible");
		}

		fwrite($temp, $remoteFile);
		$fileMetadata = stream_get_meta_data($temp);
		$tempFilename = $fileMetadata['uri'];

		switch($this->allowedTypes[mime_content_type($tempFilename)]) {
			case 'jpeg':
				$image = imagecreatefromjpeg($tempFilename);
				break;

			case 'png':
				$image = imagecreatefrompng($tempFilename);
				break;

			default:
				throw new RestException(400, "not a valid image");
				break;
		}

		if(!$image) {
			throw new RestException(400, "not a valid image");
		}

		foreach($squares as $key => $square) {
			$result = imagerectangle($image, $square['positionX'], $square['positionY'], $square['positionX'] + $square['width'], $square['positionY'] + $square['height'], imagecolorallocate($image, self::$colors[$key]['r'], self::$colors[$key]['g'], self::$colors[$key]['b']));
			if(!$result) {
				throw new RestException(500, "unable to create result image");
			}
		}

		ob_start();
		imagejpeg($image);
		$image_data = ob_get_contents();
		ob_end_clean();

		imagedestroy($image);
		fclose($temp);

		return array("status" => "OK", "image" => base64_encode($image_data));
	}
}