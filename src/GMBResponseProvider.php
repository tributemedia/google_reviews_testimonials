<?php

namespace Drupal\google_reviews_testimonials;

use Google\Service\MyBusinessAccountManagement;

class GMBResponseProvider {

  protected $config;

  protected $googleClient;

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

  /**
   * Returns reviews for the configured business from GMB.
   * 
   * @param string $pageToken
   *  The query parameter pageToken required to paginate through results
   */
  public function getReviews($pageToken = "") {

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