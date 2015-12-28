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

		$result = imagerectangle($image, 0, 0, 50, 50, imagecolorallocate($image, 0, 0, 0));

		if(!$result) {
			throw new RestException(500, "unable to create result image");
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