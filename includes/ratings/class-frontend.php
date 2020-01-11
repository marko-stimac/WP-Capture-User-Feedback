<?php

/**
 * Show ratings component, register scripts and styles, save rating with AJAX
 */

namespace ms\UserFeedback\Ratings;

defined('ABSPATH') || exit;

class Frontend
{

    // True rating for post
    private $rating_real;
    // Averate rating for post
    private $rating_rounded;
    // Show averate rating as a number next to stars
    private $show_votes_count = true;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'registerScripts'));
        add_action('wp_ajax_save_rating', array($this, 'saveRating'));
        add_action('wp_ajax_nopriv_save_rating', array($this, 'saveRating'));
        add_action('wp_ajax_ratings_check_session', array($this, 'checkSession'));
        add_action('wp_ajax_nopriv_ratings_check_session', array($this, 'checkSession'));
    }

    public function registerScripts()
    {

        wp_register_style('ratings', PLUGIN_PATH . 'assets/css/ratings.css');
        wp_register_script('underscore', 'https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js', array('jquery'), false, true);
        wp_register_script('vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.17/vue.min.js', array('underscore', 'jquery'), false, true);
        wp_register_script('ratings', PLUGIN_PATH . 'assets/js/ratings.js', array('vue'), PLUGIN_VERSION, true);
        wp_localize_script(
            'ratings',
            'postratings',
            array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce("ratings_nonce"),
                'site_url' => trailingslashit(site_url()),
                'post_id' => get_the_ID(),
            )
        );

    }

    /**
     * Check session if user can vote
     */
    public function checkSession()
    {

        session_start();

        $post_id = $_POST['post_id'];

        // If Session for ratings doesn't exist create it
        if (empty($_SESSION['ratings_voted'])) {
            $_SESSION['ratings_voted'] = array();
        }

        if (!empty($_SESSION['ratings_voted']) && $_SESSION['ratings_voted'][$post_id] == true) {
            // User can't vote for current post
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('can_vote' => false));
        } else {
            // User can vote
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('can_vote' => true));
        }

        wp_die();

    }

    // Get rating for current post
    public function getRatingData()
    {

        global $post;

        // Get votes count
        $this->votes = get_post_meta($post->ID, 'rating_votes_count', true);

        // Get true rating value
        $rating_real = get_post_meta($post->ID, 'rating', true);
        $rating_real = !empty($rating_real) ? $rating_real : $this->rating_real;
        $this->rating_real = round($rating_real, 1);

        // Get averate rating
        $rating = get_post_meta($post->ID, 'rating', true);
        $rating = !empty($rating) ? $rating : $this->rating_rounded;
        $this->rating_rounded = round($rating);

    }

    // Save rating into database
    public function saveRating()
    {

        // Check security
        if (!wp_verify_nonce($_POST['nonce'], 'ratings_nonce')) {
            die('Security check');
        }

        global $post;

        $saved = array();

        // Get votes count
        $rating_votes_count = get_post_meta($_POST['post_id'], 'rating_votes_count', true);
        // If vote is first set it to 1, otherwise add 1
        $rating_votes_count = empty($rating_votes_count) ? 1 : $rating_votes_count + 1;
        // Save votes count
        $saved[] = update_post_meta($_POST['post_id'], 'rating_votes_count', $rating_votes_count);

        // Get a number of votes
        $rating_votes_sum = get_post_meta($_POST['post_id'], 'rating_votes_sum', true);

        // Save total votes sum
        $rating_new_sum = $rating_votes_sum + $_POST['rating'];
        $saved[] = update_post_meta($_POST['post_id'], 'rating_votes_sum', $rating_new_sum);

        // Save rating
        $rating_new_average = $rating_new_sum / $rating_votes_count;
        $saved[] = update_post_meta($_POST['post_id'], 'rating', $rating_new_average);

        session_start();
        $_SESSION['ratings_voted'][$_POST['post_id']] = true;

        wp_die();

    }

    public function showComponent()
    {

        wp_enqueue_style('ratings');
        wp_enqueue_script('ratings');

        // Get ratings for current post
        $this->getRatingData();

        ob_start();
        ?>

		<div id="star-app" v-cloak>
				<ratings value="<?php echo $this->rating_rounded; ?>"></ratings>
		</div>

		<template id="template-ratings">
			<div class="ratings">
				<label class="ratings__star" v-for="rating in ratings" :class="{'is-selected': ((value >= rating) && value != null), 'is-disabled': disabled}"
					@mouseover="starOver(rating)" @mouseout="starOut">
					<input class="ratings ratings__checkbox" type="radio" :name="name" :disabled="disabled" :id="id"
							:required="required" v-model="value" @click="set(rating)">
						★
				</label>
				<?php if ($this->show_votes_count && !empty($this->rating_real)): ?>
					<span class="ratings__count">(<?php echo $this->votes; ?>)</span>
				<?php endif;?>
				<div v-if="voted">
					<?php _e('Thank you, we appreciate your feedback.', 'capture-user-feedback');?>
				</div>
			</div>
		</template>

<?php
return ob_get_clean();
    }

}