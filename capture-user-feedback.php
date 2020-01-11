<?php
/**
 * Plugin Name: Capture User Feedback
 * Description: Capture user feedback with simple yay or nay button and/or star ratings
 * Version: 1.0.0
 * Author: Marko Štimac
 * Author URI: https://marko-stimac.github.io/
 * Text Domain: capture-user-feedback
 * Domain Path: /languages
 */

namespace ms\UserFeedback;

defined('ABSPATH') || exit;

define('PLUGIN_PATH', plugin_dir_url(__FILE__));
define('PLUGIN_SLUG', plugin_basename(__DIR__));
define('PLUGIN_VERSION', '1.0.0');

require_once 'includes/feedback/class-backend.php';
require_once 'includes/feedback/class-frontend.php';
require_once 'includes/ratings/class-backend.php';
require_once 'includes/ratings/class-frontend.php';

// Feedback
new Feedback\Backend();
$post_feedback = new Feedback\Frontend();
add_shortcode('feedback', array($post_feedback, 'showComponent'));

// Ratings
new Ratings\Backend();
$ratings = new Ratings\Frontend();
add_shortcode('ratings', array($ratings, 'showComponent'));