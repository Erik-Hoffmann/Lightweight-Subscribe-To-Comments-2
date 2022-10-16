<?php

/*
Plugin Name: Lightweight Subscribe To Comments 2
Description: Easiest and most lightweight plugin to let visitors subscribe to comments and get email notifications. A fork of Isabel Castillos comment-notifier-no-spammers https://isabelcastillo.com/free-plugins/lightweight-subscribe-comments.
Version: 2.0.0
Author: Erik Hoffmann
License: GPL2
Text Domain: lstc
Domain Path: languages
This file is part of Lightweight Subscribe To Comments 2.
Lightweight Subscribe To Comments 2 is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Lightweight Subscribe To Comments 2 is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Lightweight Subscribe To Comments. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/includes/Activator.php';
require_once dirname(__FILE__) . '/includes/Settings.php';
require_once dirname(__FILE__) . '/includes/Manage.php';
require_once dirname(__FILE__) . '/includes/Util.php';

register_activation_hook(__FILE__, array('Activator', 'activate'));

add_action('rest_api_init', function () {
    register_rest_route('lstc', '/unsubscribe/', array(
        'methods'  => 'GET',
        'callback' => 'lstc_unsubscribe',
        'permission_callback' => '__return_true' // public endpoint
    ));
});
add_action('init', 'lstc_init');

/**
 * initialize
 *
 * @return void
 */
function lstc_init()
{
    $options = get_option('lstc__optionName');

    $debug = true;

    if (is_admin()) {
        if ($debug) Util::debug_flash($options);
        $settingsPage = new LstcSettings();
        $managePage = new LstcManage();
    }

    add_filter('comment_form_submit_field', 'lstc_checkbox', 9999); // add checkbox
    add_action('wp_set_comment_status', 'lstc_set_comment_status', 10, 2); // subscribe and notify
    add_action('comment_post', 'lstc_comment_post', 10, 2);
}

/**
 * Create the markup for the checkbox
 *
 * @return string -- checkbox html markup
 */
function lstc_create_checkbox()
{
    $html = '';
    $options = get_option('lstc__optionName');
    if (!empty($options['lstc_checkbox_activate'])) {
        $html .= '<p id="lstc-comment-subscription" class="cnns-comment-subscription"><input type="checkbox" value="1" name="lstc_subscribe" id="lstc_subscribe" '
            . (!empty($options['lstc_checkbox_default']) ? 'checked' : '') . '/>';
        $html .= '<label id="cnns-label" class="lstc-label" for="lstc_subscribe">' . esc_html($options['lstc_checkbox_label']) . '</label></p>';
    }
    return $html;
}

/**
 * Filter function to add the checkbox below the submit button
 * TODO: add option to display the checkbox without the use of a hook
 *
 * @param  string $comment_submit
 * @return string
 */
function lstc_checkbox(string $comment_submit)
{
    return $comment_submit . lstc_create_checkbox();
}

/**
 * Trigger notification workflow if a comment is approved
 * Fires after every status change of a comment
 *
 * @param  int $comment_id
 * @param  string $status
 * @return void
 */
function lstc_set_comment_status(int $comment_id, string $status)
{
    Util::write_log("A comment status changed! | comment_id: " . $comment_id . " | status: " . $status);
    // get original comment info
    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }
    $post_id = $comment->comment_post_ID;
    $email = strtolower(trim($comment->comment_author_email));
    $name = $comment->comment_author;

    // When a comment is approved later, notify the subscribers, and subscribe this comment author
    if ('approve' === $status) {
        lstc_notify($comment_id);
        lstc_subscribe_later($post_id, $email, $name, $comment_id);
    }
}

/**
 * Build up the notification
 * Fires after a comment switched the status to approved
 *
 * @param  int $comment_id
 * @return void
 */
function lstc_notify(int $comment_id)
{
    global $wpdb;
    $options = get_option('lstc__optionName');
    $comment = get_comment($comment_id);

    if ('trackback' == $comment->comment_type || 'pingback' == $comment->comment_type) {
        return;
    }

    $post_id = $comment->comment_post_ID;
    if (empty($post_id)) {
        return;
    }
    $email = strtolower(trim($comment->comment_author_email));

    $subscriptions = $wpdb->get_results(
        $wpdb->prepare(
            "select * from " . $wpdb->prefix . "comment_notifier where post_id=%d and email<>%s",
            $post_id,
            $email
        )
    );

    if (!$subscriptions) {
        return;
    }


    // Fill the message body with same for all data.
    $post = get_post($post_id);
    if (empty($post)) {
        return;
    }

    $data = new stdClass();
    $data->post_id = $post_id;
    $data->title = $post->post_title;
    $data->link = get_permalink($post_id);
    $data->comment_link = get_comment_link($comment_id);
    $comment = get_comment($comment_id);
    $data->author = $comment->comment_author;
    $data->content = $comment->comment_content;
    $message = Util::lstc_replace($options['lstc_mail_message'], $data);

    // Fill the message subject with same for all data.
    $subject = $options['lstc_mail_subject'];
    $subject = str_replace('{title}', $post->post_title, $subject);
    $subject = str_replace('{author}', $comment->comment_author, $subject);

    $url = get_option('home') . '/wp-json/lstc/unsubscribe/?';

    if (!empty($options['lstc_mail_copy'])) {
        $fake = new stdClass();
        $fake->token = 'fake';
        $fake->id = 0;
        $fake->email = $options['lstc_mail_copy'];
        $fake->name = 'Test subscriber';
        $subscriptions[] = $fake;
    }

    $idx = 0;
    $ok = 0;
    foreach ($subscriptions as $subscription) {
        $idx++;
        $m = apply_filters('lstc_notify_message', $message, $comment, $subscription);
        $m = str_replace('{name}', $subscription->name, $m);
        $m = str_replace('{unsubscribe}', $url . 'lstc_id=' . $subscription->id . '&lstc_t=' . $subscription->token, $m);
        $s = $subject;
        $s = str_replace('{name}', $subscription->name, $s);
        if (Util::lstc_mail($subscription->email, $s, $m)) {
            Util::write_log("mail send | to: " . $subscription->email . " | subject: " . $s);
            $ok++;
        }
    }
}

