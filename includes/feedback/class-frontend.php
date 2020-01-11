<?php
/**
 * Show component for user feedback, register scripts and styles and do AJAX requests for voting and sending message
 */

namespace ms\UserFeedback\Feedback;

defined('ABSPATH') || exit;

class Frontend
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'registerScripts'));
        add_action('plugins_loaded', array($this, 'load_text_domain'));
        add_action('wp_ajax_vote', array($this, 'vote'));
        add_action('wp_ajax_nopriv_vote', array($this, 'vote'));
        add_action('wp_ajax_check_session', array($this, 'checkSession'));
        add_action('wp_ajax_nopriv_check_session', array($this, 'checkSession'));
        add_action('wp_ajax_send_message', array($this, 'sendMessage'));
        add_action('wp_ajax_nopriv_send_message', array($this, 'sendMessage'));
    }

    /**
     * Register scripts and styles
     */
    public function registerScripts()
    {
        wp_enqueue_style('feedback', PLUGIN_PATH . 'assets/css/feedback.css');
        wp_register_script('vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.17/vue.min.js', false, null, true);
        wp_register_script('feedback', PLUGIN_PATH . 'assets/js/feedback.js', array('vue', 'jquery'), PLUGIN_VERSION, true);
        wp_localize_script(
            'feedback',
            'userfeedback',
            array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce("form_nonce"),
                'page_id' => get_the_ID(),
                'page_title' => get_the_title(),
                'page_url' => get_permalink(),
            )
        );
    }

    /**
     * Load text domain
     */
    public function load_text_domain()
    {
        load_plugin_textdomain('capture-user-feedback', false, PLUGIN_SLUG . '/languages');
    }

    /**
     * Get previous vote score for page and raise negative or positive, depending on reacting
     */
    public function vote()
    {

        // Get all votes for current action
        $current_reaction = get_post_meta($_POST['page_id'], $_POST['meta_key'], true);
        // If it is first vote set it to 1, otherwise raise it by 1
        $updated_reaction = $current_reaction == false ? 1 : $current_reaction + 1;

        header('Content-Type: application/json; charset=UTF-8');
        // Save value into database
        if (update_post_meta($_POST['page_id'], $_POST['meta_key'], $updated_reaction)) {
            // Save session
            $this->saveSession($_POST['page_id']);
            echo json_encode(array('vote_passed' => true));
        } else {
            $data = array('type' => 'error', 'message' => 'You are not allowed to vote');
            header('HTTP/1.1 400 Bad Request');
            echo json_encode($data);
        }

        wp_die();
    }

    /**
     * Check if user has already voted for current post
     */
    public function checkSession()
    {
        // Check if there is post ID in session, return 0 if user has already voted, otherwise return 1
        echo (
            !empty($_SESSION['userfeedback']) &&
            in_array((int) $_POST['page_id'], $_SESSION['userfeedback'])
        )
        ? 0 : 1;
        wp_die();
    }

    /**
     * Save action to session
     */
    public function saveSession($page_id)
    {
        session_start();
        return ($_SESSION['userfeedback'][] = (int) $page_id);
    }

    /**
     * Parse URL and return domain
     */
    public function getDomainFromUrl($url)
    {
        $parsed_url = wp_parse_url($url);
        return $parsed_url['host'];
    }

    /**
     * Send email
     */
    public function sendMessage()
    {

        $recipients_email = esc_attr(get_option('userfeedback_email'));
        // If email is not set send it to admin address
        if (empty($recipients_email)) {
            $recipients_email = get_option('admin_email');
        }

        $subject = __('User feedback', 'capture-user-feedback') . ': ' . $this->getDomainFromUrl($_POST['url']);
        $body =
            __('User has posted feedback for', 'capture-user-feedback') . ' <a href="' . $_POST['url'] . '">' . $_POST['title'] . '</a>:<br>' .
            $_POST['message'];

        $mail_sent = wp_mail($recipients_email, $subject, $body, $headers);
        if (!$mail_sent) {
            $data = array('type' => 'error', 'message' => 'Message can not be send');
            header('HTTP/1.1 400 Bad Request');
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($data);
        }

        wp_die();
    }

    /**
     * Show user component
     */
    public function showComponent()
    {
        wp_enqueue_script('feedback');

        ob_start();?>

<div id="js-userfeedback" class="userfeedback" v-cloak>

	<div v-if="!wantsToVote">
		<p>
			<?php _e('Did you find the information useful?', 'capture-user-feedback');?>
		</p>
		<div class="userfeedback__buttons">
			<button @click="vote('positive_feedback')" id="js-feedback-btn-positive" class="btn" aria-label="<?php _e('Vote positive', 'capture-user-feedback');?>">
				<svg style="width:18px;height:18px" viewBox="0 0 24 24" aria-hidden="true">
					<path d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z" />
				</svg>
			</button>
			<button @click="vote('negative_feedback')" id="js-feedback-btn-negative" class="btn" aria-label="<?php _e('Vote negative', 'capture-user-feedback');?>">
				<svg style="width:18px;height:18px" viewBox="0 0 24 24" aria-hidden="true">
					<path d="M19,15H23V3H19M15,3H6C5.17,3 4.46,3.5 4.16,4.22L1.14,11.27C1.05,11.5 1,11.74 1,12V14A2,2 0 0,0 3,16H9.31L8.36,20.57C8.34,20.67 8.33,20.77 8.33,20.88C8.33,21.3 8.5,21.67 8.77,21.94L9.83,23L16.41,16.41C16.78,16.05 17,15.55 17,15V5C17,3.89 16.1,3 15,3Z" />
				</svg>
			</button>
		</div>
	</div>

	<p v-if="!canVote && wantsToVote">
		<?php _e('Sorry, only one vote is possible and you have already voted for this post.', 'capture-user-feedback');?>
	</p>
	<p v-if="canVote && wantsToVote && !negativeReaction || messageSent">
		<?php _e('Thank you, we appreciate your feedback.', 'capture-user-feedback');?>
	</p>

	<form v-if="canVote && wantsToVote && negativeReaction && !messageSent" id="js-userfeedback__form" method="post">
		<label>
			<?php _e('What could we improve to make this page better?', 'capture-user-feedback');?></label>
		<textarea required class="form-control" rows="5" v-model="feedbackMessage"></textarea>
		<button type="submit" class="btn" @click="sendMessage" :disabled="!feedbackMessage.length">
			<?php _e('Send', 'capture-user-feedback');?>
		</button>
	</form>

</div>


<?php
return ob_get_clean();
    }
}