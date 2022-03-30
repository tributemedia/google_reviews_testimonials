<?php

namespace Drupal\google_reviews_testimonials\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_reviews_testimonials\GMBResponseProvider;
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
    $unpublishEmpty = $config->get('unpublishEmpty');
    
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
    
    $form['settings_container']['unpublish_empty'] = array(
      '#type' => 'checkbox',
      '#title' => 'Unpublish Empty Reviews',
      '#default_value' => $unpublishEmpty,
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
    $nids = \Drupal::entityQuery('node')->condition('type','testimonial')->execute();
    $testimonials = Node::loadMultiple($nids);
    
    // Update empty reviews setting.
    if ($unpublishEmpty != $settings['unpublish_empty']) {
      
      $config->set('unpublishEmpty', $settings['unpublish_empty'])->save();
      $unpublishEmpty = $settings['unpublish_empty'];
      
      if ($unpublishEmpty) {
        
        // TODO: consolidate foreach usage
        foreach($testimonials as $testimonial) {
      
          if(empty($testimonial->get('body')->getValue()[0]['value'])) {

            if($testimonial->isPublished()) {

              $testimonial->setUnpublished();
              $testimonial->save();

            }
          }
        }
      }
    }

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
      // TODO: consolidate foreach usage
      foreach($testimonials as $testimonial) {

        if(!empty($testimonial->get('field_testimonial_num_stars')->getValue())) {

          $testStars = $testimonial->get('field_testimonial_num_stars')->getValue()[0]['value'];

          if($testStars < $starMin && 
            $testimonial->isPublished()) {
            
            $testimonial->setUnpublished();
            $testimonial->save();
            $updatedTestimonials++;

          }
          else if($testStars >= $starMin && 
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

    $locations = [];
    $loadingLocs = TRUE;
    $resProvider = new GMBResponseProvider();
    $nextPageToken = '';

    while($loadingLocs) {

      $response = $resProvider->getLocations($nextPageToken);
      
      if(isset($response->locations)) {

        $locations = array_merge($locations, $response->locations);

      }

      // If nextPageToken isn't NULL, then we have more results to append to the 
      // locations array and need to make another request.
      if(isset($response->nextPageToken)) {

        $nextPageToken = $response->nextPageToken;

      }
      else {

        $nextPageToken = '';
        $loadingLocs = FALSE;

      }

    }
    
    if(isset($locations)) {

      $locationSet = FALSE;

      // Find the location with an exact match on the locationName property.
      // Note: locationName and name are different values returned from the
      // API!
      foreach($locations as $location) {
        
        if($location->title == $locationName) {

          // The location name provides a value in the following format:
          // locations/[locationID]
          // Therefore, we need this explode to strip out all the unnecessary
          // info and get the good stuff.
          $locationID = explode('/', $location->name)[1];
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