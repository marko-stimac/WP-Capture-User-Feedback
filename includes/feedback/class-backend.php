<?php
/**
 * Register admin settings page and place columns for analytics on post listings
 */

namespace ms\UserFeedback\Feedback;

defined('ABSPATH') || exit;

class Backend
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'createSettingsPage'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_filter('plugin_row_meta', array($this, 'modify_plugin_meta'), 10, 2);
        add_action('manage_posts_columns', array($this, 'addColumnsToList'), 10, 2);
        add_action('manage_pages_columns', array($this, 'addColumnsToList'), 10, 2);
        add_action('manage_posts_custom_column', array($this, 'addRatingsToListColumns'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'addRatingsToListColumns'), 10, 2);
        add_filter('manage_edit-post_sortable_columns', array($this, 'makeColumnsSortable'), 10, 1);
        add_filter('manage_edit-page_sortable_columns', array($this, 'makeColumnsSortable'), 10, 1);
    }

    /**
     * Create options page
     */
    public function createSettingsPage()
    {
        add_options_page(
            'User Feedback',
            'User Feedback',
            'manage_options',
            'userfeedback.php',
            array($this, 'pageSettingsCallback')
        );
    }

    /**
     * Register plugin settings
     */
    public function registerSettings()
    {
        register_setting('postfeedback-settings-group', 'postfeedback_email');
    }

    /**
     * Add link to readme file on installed plugin listing
     */
    public function modify_plugin_meta($links_array, $file)
    {
        if (strpos($file, 'capture-user-feedback.php') !== false) {
            $links_array[] = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=userfeedback.php')) . '">Settings</a>';
			$links_array[] = '<a href="' . PLUGIN_PATH . 'readme.md' . '" target="_blank">How to use</a>';
        }
        return $links_array;
    }

    /**
     * Add column for ratings to post lists
     */
    public function addColumnsToList($columns)
    {
        $columns['positive_feedback'] = __('Positive feedback', 'capture-user-feedback');
        $columns['negative_feedback'] = __('Negative feedback', 'capture-user-feedback');
        return $columns;
    }

    /**
     * Add ratings to posts list
     */
    public function addRatingsToListColumns($column, $post_id)
    {
        if ($column == 'positive_feedback') {
            $value = get_post_meta($post_id, 'positive_feedback', true);
            echo $value ? $value : '-';
        } elseif ($column == 'negative_feedback') {
            $value = get_post_meta($post_id, 'negative_feedback', true);
            echo $value ? $value : '-';
        }
    }

    /**
     * Make columns sortable
     */
    public function makeColumnsSortable($sortable_columns)
    {
        $sortable_columns['positive_feedback'] = 'positive_feedback';
        $sortable_columns['negative_feedback'] = 'negative_feedback';
        return $sortable_columns;
    }

    /**
     * Show options form
     */
    public function pageSettingsCallback()
    {
        ?>
<div class="wrap">
	<h1>User Feedback settings</h1>
	<form method="post" action="options.php">
		<?php settings_fields('postfeedback-settings-group');?>
		<?php do_settings_sections('postfeedback-settings-group');?>
		<p>Where do you want to receive email when user sends message through feedback component?</p>
		<table class="form-table">
			<tr>
				<th scope="row">Email:</th>
				<td><input type="text" name="postfeedback_email" value="<?php echo esc_attr(get_option('postfeedback_email')); ?>" /></td>
			</tr>
		</table>
		<?php submit_button();?>
	</form>
</div>
<?php
}
}