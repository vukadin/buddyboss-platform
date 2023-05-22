<?php

/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the user profile hash to the user meta.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param array $user_ids User IDs.
 */
function bb_set_bluk_user_profile_slug( $user_ids ) {
	_deprecated_function( __FUNCTION__, '[BBVERSION]', 'bb_set_bulk_user_profile_slug' );
	bb_set_bulk_user_profile_slug( $user_ids );
}
