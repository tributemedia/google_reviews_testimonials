<?php

namespace Drupal\google_reviews_testimonials\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_reviews_testimonials\GMBResponseProvider;

class GMBServiceConnectionInfoForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    
    return 'google_reviews_testimonials_gconnect_form';
    
  }

  public function buildForm(array $form, 
    FormStateInterface $formState = NULL) {
    
    // Load default config values
    $config = \Drupal::config('google_reviews_testimonials.settings');
    $accountID = $config->get('accountID');
    $serviceKey = $config->get('serviceKey');
    $subject = $config->get('subject');

    // Build the form
    $form = [];

    $form['settings_container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'settings-container',
      ),
      '#tree' => TRUE,
    );

    $form['settings_container']['account_id'] = array(
      '#type' => 'textfield',
      '#title' => 'Account ID',
      '#default_value' => $accountID,
      '#disabled' => TRUE,
    );

    $form['settings_container']['service_key'] = array(
      '#type' => 'textarea',
      '#title' => 'Service Key',
      '#default_value' => $serviceKey,
      '#required' => TRUE,
    );

    $form['settings_container']['subject'] = array(
      '#type' => 'textfield',
      '#title' => 'Subject',
      '#default_value' => $subject,
      '#required' => TRUE,
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $formState) {
    
    $resProvider = new GMBResponseProvider();
    $settings = $formState->getValues()['settings_container'];

    // Load stored config
    $config = \Drupal::service('config.factory')
      ->getEditable('google_reviews_testimonials.settings');
    $serviceKey = $config->get('serviceKey');
    $subject = $config->get('subject');
    $scopes = $config->get('scopes');

    // If the requested service key to be saved is different than the stored,
    // attempt to save it. Otherwise, do nothing for this step.
    if($serviceKey != $settings['service_key']) {

      $config->set('serviceKey', $settings['service_key'])->save();
      $serviceKey = $settings['service_key'];
    }

    // Update the subject as well, if necessary.
    if($subject != $settings['subject']) {

      $config->set('subject', $settings['subject'])->save();
      $subject = $settings['subject'];
    }

    $response = $resProvider->getAccounts();
    
    // Look through the list of the accounts for the account in which the 
    // businesses are organized under (the one of type LOCATION_GROUP)
    if(isset($response->accounts)) {

      foreach($response->accounts as $account) {

        if($account->type == 'LOCATION_GROUP') {

          // The value needed to query the API is not available as a property,
          // it is apart of the account name though, which is in the format:
          // accounts/[accountID] 
          // * Square brackets not included
          $config->set('accountID', explode('/', $account->name)[1])->save();
        }
      }
    }
  }

  public function validateForm(array &$form, FormStateInterface $formState){

    $tmpSK = json_decode($formState
      ->getValues()['settings_container']['service_key']);

    // json_decode returns NULL if there was an error decoding the JSON (i.e.
    // the JSON was invalid). An error should be returned indicating that.
    if($tmpSK === NULL) {

      $formState->setErrorByName('service_key', 
        'Invalid JSON submitted in the Service Key field.');
    }
  }
}