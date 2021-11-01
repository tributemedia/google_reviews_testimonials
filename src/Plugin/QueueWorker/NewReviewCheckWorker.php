<?php

namespace Drupal\google_reviews_testimonials\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_reviews_testimonials\GMBAPIConnection;
use Drupal\google_reviews_testimonials\Entity\TestimonialGMBReview;
use Drupal\node\Entity\Node;

/**
 * Checks for new reviews and queues new a new testimonial creation job for
 * each new review found.
 * 
 * @QueueWorker(
 *  id = "google_reviews_testimonials_rcq",
 *  title = @Translation("New Review Check"),
 *  cron = {"time" = 30}
 * )
 */
class NewReviewCheckWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    $config = \Drupal::config('google_reviews_testimonials.settings');
    $conn = new GMBAPIConnection();
    $queueFactory = \Drupal::service('queue');
    $rlQueue = $queueFactory->get('google_reviews_testimonials_rlq');
    $response = '';

    if(isset($item->pageToken)) {

      $response = $conn->getReviews($item->pageToken);

    }
    else {

      $response = $conn->getReviews();

    }

    foreach($response->reviews as $review) {

      // First, check to see if the review already has a testimonial
      $query = \Drupal::entityQuery('testimonial_gmb_review')
        ->condition('gid', $review->reviewId);
      $numReviews = $query->count()->execute();

      // Assuming there were no results when checking for an entity linked 
      // to the review, then we create a job for the next worker to create
      // the testimonial and linking entity.
      if($numReviews === 0) {

        $rlqArg = new \stdClass();
        $rlqArg->reviewID = $review->reviewId;
        
        if(isset($review->reviewer)) {

          $rlqArg->displayName = $review->reviewer->displayName;

        }
        else {

          $rlqArg->displayName = 'Anonymous';

        }

        if(isset($review->comment)) {

          $rlqArg->comment = $review->comment;

        }
        else {

          $rlqArg->comment = '';

        }

        // Case values are defined constants in the GMB API
        switch($review->starRating) {

          case 'ONE':
            $rlqArg->starRating = 1;
            break;
          case 'TWO':
            $rlqArg->starRating = 2;
            break;
          case 'THREE':
            $rlqArg->starRating = 3;
            break;
          case 'FOUR':
            $rlqArg->starRating = 4;
            break;
          default:
          case 'FIVE':
            $rlqArg->starRating = 5;
            break;
            
        }

        $rlQueue->createItem($rlqArg);
      
      }

    }

    // If more results need to be reviewed, create another job for this 
    // worker to check the next page of reviews.
    if(isset($response->pageToken)) {

      $myQueue = $queueFactory->get('google_reviews_testimonials_rcq');
      $rcqArg = new \stdClass();
      $rcqArg->pageToken = $response->pageToken;
      
      $myQueue->createItem($rcqArg);

    }

  }
}