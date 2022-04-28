<?php

namespace Drupal\google_reviews_testimonials\Form;

use Drupal\Core\CronInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StatusForm extends FormBase {

  /**
   * @var CronInterface
   */
  protected $cron;

  /**
   * @var MessengerInterface
   */

  /**
   * Dependency injected constructor
   * @param CronInterface $cron
   */
  public function __construct(CronInterface $cron, MessengerInterface $messenger) {

    $this->cron = $cron;
    $this->messenger = $messenger;
  }

  /**
   * @param ContainerInterface $container
   * @return FormBase|StatusForm
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('cron'),
      $container->get('messenger')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    
    return 'google_reviews_testimonials_status_form';
    
  }

  public function buildForm(array $form, 
    FormStateInterface $formState = NULL) {

    $queueFactory = \Drupal::service('queue');
    $config = \Drupal::config('google_reviews_testimonials.settings');
    $nextRunTime = 
      \Drupal::state()->get('google_reviews_testimonials.next_exec');
    $rcq = $queueFactory->get('google_reviews_testimonials_rcq');
    $rlq = $queueFactory->get('google_reviews_testimonials_rlq');
    $testimonialCount = \Drupal::entityQuery('testimonial_gmb_review')
      ->count()
      ->execute();
  
    if(empty($nextRunTime)) {
  
      $nextRunTime = 0;
  
    }
    
    $rcqText = 'Items in Review Check Queue (RCQ): ' . 
      $rcq->numberOfItems();
    $rlqText = 'Items in Review Link Queue (RLQ): ' . 
      $rlq->numberOfItems();
    $countText = 'Testimonials created: ' . $testimonialCount;
    $nextRunTimeText = 'Next run time: ';

    if(empty($nextRunTime)) {

      $nextRunTimeText .= '0';

    }
    else {

      $nextRunTimeText .= date("D M j G:i:s T Y", $nextRunTime);

    }

    $form = [];

    $form['stats'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'stats-container',
      ),
      '#tree' => TRUE,
    );

    $form['stats']['review_check_queue'] = [
      '#type' => 'label',
      '#title' => $rcqText,
    ];

    $form['stats']['review_link_queue'] = [
      '#type' => 'label',
      '#title' => $rlqText,
    ];

    $form['stats']['count'] = [
      '#type' => 'label',
      '#title' => $countText,
    ];

    $form['stats']['next_run'] = [
      '#type' => 'label',
      '#title' => $nextRunTimeText,
    ];

    $form['start_flow'] = [
      '#type' => 'submit',
      '#value' => 'Check For Reviews'
    ];

    $form['clear_queues'] = [
      '#type' => 'submit',
      '#value' => 'Clear Queues'
    ];

    $form['run_cron'] = [
      '#type' => 'submit',
      '#value' => 'Run Cron'
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $formState) {
    
    $queueFactory = \Drupal::service('queue');
    $rcq = $queueFactory->get('google_reviews_testimonials_rcq');
    $rlq = $queueFactory->get('google_reviews_testimonials_rlq');
    
    switch($formState->getValues()['op']) {

      case 'Clear Queues':
        $rcq->deleteQueue();
        $rlq->deleteQueue();
        $this->messenger->addMessage('Queues cleared.');
        break;
      case 'Check For Reviews':
        if($rcq->numberOfItems() > 0) {
          $this->messenger->addError('RCQ already has a job queued.');
        }
        else {
          $rcq->createItem(new \stdClass());

          // TODO:
          // This state logic is also in the hook_cron. Encapsulate this
          // code somewhere.
          \Drupal::state()->set('google_reviews_testimonials.next_exec', 
            time() + (12 * 60 * 60));
          $this->messenger->addMessage('Workflow started. ' .
            'Run cron to start checking for new reviews.');
        }
        break;
      case 'Run Cron':
        $this->cron->run();
        $this->messenger->addMessage('Cron ran. Check error logs if necessary.');
        break;

    }

  }

}