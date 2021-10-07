<?php

namespace Drupal\google_reviews_testimonials\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Testimonial Google My Business Review. Provides a link between Tribute Media
 * Testimonials and Google My Business API information.
 * 
 * @ConfigEntityType(
 *   id = "testimonial_gmb_review",
 *   label = @Translation("Testimonial Google My Business Review"),
 *   config_prefix = "gmb_review_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "gid",
 *     "id",
 *     "starRating",
 *     "tid"
 *   }
 * )
 */
class TestimonialGMBReview extends ConfigEntityBase 
  implements TestimonialGMBReviewInterface {

  /**
   * The ID of the Google My Business review.
   * 
   * @var string
   */
  protected $gid;

  /**
   * The ID of the entity.
   * 
   * @var string
   */
  protected $id;

  /**
   * The rating of this review.
   * 
   * @var integer
   */
  protected $starRating;

  /**
   * The ID of the testimonial. Also the ID of this entity.
   * 
   * @var string
   */
  protected $tid;

  /**
   * Returns a unique ID for a new TestimonialGMBReview entity.
   * 
   * @param string $gid
   *  The GMB ID to be used.
   * @param string $tid
   *  The testimonial ID to be used.
   * @return string
   */
  public static function generateID($gid, $tid) {

    return hash('sha256', $gid . $tid);

  }

  /**
   * {@inheritdoc}
   */
  public function getGID() {

    return $this->gid;

  }

  /**
   * {@inheritdoc}
   */
  public function getID() {

    return $this->id;

  }

  /**
   * {@inheritdoc}
   */
  public function getStarRating() {

    return $this->starRating;

  }

  /**
   * {@inheritdoc}
   */
  public function getTID() {

    return $this->tid;

  }

  /**
   * {@inheritdoc}
   */
  public function setGID($gid) {

    $this->gid = $gid;

  }

  /**
   * {@inheritdoc}
   */
  public function setStarRating($starRating) {

    $this->starRating = $starRating;

  }

  /**
   * {@inheritdoc}
   */
  public function setTID($tid) {

    $this->tid = $tid;

  }
}