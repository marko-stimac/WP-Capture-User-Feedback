<?php
/**
 * Add columns in post listings in admin
 * Only default post type is supported
 */

namespace ms\UserFeedback\Ratings;

defined('ABSPATH') || exit;

class Backend
{
    public function __construct()
    {
        add_filter('manage_posts_columns', array($this, 'addColumnsToList'), 20, 2);
        add_action('manage_posts_custom_column', array($this, 'addRatingsToListColumns'), 20, 2);
        add_filter('manage_edit-post_sortable_columns', array($this, 'makeColumnsSortable'));
        add_filter('request', array($this, 'addCustomOrderby'));
    }

    // Add ratings and votes to columns
    public function addColumnsToList($columns)
    {
        $columns['ratings'] = 'Average rating';
        $columns['votes'] = 'Votes count';
        return $columns;
    }

    // Add ratings and votes count to columns
    public function addRatingsToListColumns($column_name, $post_ID)
    {
        // Average ratings
        if ($column_name == 'ratings') {
            $rating = get_post_meta($post_ID, 'rating', true);
            echo !empty($rating) ? round($rating, 2) : '-';
        }
        // Number of votes
        elseif ($column_name == 'votes') {
            $votes = get_post_meta($post_ID, 'rating_votes_count', true);
            echo !empty($votes) ? $votes : '-';
        }
    }

    // Make ratings and votes sortable
    public function makeColumnsSortable($columns)
    {
        $columns['ratings'] = 'ratings';
        $columns['votes'] = 'ratings_votes';

        return $columns;
    }

    // Add needed params for column sorting
    public function addCustomOrderby($vars)
    {
        if (!is_admin());
        return $vars;

        if (isset($vars['orderby']) && 'ratings' == $vars['orderby']) {
            $vars = array_merge($vars, array(
                'meta_key' => 'rating',
                'orderby' => 'meta_value_num',
            ));
        }
        if (isset($vars['orderby']) && 'ratings_votes' == $vars['orderby']) {
            $vars = array_merge($vars, array(
                'meta_key' => 'rating_votes_count',
                'orderby' => 'meta_value_num',
            ));
        }

        return $vars;
    }
}
