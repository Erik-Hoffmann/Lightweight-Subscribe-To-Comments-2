<?php
require_once dirname(__FILE__) . '/Util.php';
class LstcSettings
{
    private $lstc__options;

    private $lstc__checkboxLabel;
    private $lstc__mailSenderName;
    private $lstc__mailSenderAdress;
    private $lstc__mailSubject;
    private $lstc__mailMessage;
    private $lstc__mailCoLength;
    private $lstc__mailCopy;
    private $lstc__mailTest;
    private $lstc__otherUnsubAltpage;
    private $lstc__otherUnsubMessage;


    public function __construct()
    {
        add_action('admin_menu', array($this, 'lstc__addPluginPage'));
        add_action('admin_init', array($this, 'lstc__pageInit'));
    }

    public function lstc__addPluginPage()
    {
        add_menu_page(
            'Lightweight Subscribe To Comments', // page_title
            'Lightweight Subscribe To Comments', // menu_title
            'manage_options', // capability
            'lightweight-subscribe-to-comments-2', // menu_slug
            array($this, 'lstc__createAdminPage'), // function
            plugins_url('lightweight-subscribe-to-comments-2/icon.svg'), // icon_url
            80 // position
        );
    }

    public function lstc__createAdminPage()
    {
        wp_enqueue_style('admin-styles', plugins_url('lightweight-subscribe-to-comments-2/settings.css'));
        $this->lstc__options = get_option('lstc__optionName');
        $test = empty($this->lstc__options['lstc_mail_test']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_test']);

        if (!empty($_POST['_wpnonce'])) {
            if (wp_verify_nonce($_POST['_wpnonce'], 'lstc-save')) {
                $options = stripslashes_deep($_POST['lstc__optionName']);
                $sane_options = $this->lstc__sanitize($options);
                update_option('lstc__optionName', $sane_options);

                if (isset($_POST['savethankyou'])) {
                    if (!empty($_POST['lstc__optionName']['lstc_mail_test'])) {
                        $test = sanitize_email($_POST['lstc__optionName']['lstc_mail_test']);
                    }
                    $message_pre = empty($options['lstc_mail_message']) ? '' : $options['lstc_mail_message']; // TODO: maybe send ty message instead
                    $lstc_data = new stdClass();
                    $lstc_data->author = __('Author', 'lstc');
                    $lstc_data->link = get_option('home');
                    $lstc_data->comment_link = get_option('home');
                    $lstc_data->title = __('The post title', 'lstc');
                    $lstc_data->content = __('This is a long comment. Be a yardstick of quality. Some people are not used to an environment where excellence is expected.', 'lstc');
                    $message = Util::lstc_replace($message_pre, $lstc_data);
                    $subject = $options['lstc_mail_subject']; // TODO: maybe send ty subject instead
                    $subject = str_replace('{title}', $lstc_data->title, $subject);
                    $subject = str_replace('{author}', $lstc_data->author, $subject);
                    Util::lstc_mail($test, $subject, $message);
                    add_settings_error('general', 'settings_saved_mail', __("All settings were saved and a test email was sent", 'lstc'), 'success');
                } else {
                    add_settings_error('general', 'settings_saved', __("All settings were saved", 'lstc'), 'success');
                }
            } elseif (wp_verify_nonce($_POST['_wpnonce'], 'restore-defaults')) {
                Util::set_defaults();
                add_settings_error('general', 'settings_restored', __("All settings were restored to default", 'lstc'), 'success');
            }
        }
        $this->lstc__options = get_option('lstc__optionName');

        $this->lstc__checkboxLabel = empty($this->lstc__options['lstc_checkbox_label']) ? '' : htmlspecialchars($this->lstc__options['lstc_checkbox_label']);
        $this->lstc__mailSenderName = empty($this->lstc__options['lstc_mail_sender_name']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_sender_name']);
        $this->lstc__mailSenderAdress = empty($this->lstc__options['lstc_mail_sender_adress']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_sender_adress']);
        $this->lstc__mailSubject = empty($this->lstc__options['lstc_mail_subject']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_subject']);
        $this->lstc__mailMessage = empty($this->lstc__options['lstc_mail_message']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_message']);
        $this->lstc__mailCoLength = empty($this->lstc__options['lstc_mail_co_length']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_co_length']);
        $this->lstc__mailCopy = empty($this->lstc__options['lstc_mail_copy']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_copy']);
        $this->lstc__mailTest = empty($this->lstc__options['lstc_mail_test']) ? '' : htmlspecialchars($this->lstc__options['lstc_mail_test']);
        $this->lstc__otherUnsubAltpage = empty($this->lstc__options['lstc_other_unsub_altpage']) ? '' : htmlspecialchars($this->lstc__options['lstc_other_unsub_altpage']);
        $this->lstc__otherUnsubMessage = empty($this->lstc__options['lstc_other_unsub_message']) ? '' : htmlspecialchars($this->lstc__options['lstc_other_unsub_message']);
?>


        <script type="text/javascript">
            function lstcPreview() {
                var s = document.getElementById("lstc_mail_subject-0").value;
                var m = document.getElementById("lstc_mail_message-0").value;
                // Replace tags in Subject for preview
                s = s.replaceAll('{title}', 'Sample Title');
                s = s.replaceAll('{name}', 'You');
                s = s.replaceAll('{author}', 'Bob');
                // Replace tags in Message Body for preview
                m = m.replaceAll('{title}', 'Sample Title');
                m = m.replaceAll('{name}', 'You');
                m = m.replaceAll('{author}', 'Bob');
                m = m.replaceAll("{content}", "I totally agree with your opinion about him, he's really...");
                m = m.replaceAll('{link}', '#');
                m = m.replaceAll('{comment_link}', '#');
                m = m.replaceAll('{unsubscribe}', '#');
                m = m.replace(/\n/g, "<br />");
                var h = window.open("", "lstc", "status=0,toolbar=0,scrollbars=1,height=400,width=550");
                var d = h.document;
                d.write('<html><head><title>Email preview</title>');
                d.write('</head><body>');
                d.write('<table width="100%" border="1" cellspacing="0" cellpadding="5">');
                d.write('<tr><td align="right"><b>Subject</b></td><td>' + s + '</td></tr>');
                d.write('<tr><td align="right"><b>From</b></td><td>' + document.getElementById("lstc_mail_sender_name-0").value + ' &lt;' + document.getElementById("lstc_mail_sender_adress-0").value + '&gt;</td></tr>');
                d.write('<tr><td align="right"><b>To</b></td><td>User name &lt;user@email&gt;</td></tr>');
                d.write('<tr><td align="left" colspan="2">' + m + '</td></tr>');
                d.write('</table>');
                d.write('</body></html>');
                d.close();
                return false;
            }
        </script>



        <div class="wrap">
            <h2>Lightweight Subscribe To Comments</h2>

            <?php
            settings_errors();
            ?>
            <form method="post" action="">
                <?php
                settings_fields('lstc__optionGroup');
                do_settings_sections('lstc-admin');
                $submit = get_submit_button();
                $submit .= get_submit_button(__('Save and send a Thank You test email', 'lstc'), 'button-secondary button-large', 'savethankyou', false);
                $submit .= wp_nonce_field('lstc-save');
                $submit = str_replace('</p>', ' ', $submit) . '</p>';
                echo $submit;
                ?>
            </form>
            <script>
                function enable_restore(source) {
                    console.log(source)
                    let target = document.getElementById('defaults')
                    if (target.hasAttribute('disabled')) {
                        target.removeAttribute('disabled')
                        source.classList.add('active')
                    } else {
                        target.setAttribute('disabled', 'true')
                        source.classList.remove('active')
                    }
                }
            </script>
            <form method="post">
                <?php
                wp_nonce_field('restore-defaults');
                ?>
                <button type="button" class="lstc-toggle" onclick="enable_restore(this)">toggle</button>
                <?php
                submit_button(__('Restore defaults', 'lstc'), 'delete button-primary', 'defaults', false, 'disabled');
                ?>
            </form>
        </div>
<?php
    }

    public function lstc__pageInit()
    {
        $this->lstc__checkboxSettings();
        $this->lstc__mailSettings();
        $this->lstc__otherSettings();
        register_setting(
            'lstc__optionGroup', // option_group
            'lstc__optionName', // option_name
            array($this, 'lstc__sanitize') // sanitize_callback
        );
    }

    public function lstc__checkboxSettings()
    {
        add_settings_section(
            'lstc__checkboxSettings', // id
            __('Subscription Checkbox Configuration', 'lstc'), // title
            null, // callback
            'lstc-admin' // page
        );

        add_settings_field(
            'lstc_checkbox_activate', // id
            __('Enable The Checkbox', 'lstc'), // title
            array($this, 'lstc_checkbox_activate_callback'), // callback
            'lstc-admin', // page
            'lstc__checkboxSettings' // section
        );

        add_settings_field(
            'lstc_checkbox_label', // id
            __('Subscription Checkbox Label', 'lstc'), // title
            array($this, 'lstc_checkbox_label_callback'), // callback
            'lstc-admin', // page
            'lstc__checkboxSettings' // section
        );

        add_settings_field(
            'lstc_checkbox_default', // id
            __('Checkbox Default Status', 'lstc'), // title
            array($this, 'lstc_checkbox_default_callback'), // callback
            'lstc-admin', // page
            'lstc__checkboxSettings' // section
        );
    }

    public function lstc__mailSettings()
    {
        add_settings_section(
            'lstc__mailSettings', // id
            __('Notification Email Settings', 'lstc'), // title
            array($this, 'lstc_mail_info_callback'), // callback
            'lstc-admin' // page
        );

        add_settings_field(
            'lstc_mail_sender_name', // id
            __('From Name', 'lstc'), // title
            array($this, 'lstc_mail_sender_name_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );

        add_settings_field(
            'lstc_mail_sender_adress', // id
            __('From Email', 'lstc'), // title
            array($this, 'lstc_mail_sender_adress_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );

        add_settings_field(
            'lstc_mail_subject', // id
            __('Notification Subject', 'lstc'), // title
            array($this, 'lstc_mail_subject_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );

        add_settings_field(
            'lstc_mail_message', // id
            __('Message Body', 'lstc'), // title
            array($this, 'lstc_mail_message_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );

        add_settings_field(
            'lstc_mail_co_length', // id
            __('Comment Excerpt Length', 'lstc'), // title
            array($this, 'lstc_mail_co_length_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );

        add_settings_field(
            'lstc_mail_copy', // id
            __('Extra email address where to send a copy of EACH notification:', 'lstc'), // title
            array($this, 'lstc_mail_copy_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );

        add_settings_field(
            'lstc_mail_test', // id
            __('Email address where to send test emails:', 'lstc'), // title
            array($this, 'lstc_mail_test_callback'), // callback
            'lstc-admin', // page
            'lstc__mailSettings' // section
        );
    }

    public function lstc__otherSettings()
    {
        add_settings_section(
            'lstc__otherSettings', // id
            __('Additional Settings', 'lstc'), // title
            null, // callback
            'lstc-admin' // page
        );

        add_settings_field(
            'lstc_other_unsub_altpage', // id
            __('Custom Unsubscription Page', 'lstc'), // title
            array($this, 'lstc_other_unsub_altpage_callback'), // callback
            'lstc-admin', // page
            'lstc__otherSettings' // section
        );

        add_settings_field(
            'lstc_other_unsub_message', // id
            __('Display a message on unsubscription', 'lstc'), // title
            array($this, 'lstc_other_unsub_message_callback'), // callback
            'lstc-admin', // page
            'lstc__otherSettings' // section
        );

        add_settings_field(
            'lstc_other_delete_data', // id
            __('Wipe Data', 'lstc'), // title
            array($this, 'lstc_other_delete_data_callback'), // callback
            'lstc-admin', // page
            'lstc__otherSettings' // section
        );
    }

    public function lstc_checkbox_activate_callback()
    {
        printf(
            '<label for="lstc_checkbox_activate-0">
                <input type="checkbox" name="lstc__optionName[lstc_checkbox_activate]" id="lstc_checkbox_activate-0" value="checkbox-active" %s />
                %s
            </label>',
            (isset($this->lstc__options['lstc_checkbox_activate']) && $this->lstc__options['lstc_checkbox_activate'] === 'checkbox-active'
            ) ? 'checked' : '',
            __('Check this to add the "Notify me" subscription checkbox to the comment form.', 'lstc')
        );
    }

    public function lstc_checkbox_label_callback()
    {
        printf(
            '<label for="lstc_checkbox_label-0">
                <input type="text" size="70" name="lstc__optionName[lstc_checkbox_label]" id="lstc_checkbox_label-0" value="%s"/><br/>
                %s
            </label>',
            htmlspecialchars($this->lstc__checkboxLabel) ?? "",
            __('Label to be displayed near the subscription checkbox', 'lstc')
        );
    }

    public function lstc_checkbox_default_callback()
    {
        printf(
            '<label for="lstc_checkbox_default-0">
                <input type="checkbox" name="lstc__optionName[lstc_checkbox_default]" id="lstc_checkbox_default-0" value="checkbox-active" %s />%s
            </label>',
            (isset($this->lstc__options['lstc_checkbox_default']) && $this->lstc__options['lstc_checkbox_default'] === 'checkbox-active'
            ) ? 'checked' : '',
            __('Check here if you want the "Notify me" subscription checkbox to be checked by default', 'lstc')
        );
    }

    public function lstc_mail_info_callback()
    {
        echo '<p>' . __('Here you can configure the message which is sent to subscribers to notify them that a new comment was posted.') . '</p>';
    }

    public function lstc_mail_sender_name_callback()
    {
        printf(
            '<label for="lstc_mail_sender_name-0">
                <input type="text" size="70" name="lstc__optionName[lstc_mail_sender_name]" id="lstc_mail_sender_name-0" value="%s"/><br/>
                %s
            </label>',
            htmlspecialchars($this->lstc__mailSenderName) ?? "",
            __('Notification Sender Name', 'lstc')
        );
    }

    public function lstc_mail_sender_adress_callback()
    {
        printf(
            '<label for="lstc_mail_sender_adress-0">
                <input type="text" size="70" name="lstc__optionName[lstc_mail_sender_adress]" id="lstc_mail_sender_adress-0" value="%s"/><br/>
                %s
            </label>',
            htmlspecialchars($this->lstc__mailSenderAdress) ?? "",
            __('Notification Sender Email', 'lstc')
        );
    }

    public function lstc_mail_subject_callback()
    {
        printf(
            '<label for="lstc_mail_subject-0">
                <input type="text" size="70" name="lstc__optionName[lstc_mail_subject]" id="lstc_mail_subject-0" value="%s"/><br/>
                %s
            </label>',
            $this->lstc__mailSubject ?? "",
            sprintf(__('Tags: %4$s %1$s - the post title %4$s %2$s - the subscriber name %4$s %3$s - the commenter name', 'lstc'), '{title}', '{name}', '{author}', '<br />')
        );
    }

    public function lstc_mail_message_callback()
    {
        printf(
            '<label for="lstc_mail_message-0">
            (<a href="javascript:void(lstcPreview());">%s</a>)<br/>
            <textarea name="lstc__optionName[lstc_mail_message]" id="lstc_mail_message-0" wrap="off" rows="10" cols="70">%s</textarea><br/>
            %s
            </label>',
            __('preview', 'lstc'),
            $this->lstc__mailMessage ?? "",
            sprintf(__('Tags: %8$s %1$s - the subscriber name %8$s %2$s - the commenter name %8$s %3$s - the post title %8$s %4$s - the comment text (eventually truncated) %8$s %5$s - link to the comment %8$s %6$s - link to the post/page %8$s %7$s - the unsubscribe link', 'lstc'), '{name}', '{author}', '{title}', '{content}', '{comment_link}', '{link}', '{unsubscribe}', '<br />')
        );
    }

    public function lstc_mail_co_length_callback()
    {
        printf(
            '<label for="lstc_mail_co_length-0">
                <input type="number" size="5" min="10" max="2000" name="lstc__optionName[lstc_mail_co_length]" id="lstc_mail_co_length-0" value="%s"/>%s<br/>
                %s
            </label>',
            htmlspecialchars($this->lstc__mailCoLength) ?? "",
            __('Characters', 'lstc'),
            __('The length of the comment excerpt to be inserted in the email notification. The default is 155 characters.', 'lstc')
        );
    }

    public function lstc_mail_copy_callback()
    {
        printf(
            '<label for="lstc_mail_copy-0">
                <input type="text" size="70" name="lstc__optionName[lstc_mail_copy]" id="lstc_mail_copy-0" value="%s"/><br/>
                %s
            </label>',
            htmlspecialchars($this->lstc__mailCopy) ?? "",
            __('Leave empty to disable.', 'lstc')
        );
    }

    public function lstc_mail_test_callback()
    {
        printf(
            '<label for="lstc_mail_test-0">
                <input type="text" size="70" name="lstc__optionName[lstc_mail_test]" id="lstc_mail_test-0" value="%s"/>
            </label>',
            htmlspecialchars($this->lstc__mailTest) ?? ""
        );
    }

    public function lstc_other_unsub_altpage_callback()
    {
        printf(
            '<label for="lstc_other_unsub_altpage-0">
                <input type="text" size="70" name="lstc__optionName[lstc_other_unsub_altpage]" id="lstc_other_unsub_altpage-0" value="%s"/>
                <br/>%s
            </label>',
            htmlspecialchars($this->lstc__otherUnsubAltpage) ?? "",
            __('If you want to provide a custom unsubscription page, enter the URL here.', 'lstc')
        );
    }

    public function lstc_other_unsub_message_callback()
    {
        printf(
            '<label for="lstc_other_unsub_message-0">
                <textarea name="lstc__optionName[lstc_other_unsub_message]" id="lstc_other_unsub_message-0" wrap="off" rows="10" cols="70">%s</textarea><br/>
                %s
            </label>',
            htmlspecialchars($this->lstc__otherUnsubMessage) ?? "",
            __('Example: You successfully unsubscribed and will be redirected to the homepage in a few seconds.', 'lstc')
        );
    }

    public function lstc_other_delete_data_callback()
    {
        printf(
            '<label for="lstc_other_delete_data_callback-0">
                <input type="checkbox" name="lstc__optionName[lstc_other_delete_data]" id="lstc_other_delete_data-0" value="checkbox-active" %s />%s
            </label>',
            (isset($this->lstc__options['lstc_other_delete_data']) && $this->lstc__options['lstc_other_delete_data'] === 'checkbox-active'
            ) ? 'checked' : '',
            __('Check here if you want to delete all data on uninstall.', 'lstc')
        );
    }

    public function lstc__sanitize($input)
    {
        $sanitary_values = array();

        /* CHECKBOX SETTINGS */
        if (isset($input['lstc_checkbox_activate'])) {
            $sanitary_values['lstc_checkbox_activate'] = $input['lstc_checkbox_activate'];
        } // checkbox
        if (isset($input['lstc_checkbox_label'])) {
            $sanitary_values['lstc_checkbox_label'] = sanitize_text_field($input['lstc_checkbox_label']);
        } // textfield
        if (isset($input['lstc_checkbox_default'])) {
            $sanitary_values['lstc_checkbox_default'] = $input['lstc_checkbox_default'];
        } // checkbox

        /* MAIL SETTINGS */
        if (isset($input['lstc_mail_sender_name'])) {
            $sanitary_values['lstc_mail_sender_name'] = sanitize_text_field($input['lstc_mail_sender_name']);
        } // textfield
        if (isset($input['lstc_mail_sender_adress'])) {
            $sanitary_values['lstc_mail_sender_adress'] = sanitize_email($input['lstc_mail_sender_adress']);
        } // textfield
        if (isset($input['lstc_mail_subject'])) {
            $sanitary_values['lstc_mail_subject'] = sanitize_text_field($input['lstc_mail_subject']);
        } // textfield
        if (isset($input['lstc_mail_message'])) {
            $sanitary_values['lstc_mail_message'] = wp_kses_post($input['lstc_mail_message']);
        } // textarea
        if (isset($input['lstc_mail_co_length'])) {
            $sanitary_values['lstc_mail_co_length'] = (int) $input['lstc_mail_co_length'];
        } // numberfield
        if (isset($input['lstc_mail_copy'])) {
            $sanitary_values['lstc_mail_copy'] = sanitize_email($input['lstc_mail_copy']);
        } // textfield
        if (isset($input['lstc_mail_test'])) {
            $sanitary_values['lstc_mail_test'] = sanitize_email($input['lstc_mail_test']);
        } // textfield

        /* OTHER SETTINGS */
        if (isset($input['lstc_other_unsub_altpage'])) {
            $sanitary_values['lstc_other_unsub_altpage'] = sanitize_text_field($input['lstc_other_unsub_altpage']);
        } // textfield
        if (isset($input['lstc_other_unsub_message'])) {
            $sanitary_values['lstc_other_unsub_message'] = wp_kses_post($input['lstc_other_unsub_message']);
        } // textarea
        if (isset($input['lstc_other_delete_data'])) {
            $sanitary_values['lstc_other_delete_data'] = $input['lstc_other_delete_data'];
        } // checkbox

        return $sanitary_values;
    }
}
