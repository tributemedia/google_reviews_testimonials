<?php

namespace Drupal\google_reviews_testimonials\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_reviews_testimonials\Entity\TestimonialGMBReview;
use Drupal\node\Entity\Node;

/**
 * Creates testimonials and links them to GMB reviews. Refer to README for
 * more details.
 * 
 * @QueueWorker(
 *  id = "google_reviews_testimonials_rlq",
 *  title = @Translation("Testimonial Creation and Review Link"),
 *  cron = {"time" = 30}
 * )
 */
class TestimonialCreationReviewLinkWorker extends QueueWorkerBase{

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    $config = \Drupal::config('google_reviews_testimonials.settings');
    $testimonial = Node::create([
      'type' => 'testimonial',
      'title' => $item->displayName,
      'body' => $item->comment,
    ]);
    $testimonial->save();

    $testGMBReview = TestimonialGMBReview::create([
      'id' => TestimonialGMBReview::generateID(
        $item->reviewID, 
        $testimonial->id()),
      'gid' => $item->reviewID,
      'starRating' => $item->starRating,
      'tid' => $testimonial->id(),
    ]);
    $testGMBReview->save();

    if($testGMBReview->getStarRating() < $config->get('starMin')) {

      $testimonial->setUnpublished();
      
    }
  }

}