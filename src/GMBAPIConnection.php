<?php

namespace Drupal\google_reviews_testimonials;

/**
 * A connection instance to the GMB API.
 */
class GMBAPIConnection {

  /**
   * Module config object
   * 
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Google client connection object.
   * 
   * @var \Google\Client
   */
  protected $googleClient;

  /**
   * The Guzzle web client.
   * 
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Page limit on requests.
   * 
   * @var integer
   */
  protected $pageSize;

  /**
   * Root string of the URL to make requests to.
   * 
   * @var string
   */
  protected $rootURL;

  public function __construct() {
    
    $rootURL = 'https://mybusiness.googleapis.com/v4';
    $config = \Drupal::config('google_reviews_testimonials.settings');
    $serviceKey = $config->get('serviceKey');
    $subject = $config->get('subject');
    $scopes = $config->get('scopes');
    $googleClient = new \Google\Client();

    $tmpSK = (array)json_decode($serviceKey);
    $googleClient->setAuthConfig($tmpSK);
    $googleClient->setScopes($scopes);
    $googleClient->setSubject($subject);
    $httpClient = $googleClient->authorize();

    $this->config = $config;
    $this->httpClient = $httpClient;
    $this->rootURL = $rootURL;
    $this->pageSize = 20;
  }

  /**
   * Returns reviews for the configured business from GMB.
   * 
   * @param string $pageToken
   *  The query parameter pageToken required to paginate through results
   */
  public function getReviews($pageToken = "") {

    $accountID = $this->config->get('accountID');
    $locationID = $this->config->get('locationID');
    $url = $this->rootURL 
      . '/accounts/' . $accountID
      . '/locations/' . $locationID
      . '/reviews'
      . '?pageSize=' . $this->pageSize;

    if(!empty($pageToken)) {

      $url .= '&pageToken=' . $pageToken;

    }

    $responseJSON = json_decode(strval(
      $this->httpClient->get($url)->getBody()));
    $reviews = $responseJSON->reviews;
    $returnObj = new \stdClass();
    $returnObj->reviews = $reviews;
    
    if(isset($responseJSON->nextPageToken)) {

      $returnObj->pageToken = $responseJSON->nextPageToken;

    }

    return $returnObj;
  }

}