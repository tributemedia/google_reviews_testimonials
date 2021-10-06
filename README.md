# Google Reviews Testimonials

## Summary

This Drupal 9 module provides the ability for the system to download reviews from Google My Business (GMB) and convert them to Tribute Media's testimonial entities.

  

### Features

- Automatically downloads reviews from GMB API and converts them to testimonials.

- Minimum review star threshold can be configured to have the module filter out reviews based on their amount of stars.

- Review status page to view queue sizes and other module information.

  

## Installation & Configuration

Installation steps TBD.

  

After installation, go to Configuration -> Web services -> GMB Service Connection Settings. Provide the service key (obtained from the Google admin console), and subject (email of a user within the organization authorized to use the service account). Then, click the save button and the disabled field for 'Account' should now be filled in with a value.

  

Next, navigate to Configuration -> Web services -> GMB Location Settings. Provide the name of the business **as shown in Google My Business** for 'Location Name' field. Also, change the 'Star Minimum' if you do not like the default value of 3. Any review that has the amount of stars specified in this field, or greater, will be downloaded and published as a testimonial. Once this has been completed, click the save button, and now the 'Location ID' field should have a value.

  

## Usage

**Make sure the steps outlined in the configuration section above have been completed!** Once that is done, then all you have to do is simply wait until the next Cron run for the first automatic download of reviews. If you do not want to wait, you may manually start a Cron run by going to Configuration -> System -> Cron. If your business has a lot of reviews, they may not all get downloaded and created on the first run. Feel free to run Cron again until all reviews that meet the minimum star threshold are downloaded and created into testmonials.

If you want to manually start a download, you can go to Configuration -> Web services -> GRT Status. From this page you'll be able to see the status of current queues, and manually queue work. To run the work, simply run Cron.

### Cron Workflow

This is a high-level, but technically detailed breakdown of the entire Cron workflow pipeline, from review download to testimonial creation. If you're seeking information on how, in particular, the Cron workflow of the module is working, then read on.

#### Trigger Review Check

**Queue ID:** `google_reviews_testimonials_trcq` (trcq = Trigger Review Check Queue)

**Run Time:** 12 hours

**Parameters:** *None*

The first step in the workflow. This is a worker that runs every 12 hours, and its one job is simple: Queue a job to the worker for the next step, checking for new reviews. No value is provided for `pageToken` when a job is queued by this worker, which will result in the review checker getting the first page of reviews on its first run. Naturally, this is what we always want on our first run.

#### New Review Check

**Queue ID:** `google_reviews_testimonials_rcq` (rcq = Review Check Queue)

**Run Time:** 30 minutes

**Parameters:**

`pageToken` *(string)* - Pagination value that must be provided to the API to retrieve the next set of reviews, if applicable. **OPTIONAL.**

Next, a list of **20** GMB reviews for the configured location is queried from the API. If there is no `TestimonialGMBReviewEntity` with a reference to the ID of the review, then a new job is created for the worker of the next step, to create new testimonials. 

This step is repeated for each review ID retrieved from the API. **If there are more reviews than the 20 retrieved in the first query,** a new job is created for the worker of this queue, using the `pageToken` provided by the API as the parameter. **Since this is a separate job, it will not be processed until the next run of Cron.** This step, naturally, repeats until every review has been checked from the API, no matter how many paginations are required.

#### Testimonial Creation & Review Link

**Queue ID:** `google_reviews_testimonials_rlq` (rlq = Review Link Queue)

**Run Time:** 30 minutes

**Parameters:**

`reviewID` *(string)* - ID of the review. **REQUIRED.**

`displayName` *(string)* - Google display name of the reviewer. **REQUIRED.**

`starRating` *(int)* - Star rating. **REQUIRED.**

`comment` *(string)* - The review (comment, as its called in GMB). **REQUIRED.**

The last step in this workflow. It starts by creating a new testimonial with the details specified above (note that there is no location of the reviewer, that is not provided by the API). The comment is trimmed to less than 150 characters for the summary of the testimonial, if necessary. Otherwise, the comment is used for both the summary and review fields of the testimonial.

Once the testimonial is created, a `TestimonialGMBReviewEntity` is also created, to link the association of the testimonial and GMB review.

Finally, based on the star count of the review and the minimum star threshold in the module settings, the testimonial may or may not be published. If the star rating is equal to or greater than the module setting, the testimonial is published. Otherwise, it is unpublished.
