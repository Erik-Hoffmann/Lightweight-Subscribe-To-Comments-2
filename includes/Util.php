<?php

class Util
{
    /**
     * replace placeholders with corresponding data
     *
     * @param  string $message
     * @param  object $data
     * @return string
     */
    public static function lstc_replace($message, $data)
    {
        $options = get_option('lstc__optionName');
        $message = str_replace('{title}', $data->title, $message);
        $message = str_replace('{link}', $data->link, $message);
        $message = str_replace('{comment_link}', $data->comment_link, $message);
        $message = str_replace('{author}', $data->author, $message);
        $temp = strip_tags($data->content);
        $length = empty($options['lstc_mail_co_length']) ? 155 : htmlspecialchars($options['lstc_mail_co_length']);

        if (!is_numeric($length)) {
            $length = 155;
        }

        if ($length) {
            if (strlen($temp) > $length) {
                $x = strpos($temp, ' ', $length);
                if ($x !== false) {
                    $temp = substr($temp, 0, $x) . '...';
                }
            }
        }
        return str_replace('{content}', $temp, $message);
    }

    /**
     * lstc_mail
     *
     * @param  string $to
     * @param  mixed $subject
     * @param  mixed $message
     * @return void
     */
    public static function lstc_mail($to, $subject, $message)
    {
        $options = get_option('lstc__optionName');
        $headers = "Content-type: text/html; charset=UTF-8\n";
        if (!empty($options['lstc_mail_sender_name']) && !empty($options['lstc_mail_sender_adress'])) {
            $headers .= 'From: "' . $options['lstc_mail_sender_name'] . '" <' . $options['lstc_mail_sender_adress'] . ">\n";
        }
        $message = wpautop($message);
        return wp_mail($to, $subject, $message, $headers);
    }

    public static function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

    /**
     * Add a box to debug data
     *
     * @param  mixed $data -- data to be printed
     * @return void
     */
    public static function debug_flash($data)
    {
        print_r('<div class="notice wrap"><pre>');
        print_r($data);
        print_r('</pre></div>');
    }

    public static function set_defaults()
    {
        $default_options['lstc_checkbox_activate'] = 'checkbox-active';
        $default_options['lstc_checkbox_label'] = __('Notify me when new comments are added.', 'lstc');
        $default_options['lstc_checkbox_default'] = 'checkbox-active';
        $default_options['lstc_mail_sender_name'] = get_option('blogname');
        $default_options['lstc_mail_sender_adress'] = get_option('admin_email');
        $default_options['lstc_mail_subject'] = sprintf(__('A new comment from %s on "%s"', 'lstc'), '{author}', '{title}');
        $default_options['lstc_mail_message'] =
            sprintf(__('Hi %s,', 'lstc'), '{name}') .
            "\n\n" .
            sprintf(__('%s has just written a new comment on "%s". Here is an excerpt:', 'lstc'), '{author}', '{title}') .
            "\n\n" .
            '{content}' .
            "\n\n" .
            sprintf(__('To read more, <a href="%s">click here</a>.', 'lstc'), '{comment_link}') .
            "\n\n" .
            __('Bye', 'lstc') .
            "\n\n" .
            sprintf(__('To unsubscribe from this notification service, <a href="%s">click here</a>.', 'lstc'), '{unsubscribe}');
        $default_options['lstc_mail_co_length'] = 155;
        $default_options['lstc_other_unsub_message'] = __('You successfully unsubscribed and will be redirected to the homepage in a few seconds.', 'lstc');

        update_option('lstc__optionName', $default_options);
    }
}
