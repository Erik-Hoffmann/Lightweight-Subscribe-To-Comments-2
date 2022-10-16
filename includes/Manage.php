<?php
class LstcManage
{

    private $db;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'lstc__addManagePage'));
        add_action('admin_menu', array($this, 'lstc__pageInit'));
        global $wpdb;
        $this->db = $wpdb;
    }

    public function lstc__addManagePage()
    {
        add_submenu_page(
            'lightweight-subscribe-to-comments-2',  // parent_slug
            'Email and Subscription Management', // page_title
            'Email and Subscription Management', // menu_title
            'manage_options', // capability
            'lstc-manage-page', // menu_slug
            array($this, 'lstc_createManagePage') // callback
        );
    }

    public function lstc_createManagePage()
    {
        wp_enqueue_style('admin-styles', plugins_url('lightweight-subscribe-to-comments-2/manage.css'));
?>
        <div class="wrap">

            <?php settings_errors(); ?>
            <?php
            settings_fields('lstc__manageGroup');
            do_settings_sections('lstc-manage');
            ?>

        </div>
    <?php
    }

    public function lstc__pageInit()
    {
        register_setting(
            'lstc__manageGroup', // option_group
            'lstc__manageName', // option_name
            null
            //array($this, 'lstc__sanitize') // sanitize_callback
        );
        add_settings_section(
            'lstc__manageNunsub', // id
            __('Manage Subscriptions & Unsubscribe', 'lstc'), // title
            array($this, 'lstc_manage_section_info'), // callback
            'lstc-manage' // page
        );

        add_settings_field(
            'lstc_manual_unsubscribe', // id
            __('Search Subscriptions', 'lstc'), // title
            array($this, 'subscriber_list'), // callback
            'lstc-manage', // page
            'lstc__manageNunsub', // section
            $_POST['s'][0] ?? null // args
        );
    }

    public function subscriber_list($search = null)
    {
        if (isset($_POST['remove'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'], 'remove'))
                die(__('Security violated', 'comment-notifier-no-spammers'));
            $query = "delete from " . $this->db->prefix . "comment_notifier where id in (" . implode(',', $_POST['r']) . ")";
            $this->db->query($query);
        }
        if (isset($_POST['search'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'], 'search'))
                die(__('Security violated', 'comment-notifier-no-spammers'));
            $this->lstc_options['search'] = $_POST['s'] ?? null;
        }
    ?>


        <form action="" method="post">
            <?php wp_nonce_field('search') ?>
            <label for="s[]">Search by Mail</label>
            <input class="regular-text" type="text" name="s[]" placeholder="email@mail.com" />
            <?php submit_button(__('Search', 'comment-notifier-no-spammers'), 'secondary', 'search', false); ?>
        </form>


        <form action="" method="post">
            <?php wp_nonce_field('remove') ?>
            <ul>
                <?php
                $list = $this->db->get_results("select distinct post_id, count(post_id) as total from " . $this->db->prefix . "comment_notifier where post_id != 0 group by post_id order by total desc");
                foreach ($list as $r) {
                    $post_id = (int) $r->post_id;
                    $total = (int) $r->total;
                    $post = get_post($post_id);
                    $list2 = $this->db->get_results("select id,email,name from " . $this->db->prefix . "comment_notifier where post_id=" . $post_id . ($search ? " and email='" . $search . "'" : ""));
                    $found = count($list2);
                    // ($found == 0 ? "d-none" : "")
                    echo '<li class="lstc_post_entry">
                        <span class="lstc_trigger"><span class="dashicons dashicons-controls-play"></span></span>
                        <div class="lstc_post_data">
                            <div class="lstc_post_meta">
                                <h3><a href="' . esc_url(get_permalink($post_id)) . '" target="_blank">' .
                        esc_html($post->post_title) . '</a></h3>
                                <sub>(id: ' . $post_id . __(', subscribers: ', 'lstc') . $total . ', found:' . $found . ')</sub>
                            </div>';
                    echo '<ul class="lstc_subscribers d-none">';
                    foreach ($list2 as $r2) {
                        echo '<li class="lstc_subscriber">
                                <input type="checkbox" name="r[]" value="' .  esc_attr($r2->id) . '"/>
                                <p>' . esc_html($r2->name) . '</p>
                                <p>' . esc_html($r2->email) . '</p>'
                            . '</li>';
                    }
                    echo '</ul>';
                    submit_button(__('Remove', 'lstc'), 'delete', 'remove');
                    echo '</div></li>';
                }
                ?>
            </ul>
        </form>
<?php
        print(<<<EOF
        <script defer>
            // handle post subscription toggles
            for (let ob of document.getElementsByClassName('lstc_trigger')) {
                ob.addEventListener('click', (el) => {
                    el.target.firstChild.classList.toggle('lstc_toggle')
                    el.target.nextElementSibling.children[1].classList.toggle('d-none')
                })
            }
        </script>
        EOF);
    }

    public function lstc_manage_section_info()
    {
        print("Search and remove subscribers directly from the database");
    }
}
