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
class TestimonialCreationReviewLinkWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    $config = \Drupal::config('google_reviews_testimonials.settings');
    $summary = "";

    // Compute a proper summary that fits under 150 characters, since the
    // testimonial summary field has a 150 char limit.
    if(strlen($item->comment) < 150) {

      $summary = $item->comment;

    }
    else {
      $sumArr = explode(" ", $item->comment);

        foreach($sumArr as $str)
        {

            if((strlen($summary) + (strlen($str) + 1)) < 150)
            {
                $summary .= $str;
                $summary .= ' ';
            }
            else
            {
                break;
            }

        }
        
    }
    
    $testimonial = Node::create([
      'type' => 'testimonial',
      'title' => $item->displayName,
      'body' => [
        'summary' => $summary,
        'value' => $item->comment,
      ],
    ]);

    if($item->starRating < $config->get('starMin')) {

      $testimonial->setUnpublished();
      
    }

    $testimonial->save();

    // Now that the testimonial is created, create the linking entity and
    // unpublish if necessary
    $testGMBReview = TestimonialGMBReview::create([
      'id' => TestimonialGMBReview::generateID(
        $item->reviewID, 
        $testimonial->id()),
      'gid' => $item->reviewID,
      'starRating' => $item->starRating,
      'tid' => $testimonial->id(),
    ]);
    $testGMBReview->save();

  }

}