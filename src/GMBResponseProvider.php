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

}