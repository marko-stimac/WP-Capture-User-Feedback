# WP Capture User Feedback

WordPress plugin for receiving users feedback in two ways:  

## Post Feedback 

Shows thumbs up and thumbs down so user can vote on a post. If the votes is negative, simple contact form with only a message field is shown so that user can give his insights on how to better improve. You can set email address in settings->User Feedback, otherwise default admin address will be used.

To show this component use shortcode `[feedback]`

The plugin only captures feedback. If you want to query posts and order them by post feedback you can use this query: 

	$args = array(   
	    'meta_key' => 'rating_votes_count',
    	'orderby' => 'meta_value_num',
    	'order' => 'DESC'
	);   
	$query = new WP_Query($args);   

If you are feeling overwhelmed by too many columns in admin post listing you can turn those off in Screen Options.

## Post Ratings 

User can choose to give up to 5 golden stars to a post and thus rate it. 

To show this component use shortcode `[ratings]`
