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
    
    $form = [];

    $form['test'] = array(
      '#markup' => 'Hello form!',
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $formState) {

  }

}