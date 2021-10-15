<?php

namespace Drupal\google_reviews_testimonials\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface TestimonialGMBReviewInterface extends ConfigEntityInterface {
  
  /**
   * Returns the GMB Review ID.
   */
  public function getGID();

  /**
   * Returns the entity ID.
   */
  public function getID();

  /**
   * Returns the star rating of this review.
   */
  public function getStarRating();

  /**
   * Returns the Testimonial ID of this entity.
   */
  public function getTID();

  /**
   * Sets the GMB ID of this entity.
   * 
   * @param string $gid
   *  The ID of the GMB review associated with the testimonial.
   */
  public function setGID($gid);

  /**
   * Sets the star rating for the review.
   * 
   * @param float $starRating
   *  The star rating of this review.
   */
  public function setStarRating($starRating);

  /**
   * Sets the Testimonial ID of this entity.
   * 
   * @param string $tid
   *  The ID of the testimonial the GMB review is associated with.
   */
  public function setTID($tid);

}