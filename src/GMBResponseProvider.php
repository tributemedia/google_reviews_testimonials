<?php

namespace Drupal\google_reviews_testimonials;

use Google\Service\MyBusinessAccountManagement;
use Google\Service\MyBusinessBusinessInformation;

/**
 * Responsible for dealing with the GMB API, and returning responses.
 */
class GMBResponseProvider {

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
   * Page limit on requests.
   * 
   * @var integer
   */
  protected $pageSize;

  public function __construct() {

    $config = \Drupal::config('google_reviews_testimonials.settings');
    $serviceKey = $config->get('serviceKey');
    $subject = $config->get('subject');
    $scopes = $config->get('scopes');
    $googleClient = new \Google\Client();

    $tmpSK = (array)json_decode($serviceKey);
    $googleClient->setAuthConfig($tmpSK);
    $googleClient->setScopes($scopes);
    $googleClient->setSubject($subject);

    $this->config = $config;
    $this->pageSize = 20;
    $this->googleClient = $googleClient;
  }

  /**
   * Tests the validity of provided service key auth.
   * 
   * @param string $serviceKey
   *  The service key
   * @param string $subject
   *  Email, as a string, of subject to act on behalf of using key.
   * @param array $scopes
   *  An array of strings detailing the auth scopes to use when making requests.
   * @return bool
   */
  public static function testAuth($serviceKey, $subject, $scopes) {

    $googleClient = new \Google\Client();

    $tmpSK = (array)json_decode($serviceKey);
    $googleClient->setAuthConfig($tmpSK);
    $googleClient->setScopes($scopes);
    $googleClient->setSubject($subject);
    $mbam = new MyBusinessAccountManagement($googleClient);

    if(!empty($serviceKey) && !empty($subject) && !empty($scopes)) {

      try {

        if(($mbam->accounts->listAccounts()->accounts) > 0) {

          return TRUE;

        }
      }
      catch(\Google\Service\Exception $e) {

        \Drupal::logger('google_reviews_testimonials')
          ->error('Access credentials provided are incorrect.');

      }
    }
    else {

      \Drupal::logger('google_reviews_testimonials')
        ->error('One or more of the following is empty: service key, scope, subject.');

    }

    return FALSE;

  }

  /**
   * Returns accounts associated with configured GMB credentials.
   * 
   * @return object
   */
  public function getAccounts() {

    $accounts = [];

    $mbam = new MyBusinessAccountManagement($this->googleClient);

    try {

      $accounts = $mbam->accounts->listAccounts()->accounts;

    }
    catch(\Google\Service\Exception $e) {

      \Drupal::logger('google_reviews_testimonials')
          ->error('Tried getting accounts with invalid credentials.');

    }

    return $accounts;

  }

  public function getLocations($pageToken = '') {

    $mbbm = new MyBusinessBusinessInformation($this->googleClient);
    // READ MASK Needed to specify fields returned. See following for more info:
    // https://stackoverflow.com/questions/69646296/google-api-get-locations-with-google-service-mybusinessbusinessinformation
    $queryParams = [
      'readMask' => 'name,labels,title',
    ];
    $accountName = 'accounts/' . $this->config->get('accountID');
    $locsResponse = NULL;

    if(!empty($pageToken)) {

      $queryParams['pageToken'] = $pageToken;

    }

    try {

      $locsResponse = $mbbm->accounts_locations
        ->listAccountsLocations($accountName, $queryParams);

    }
    catch(\Google\Service\Exception $e) {

      \Drupal::logger('google_reviews_testimonials')
          ->error('Tried getting locations with invalid credentials.');

    }

    return $locsResponse;

  }

  /**
   * Returns reviews for the configured business from GMB.
   * 
   * @param string $pageToken
   *  The query parameter pageToken required to paginate through results
   * @return object
   */
  public function getReviews($pageToken = "") {

    // Currently, Reviews API is not being deprecated on v4 of the API, and 
    // has no replace on the new API. So, old v4 must be used, which requires
    // this procedural code to request.
    $rootURL = 'https://mybusiness.googleapis.com/v4';
    $accountID = $this->config->get('accountID');
    $locationID = $this->config->get('locationID');
    $url = $rootURL 
      . '/accounts/' . $accountID
      . '/locations/' . $locationID
      . '/reviews'
      . '?pageSize=' . $this->pageSize;

    if(!empty($pageToken)) {

      $url .= '&pageToken=' . $pageToken;

    }

    $responseJSON = json_decode(strval(
      $this->googleClient->authorize()->get($url)->getBody()));
    $reviews = $responseJSON->reviews;
    $returnObj = new \stdClass();
    $returnObj->reviews = $reviews;
    
    if(isset($responseJSON->nextPageToken)) {

      $returnObj->pageToken = $responseJSON->nextPageToken;

    }

    return $returnObj;
  }

}