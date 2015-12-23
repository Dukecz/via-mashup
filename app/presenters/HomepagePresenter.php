<?php

namespace App\Presenters;

use App\Services\ApiConfig;
use Nette;
use GuzzleHttp\Client;


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
	public function getClarifaiToken(Client $client)
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

	public function renderDefault()
	{
		$imageUrl = "http://www.sloanlongway.org/images/default-album/tank-181.jpg";
		$this->template->imageUrl = $imageUrl;

		$clientClarifai = new Client([
			'base_uri' => $this->apiConfig->options->clarifai['baseUrl'],
			'timeout' => 11,
		]);

		$this->getClarifaiToken($clientClarifai);
		if(!empty($this->clarifaiToken)) {
			$response = $clientClarifai->request('GET', '/v1/tag/', [
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
			$this->template->tagStatus = $result['status_code'] . ':' . $result['status_msg'];
		}

		$parameters = array(
			'url' => $imageUrl,
			'apikey' => $this->apiConfig->options->alchemyApi['apiKey'],
			'outputMode' => "json",
		);

		$client = new Client([
			'base_uri' => $this->apiConfig->options->alchemyApi['baseUrl'],
			'timeout' => 11,
		]);

		$response = $client->request('GET', '/calls/url/URLGetRankedImageFaceTags', [
			'query' => $parameters,
		]);

		$result = json_decode($response->getBody()->getContents(), true);

		if ($response->getStatusCode() == 200) {
			$this->template->faces = $result;
		}
		$this->template->faceStatus = $result['status'];
	}
}
