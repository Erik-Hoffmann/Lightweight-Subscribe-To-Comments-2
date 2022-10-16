<?php

/*
Upon activation, setup the database table, default settings, and migrate
subscribers from other comment subscriber plugins.
 */

class Activator
{
    public static function activate()
    {
        global $wpdb;
        //Create table unless it exists from Comment Notifier plugin
        $sql = 'create table if not exists ' . $wpdb->prefix . 'comment_notifier (
        `id` int unsigned not null AUTO_INCREMENT,
        `post_id` int unsigned not null default 0,
        `name` varchar (100) not null default \'\',
        `email` varchar (100) not null default \'\',
        `token` varchar (50) not null default \'\',
        primary key (`id`),
        unique key `post_id_email` (`post_id`,`email`),
        key `token` (`token`)
        )';

        @$wpdb->query($sql);

        if (empty(get_option('lstc__optionName'))) Util::set_defaults();

    }
}
