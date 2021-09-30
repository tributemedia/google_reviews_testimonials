<?php

namespace Drupal\google_reviews_testimonials\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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

  }

}