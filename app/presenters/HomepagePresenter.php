<?php

namespace App\Presenters;

use App\Api\Api;
use App\Services\ApiConfig;
use Nette;
use GuzzleHttp\Client;
use Nette\Utils\Validators;

class HomepagePresenter extends Nette\Application\UI\Presenter
{
	/** @var ApiConfig @inject */
	public $apiConfig;

	/**
	 * @var string
	 */
	public $clarifaiToken;

	/**
	 * @param Client $client
	 */
	protected function getClarifaiToken(Client $client)
	{
		$response = $client->request('POST', '/v1/token/', [
			'query' => [
				'grant_type' => 'client_credentials',
				'client_id' => $this->apiConfig->options->clarifai['clientId'],
				'client_secret' => $this->apiConfig->options->clarifai['clientSecret']
			],
		]);

		$result = json_decode($response->getBody()->getContents(), true);

		if ($response->getStatusCode() == 200) {
			$this->clarifaiToken = $result['access_token'];
		}
	}

	/**
	 * @param Client $client
	 * @param string $imageUrl
	 */
	protected function getClarifaiTags(Client $client, $imageUrl)
	{
		$response = $client->request('GET', '/v1/tag/', [
			'query' => [
				'url' => $imageUrl,
			],
			'headers' => [
				'Authorization' => 'Bearer ' . $this->clarifaiToken,
			],
		]);

		$result = json_decode($response->getBody()->getContents(), true);

		if ($response->getStatusCode() == 200) {
			$this->template->tags = $result['results'][0]['result']['tag']['classes'];
		}
		$this->template->tagStatus = $result['status_code'];
	}

	/**
	 * @param Client $client
	 * @param $imageUrl
	 */
	protected function getAlchemyFaces(Client $client, $imageUrl)
	{
		$parameters = array(
			'url' => $imageUrl,
			'apikey' => $this->apiConfig->options->alchemyApi['apiKey'],
			'outputMode' => "json",
		);

		$response = $client->request('GET', '/calls/url/URLGetRankedImageFaceTags', [
			'query' => $parameters,
		]);

		$result = json_decode($response->getBody()->getContents(), true);

		if ($response->getStatusCode() == 200) {
			$this->template->faces = $result['imageFaces'];
		}
		$this->template->faceStatus = $result['status'];
	}

	/**
	 * @param Client $client
	 * @param $imageUrl
	 * @param array $facesLocations
	 */
	protected function getMashupImage(Client $client, $imageUrl, array $facesLocations)
	{
		$response = $client->request('GET', '/api/imagesquaredrawing', [
			'query' => ['imageUrl' => $imageUrl,
						'squares' => json_encode($facesLocations),
					],
		]);

		$result = json_decode($response->getBody()->getContents(), true);

		if ($response->getStatusCode() == 200) {
			$this->template->faceImage = $result['image'];
		}
		$this->template->faceImageStatus = $result['status'];
	}

	public function renderDefault()
	{
		$imageUrl = $this->getHttpRequest()->getPost('imageUrl');

		if(empty($imageUrl) || !Validators::isUrl($imageUrl)) {
			if($this->getComponent('imageUrlForm')->isSubmitted()) {
				$this->getComponent('imageUrlForm')->getComponent('imageUrl')->addError('Please enter absolute url');
			}
			return;
		}

		$this->template->imageUrl = $imageUrl;

		$clientClarifai = new Client([
			'base_uri' => $this->apiConfig->options->clarifai['baseUrl'],
			'timeout' => 11,
		]);

		$this->getClarifaiToken($clientClarifai);
		if (!empty($this->clarifaiToken)) {
			$this->getClarifaiTags($clientClarifai, $imageUrl);
		}

		$clientAlchemy = new Client([
			'base_uri' => $this->apiConfig->options->alchemyApi['baseUrl'],
			'timeout' => 11,
		]);

		$this->getAlchemyFaces($clientAlchemy, $imageUrl);

		$clientMashup = new Client([
			'base_uri' => $this->apiConfig->options->mashup['baseUrl'],
			'timeout' => 11,
		]);

		if(!empty($this->template->faces)) {
			$facesLocations = array();
			foreach($this->template->faces as $face) {
				$facesLocations[] = array(
					'positionX' => $face['positionX'],
					'positionY' => $face['positionY'],
					'width' => $face['width'],
					'height' => $face['height'],
				);
			}
			$this->getMashupImage($clientMashup, $imageUrl, $facesLocations);
		} else {
			$this->template->faceImageStatus = 'Not called';
		}

		$this->template->colors = Api::$colors;
	}

	/**
	 * @param $name
	 * @return \ImageUrlFormFactory
	 */
	protected function createComponentImageUrlForm($name)
	{
		$form = new \ImageUrlFormFactory($this, $name);

		return $form;
	}
}
