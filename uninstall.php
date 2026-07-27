<?php
/**
 * Uninstall Micro Site Care: Post Last Updated Date.
 *
 * @return void
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'msclu_options' );
delete_option( 'msclu_activated_time' );
delete_option( 'msclu_review_dismissed' );
