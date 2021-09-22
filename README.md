# Google Reviews Testimonials
## Summary
This Drupal 9 module provides the ability for the system to download reviews from Google My Business (GMB) and convert them to Tribute Media's testimonial entities.

### Features
- Automatically downloads reviews from GMB API and converts them to testimonials.
- Minimum review star threshold can be configured to have the module filter out reviews based on their amount of stars.
- Review status page to view queue sizes and other module information.

## Installation & Configuration
Installation steps TBD.

After installation, go to Configuration -> Web services -> Google reviews testimonials -> Service connection. Provide the service key (obtained from the Google admin console), and subject (email of a user within the organization authorized to use the service account). Then, click the save button and the disabled field for 'Account' should now be filled in with a value if you go back to the previous screen.

Next, navigate to Configuration -> Web services -> Google reviews testimonials -> GMB location config. Provide the name of the business **as shown in Google My Business** for 'Location Name' field. Also, change the 'Star Minimum' if you do not like the default value of 3. Any review that has the amount of stars specified in this field, or greater, will be downloaded and published as a testimonial. Once this has been completed, click the save button, and now the 'Location ID' field should have a value if you go back to the previous screen.

## Usage
**Make sure the steps outlined in the configuration section above have been completed!** Once that is done, then all you have to do is simply wait until the next Cron run for the first automatic download of reviews. If you do not want to wait, you may manually start a Cron run by going to Configuration -> System -> Cron. If your business has a lot of reviews, they may not all get downloaded and created on the first run. Feel free to run Cron again until all reviews that meet the minimum star threshold are downloaded and created into testmonials.