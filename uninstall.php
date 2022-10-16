<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
global $wpdb;
// Make sure they opted to delete all data
$options = get_option( 'lstc__optionName' );
if ( ! empty( $options['lstc_other_delete_data'] ) ) {
    delete_option( 'lstc__optionName' );
    // Delete the comment_notifier database table
    $wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "comment_notifier" );
}