/**
 * lstc_subscribe
 *
 * @param  int $post_id
 * @param  string $email
 * @param  string $name
 * @return void
 */
function lstc_subscribe(int $post_id, string $email, string $name)
{
    global $wpdb;

    // Check if user is already subscribed to this post
    $subscribed = $wpdb->get_var(
        $wpdb->prepare(
            "select count(*) from " . $wpdb->prefix . "comment_notifier where post_id=%d and email=%s",
            $post_id,
            $email
        )
    );

    // Insert only if a valid email is supplied and is not subscribed yet
    if ($subscribed > 0 || !is_email($email)) {
        return;
    }

    $token = md5(rand()); // The random token for unsubscription
    $res = $wpdb->insert($wpdb->prefix . "comment_notifier", array(
        'post_id'    => $post_id,
        'email'        => $email,
        'name'        => $name,
        'token'        => $token
    ));
}

/**
 * Subscribe the comment author to the post after the comment was approved
 *
 * @param  int $post_id
 * @param  string $email
 * @param  string $name
 * @param  int $comment_id
 * @return void
 */
function lstc_subscribe_later(int $post_id, string $email, string $name, int $comment_id)
{
    global $wpdb;

    // Check if user is already subscribed to this post
    $subscribed = $wpdb->get_var(
        $wpdb->prepare(
            "select count(*) from " . $wpdb->prefix . "comment_notifier where post_id=%d and email=%s",
            $post_id,
            $email
        )
    );

    if ($subscribed > 0) {
        return;
    }

    // Did the comment author check the box to subscribe?
    if ($comment_id) {
        if (get_comment_meta($comment_id, 'lstc_subscribe', true)) {

            // The random token for unsubscription
            $token = md5(rand());
            $res = $wpdb->insert($wpdb->prefix . "comment_notifier", array(
                'post_id' => $post_id,
                'email' => $email,
                'name' => $name,
                'token' => $token
            ));

            delete_comment_meta($comment_id, 'lstc_subscribe');
        }
    }
}

/**
 * Subscribe the comment author and notify all subscribers when comment is posted with approved status
 * If the comment goes to moderation, comment meta is added
 *
 * status:
 * 0 - in moderation
 * 1 - approved
 * spam - spam
 *
 * @param  int $comment_id -- database id of the comment
 * @param  int|string $status
 * @return void
 */
function lstc_comment_post(int $comment_id, $status)
{
    Util::write_log("A comment was inserted to database | comment_id: " . $comment_id . "| status: " . $status);
    Util::write_log($_POST);
    $comment = get_comment($comment_id);
    $name = $comment->comment_author;
    $email = strtolower(trim($comment->comment_author_email));
    $post_id = $comment->comment_post_ID;

    // Only subscribe if comment is approved; skip those in moderation.

    // If comment is approved automatically, notify subscribers
    if (1 === $status) {
        lstc_notify($comment_id);

        // If comment author subscribed, subscribe author since the comment is automatically approved.
        if (isset($_POST['lstc_subscribe'])) {
            lstc_subscribe($post_id, $email, $name);
        }
    }

    // If comment goes to moderation, and if comment author subscribed,
    // add comment meta key for pending subscription.
    if ((0 === $status) && isset($_POST['lstc_subscribe'])) {
        add_comment_meta($comment_id, 'lstc_subscribe', true, true);
    }
}

/**
 * handle unsubscription
 *
 * @param  object $args
 * @return void
 */
function lstc_unsubscribe(object $args)
{
    if (empty($_GET['lstc_id'])) {
        return;
    }

    $token = sanitize_text_field($_GET['lstc_t']);
    $id = sanitize_text_field($_GET['lstc_id']);

    global $wpdb;
    $wpdb->query($wpdb->prepare("delete from " . $wpdb->prefix . "comment_notifier where id=%d and token=%s", $id, $token));
    $options = get_option('lstc__optionName');

    $unsubscribe_url = empty($options['lstc_other_unsub_altpage']) ? '' : esc_url_raw($options['lstc_other_unsub_altpage']);

    if ($unsubscribe_url) {
        header('Location: ' . $unsubscribe_url);
    } else {
        $thankyou = empty($options['lstc_other_unsub_message']) ?
            __('Your subscription has been removed. You\'ll be redirect to the home page within few seconds.', 'lstc') :
            htmlspecialchars($options['lstc_other_unsub_message']);

        echo $thankyou;
        header("refresh:5;url=/");
    }

    flush();

    die();
}
