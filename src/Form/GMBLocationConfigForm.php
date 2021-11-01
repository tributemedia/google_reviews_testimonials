<?php

namespace Drupal\google_reviews_testimonials\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_reviews_testimonials\Entity\TestimonialGMBReview;
use Drupal\node\Entity\Node;

class GMBLocationConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    
    return 'google_reviews_testimonials_gloc_form';
    
  }

  public function buildForm(array $form, 
    FormStateInterface $formState = NULL) {

    // Load default config values
    $config = \Drupal::config('google_reviews_testimonials.settings');
    $locationID = $config->get('locationID');
    $locationName = $config->get('locationName');
    $starMin = $config->get('starMin');
    
    // Build the form
    $form = [];

    $form['settings_container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'settings-container',
      ),
      '#tree' => TRUE,
    );

    $form['settings_container']['location_id'] = array(
      '#type' => 'textfield',
      '#title' => 'Location ID',
      '#default_value' => $locationID,
      '#disabled' => TRUE,
    );

    $form['settings_container']['location_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Location Name',
      '#default_value' => $locationName,
      '#required' => TRUE,
    );

    $form['settings_container']['star_min'] = array(
      '#type' => 'number',
      '#title' => 'Review Star Minimum',
      '#default_value' => $starMin,
      '#min' => 1,
      '#max' => 5,
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $formState) {

    $settings = $formState->getValues()['settings_container'];

    // Load stored config
    $config = \Drupal::service('config.factory')
      ->getEditable('google_reviews_testimonials.settings');
    $accountID = $config->get('accountID');
    $locationName = $config->get('locationName');
    $starMin = $config->get('starMin');
    $serviceKey = $config->get('serviceKey');
    $subject = $config->get('subject');
    $scopes = $config->get('scopes');

    // Update the location name and star min, if necessary.
    if($locationName != $settings['location_name']) {

      $config->set('locationName', $settings['location_name'])->save();
      $locationName = $settings['location_name'];
    }

    if($starMin != $settings['star_min']) {

      // Update config
      $config->set('starMin', $settings['star_min'])->save();
      $starMin = $settings['star_min'];
      $updatedTestimonials = 0;

      // Unpublish testimonials that no longer meet the threshold
      $testimonialGMBReviews = TestimonialGMBReview::loadMultiple();

      foreach($testimonialGMBReviews as $review) {

        $testimonial = Node::load($review->getTID());

        if(!empty($testimonial)) {

          if($review->getStarRating() < $starMin && 
            $testimonial->isPublished()) {
            
            $testimonial->setUnpublished();
            $testimonial->save();
            $updatedTestimonials++;

          }
          else if($review->getStarRating() >= $starMin && 
            !$testimonial->isPublished()) {

            $testimonial->setPublished();
            $testimonial->save();
            $updatedTestimonials++;

          }
          
        }

      }

      \Drupal::messenger()->addMessage('Updated ' 
        . $updatedTestimonials 
        . ' due to change in minimum star rating.');

    }

    // Authenticate and query for the location
    $queryURL = 'https://mybusiness.googleapis.com/v4/accounts/' 
      . $accountID 
      . '/locations';
    $tmpSK = (array)json_decode($serviceKey);
    $googleClient = new \Google\Client();
    $googleClient->setAuthConfig($tmpSK);
    $googleClient->setScopes($scopes);
    $googleClient->setSubject($subject);
    $httpClient = $googleClient->authorize();
    $response = json_decode(strval($httpClient
      ->get($queryURL)
      ->getBody()));
    
    if($response->locations) {

      $locationSet = FALSE;

      // Find the location with an exact match on the locationName property.
      // Note: locationName and name are different values returned from the
      // API!
      foreach($response->locations as $location) {
        
        if($location->locationName == $locationName) {

          // Once again, the location ID required for use in the API is not
          // provided in a straight-forward manner. The locationName property,
          // which contains the location ID, has this value in the following
          // format: 
          // accounts/[accountID]/locations/[locationID]
          // * Square brackets not included
          // Therefore, we need this explode to strip out all the unnecessary
          // info and get the good stuff.
          $locationID = explode('/', $location->name)[3];
          $config->set('locationID', $locationID)->save();

          \Drupal::messenger()->addMessage('Location saved.');
          $locationSet = true;
          break;
        }
      }

      if(!$locationSet) {

        $config->set('locationID', '')->save();
        \Drupal::messenger()->addError('No location found with that name.');

      }
    }
    else {
      
      $message = 'An error occured while querying the Google API. Did you '
        . 'setup your Account ID first? Visit the README for more details.';
      \Drupal::messenger()->addError($message);

    }
  }

}