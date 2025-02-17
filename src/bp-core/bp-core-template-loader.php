<?php
/**
 * BuddyPress Template Functions.
 *
 * This file contains functions necessary to mirror the WordPress core template
 * loading process. Many of those functions are not filterable, and even then
 * would not be robust enough to predict where BuddyPress templates might exist.
 *
 * @package BuddyBoss\Template\Functions
 * @since BuddyPress 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get a BuddyPress template part for display in a theme.
 *
 * @since BuddyPress 1.7.0
 * @since BuddyPress 7.0.0 Added $args parameter.
 * @since BuddyBoss [BBVERSION] Added $args parameter.
 *
 * @param string      $slug Template part slug. Used to generate filenames,
 *                          eg 'friends' for 'friends.php'.
 * @param string|null $name Optional. Template part name. Used to generate
 *                          secondary filenames, eg 'personal' for 'activity-personal.php'.
 * @param array       $args Optional. Extra args to pass to locate_template().
 *
 * @return false|string Path to located template. See {@link bp_locate_template()}.
 */
function bp_get_template_part( $slug, $name = null, $args = array() ) {

	/**
	 * Fires at the start of bp_get_template_part().
	 *
	 * This is a variable hook that is dependent on the slug passed in.
	 *
	 * @since BuddyPress 1.7.0
	 * @since BuddyPress 7.0.0 Added $args parameter.
	 * @since BuddyBoss [BBVERSION] Added $args parameter.
	 *
	 * @param string $slug Template part slug requested.
	 * @param string $name Template part name requested.
	 * @param array  $args Extra args to pass to locate_template().
	 */
	do_action( 'get_template_part_' . $slug, $slug, $name, $args );

	// Setup possible parts.
	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = $slug . '-' . $name . '.php';
	}
	$templates[] = $slug . '.php';

	/**
	 * Filters the template parts to be loaded.
	 *
	 * @since BuddyPress 1.7.0
	 * @since BuddyPress 7.0.0 Added $args parameter.
	 * @since BuddyBoss [BBVERSION] Added $args parameter.
	 *
	 * @param array  $templates Array of templates located.
	 * @param string $slug      Template part slug requested.
	 * @param string $name      Template part name requested.
	 * @param array  $args      Extra args to pass to locate_template().
	 */
	$templates = apply_filters( 'bp_get_template_part', $templates, $slug, $name, $args );

	// Return the part that is found.
	return bp_locate_template( $templates, true, false, $args );
}

/**
 * Get an asset template part.
 *
 * Basically the same as {@link bp_get_template_part()}, but with 'assets/'
 * prepended to the slug.
 *
 * @since BuddyPress 2.6.0
 * @since BuddyPress 7.0.0 Added $args parameter.
 * @since BuddyBoss [BBVERSION] Added $args parameter.
 *
 * @see bp_get_template_part() for full documentation.
 *
 * @param string      $slug Template slug.
 * @param string|null $name Template name.
 * @param array       $args Optional. Extra args to pass to locate_template().
 *
 * @return false|string
 */
function bp_get_asset_template_part( $slug, $name = null, $args = array() ) {
	return bp_get_template_part( "assets/{$slug}", $name, $args );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * @since BuddyPress 1.7.0
 * @since BuddyPress 7.0.0 Added $args parameter.
 * @since BuddyBoss [BBVERSION] Added $args parameter.
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool         $load           Optional. If true, the template file will be loaded when
 *                                     found. If false, the path will be returned. Default: false.
 * @param bool         $require_once   Optional. Whether to require_once or require. Has
 *                                     no effect if $load is false. Default: true.
 * @param array        $args           Optional. Extra args to pass to locate_template().
 *
 * @return string The template filename if one is located.
 */
function bp_locate_template( $template_names, $load = false, $require_once = true, $args = array() ) {

	// Bail when there are no templates to locate
	if ( empty( $template_names ) ) {
		return false;
	}

	/**
	 * Filter here to update template name.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $template_names template name.
	 */
	$template_names = apply_filters( 'bp_locate_template_names', $template_names );

	// No file found yet.
	$located            = false;
	$template_locations = bp_get_template_stack();

	// Try to find a template file.
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty.
		if ( empty( $template_name ) ) {
			continue;
		}

		// Trim off any slashes from the template name.
		$template_name = ltrim( $template_name, '/' );

		// Loop through template stack.
		foreach ( (array) $template_locations as $template_location ) {

			// Continue if $template_location is empty.
			if ( empty( $template_location ) ) {
				continue;
			}

			// Check child theme first.
			if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
				$located = trailingslashit( $template_location ) . $template_name;
				break 2;
			}
		}
	}

	/**
	 * This action exists only to follow the standard BuddyPress coding convention,
	 * and should not be used to short-circuit any part of the template locator.
	 *
	 * If you want to override a specific template part, please either filter
	 * 'bp_get_template_part' or add a new location to the template stack.
	 */
	do_action( 'bp_locate_template', $located, $template_name, $template_names, $template_locations, $load, $require_once, $args );
	/**
	 * Filter here to allow/disallow template loading.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param bool $value True to load the template, false otherwise.
	 */
	$load_template = (bool) apply_filters( 'bp_locate_template_and_load', true );

	if ( $load_template && $load && ! empty( $located ) ) {
		load_template( $located, $require_once, $args );
	}

	return $located;
}

/**
 * Get file data of the highest priority asset that exists.
 *
 * Similar to {@link bp_locate_template()}, but for files like CSS and JS.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string $filename Relative filename to search for.
 * @return false|array Array of asset data if one is located (includes absolute filepath and URI).
 *                     Boolean false on failure.
 */
function bp_locate_template_asset( $filename ) {
	// Use bp_locate_template() to find our asset.
	$located = bp_locate_template( $filename, false );
	if ( false === $located ) {
		return false;
	}

	// Set up data array.
	$data         = array();
	$data['file'] = $data['uri'] = $located;

	$find = array(
		get_theme_root(),
		bp_get_theme_compat_dir(),
	);

	$replace = array(
		get_theme_root_uri(),
		bp_get_theme_compat_url(),
	);

	// Make sure URI path is relative to site URL.
	$data['uri'] = str_replace( $find, $replace, $data['uri'] );

	return $data;
}

/**
 * Register a new template stack location.
 *
 * This allows for templates to live in places beyond just the parent/child
 * relationship, to allow for custom template locations. Used in conjunction
 * with bp_locate_template(), this allows for easy template overrides.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $location_callback Callback function that returns the stack location.
 * @param int    $priority          Optional. The priority parameter as passed to
 *                                  add_filter(). Default: 10.
 * @return bool See {@link add_filter()}.
 */
function bp_register_template_stack( $location_callback = '', $priority = 10 ) {

	// Bail if no location, or function/method is not callable.
	if ( empty( $location_callback ) || ! is_callable( $location_callback ) ) {
		return false;
	}

	// Add location callback to template stack.
	return add_filter( 'bp_template_stack', $location_callback, (int) $priority );
}

/**
 * Deregister a previously registered template stack location.
 *
 * @since BuddyPress 1.7.0
 *
 * @see bp_register_template_stack()
 *
 * @param string $location_callback Callback function that returns the stack location.
 * @param int    $priority          Optional. The priority parameter passed to
 *                                  {@link bp_register_template_stack()}. Default: 10.
 * @return bool See {@link remove_filter()}.
 */
function bp_deregister_template_stack( $location_callback = '', $priority = 10 ) {

	// Bail if no location, or function/method is not callable.
	if ( empty( $location_callback ) || ! is_callable( $location_callback ) ) {
		return false;
	}

	// Add location callback to template stack.
	return remove_filter( 'bp_template_stack', $location_callback, (int) $priority );
}

/**
 * Get the "template stack", a list of registered directories where templates can be found.
 *
 * Calls the functions added to the 'bp_template_stack' filter hook, and return
 * an array of the template locations.
 *
 * @since BuddyPress 1.7.0
 *
 * @see bp_register_template_stack()
 *
 * @global array $wp_filter         Stores all of the filters.
 * @global array $merged_filters    Merges the filter hooks using this function.
 * @global array $wp_current_filter Stores the list of current filters with
 *                                  the current one last.
 * @return array The filtered value after all hooked functions are applied to it.
 */
function bp_get_template_stack() {
	global $wp_filter, $merged_filters, $wp_current_filter;

	// Setup some default variables.
	$tag  = 'bp_template_stack';
	$args = $stack = array();

	// Add 'bp_template_stack' to the current filter array.
	$wp_current_filter[] = $tag;

	// Sort.
	if ( class_exists( 'WP_Hook' ) ) {
		$filter = $wp_filter[ $tag ]->callbacks;
	} else {
		$filter = &$wp_filter[ $tag ];

		if ( ( is_array( $filter ) && ! empty( $filter ) ) && ! isset( $merged_filters[ $tag ] ) ) {
			ksort( $filter );
			$merged_filters[ $tag ] = true;
		}
	}

	// check filter if not array.
	if ( ! is_array( $filter ) || empty( $filter ) ) {
		return (array) apply_filters( 'bp_get_template_stack', array() );
	}

	// Ensure we're always at the beginning of the filter array.
	reset( $filter );

	// Loop through 'bp_template_stack' filters, and call callback functions.
	do {
		foreach ( (array) current( $filter ) as $the_ ) {
			if ( ! is_null( $the_['function'] ) ) {
				$args[1] = $stack;
				$stack[] = call_user_func_array( $the_['function'], array_slice( $args, 1, (int) $the_['accepted_args'] ) );
			}
		}
	} while ( next( $filter ) !== false );

	// Remove 'bp_template_stack' from the current filter array.
	array_pop( $wp_current_filter );

	// Remove empties and duplicates.
	$stack = array_unique( array_filter( $stack ) );

	/**
	 * Filters the "template stack" list of registered directories where templates can be found.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $stack Array of registered directories for template locations.
	 */
	return (array) apply_filters( 'bp_get_template_stack', $stack );
}

/**
 * Put a template part into an output buffer, and return it.
 *
 * @since BuddyPress 1.7.0
 * @since BuddyPress 7.0.0 Added $args parameter.
 * @since BuddyBoss [BBVERSION] Added $args parameter.
 *
 * @see bp_get_template_part() for a description of $slug, $name and $args params.
 *
 * @param string      $slug See {@link bp_get_template_part()}.
 * @param string|null $name See {@link bp_get_template_part()}.
 * @param bool        $echo If true, template content will be echoed. If false,
 *                          returned. Default: true.
 * @param array       $args See {@link bp_get_template_part()}.
 *
 * @return string|null If $echo, returns the template content.
 */
function bp_buffer_template_part( $slug, $name = null, $echo = true, $args = array() ) {
	ob_start();

	// Remove 'bp_replace_the_content' filter to prevent infinite loops.
	remove_filter( 'the_content', 'bp_replace_the_content' );

	bp_get_template_part( $slug, $name, $args );

	// Remove 'bp_replace_the_content' filter to prevent infinite loops.
	add_filter( 'the_content', 'bp_replace_the_content' );

	// Get the output buffer contents.
	$output = ob_get_clean();

	// Echo or return the output buffer contents.
	if ( true === $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Retrieve the path to a template.
 *
 * Used to quickly retrieve the path of a template without including the file
 * extension. It will also check the parent theme and theme-compat theme with
 * the use of {@link bp_locate_template()}. Allows for more generic template
 * locations without the use of the other get_*_template() functions.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $type      Filename without extension.
 * @param array  $templates An optional list of template candidates.
 * @return string Full path to file.
 */
function bp_get_query_template( $type, $templates = array() ) {
	$type = preg_replace( '|[^a-z0-9-]+|', '', $type );

	if ( empty( $templates ) ) {
		$templates = array( "{$type}.php" );
	}

	/**
	 * Filters possible file paths to check for for a template.
	 *
	 * This is a variable filter based on the type passed into
	 * bp_get_query_template.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $templates Array of template files already prepared.
	 */
	$templates = apply_filters( "bp_get_{$type}_template", $templates );

	/*
	 * Filter possible templates, try to match one, and set any BuddyPress theme
	 * compat properties so they can be cross-checked later.
	 */
	$templates = bp_set_theme_compat_templates( $templates );
	$template  = bp_locate_template( $templates );

	/*
	 * The current theme is using the WordPress Full Site Editing feature.
	 * BuddyPress then needs to use the WordPress template canvas to retrieve the community content.
	 */
	if (
		function_exists( 'wp_is_block_theme' ) &&
		wp_is_block_theme() &&
		(
			empty( $template ) ||
			get_theme_file_path( 'index.php' ) === $template
		)
	) {
		$template = ABSPATH . WPINC . '/template-canvas.php';
	}

	$template = bp_set_theme_compat_template( $template );

	/**
	 * Filters the path to a template file.
	 *
	 * This is a variable filter based on the type passed into
	 * bp_get_query_template.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $template Path to the most appropriate found template file.
	 */
	return apply_filters( "bp_{$type}_template", $template );
}

/**
 * Get the possible subdirectories to check for templates in.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $templates Templates we are looking for.
 * @return array Possible subfolders to look in.
 */
function bp_get_template_locations( $templates = array() ) {
	$locations = array(
		'buddypress',
		'community',
		'',
	);

	/**
	 * Filters the possible subdirectories to check for templates in.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $locations Array of subfolders to look in.
	 * @param array $templates Array of templates we are looking for.
	 */
	return apply_filters( 'bp_get_template_locations', $locations, $templates );
}

/**
 * Add template locations to template files being searched for.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $stacks Array of template locations.
 * @return array Array of all template locations registered so far.
 */
function bp_add_template_stack_locations( $stacks = array() ) {
	$retval = array();

	// Get alternate locations.
	$locations = bp_get_template_locations();

	// Loop through locations and stacks and combine.
	foreach ( (array) $stacks as $stack ) {
		foreach ( (array) $locations as $custom_location ) {
			$retval[] = untrailingslashit( trailingslashit( $stack ) . $custom_location );
		}
	}

	/**
	 * Filters the template locations to template files being searched for.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $value  Array of all template locations registered so far.
	 * @param array $stacks Array of template locations.
	 */
	return apply_filters( 'bp_add_template_stack_locations', array_unique( $retval ), $stacks );
}

/**
 * Add checks for BuddyPress conditions to 'parse_query' action.
 *
 * @since BuddyPress 1.7.0
 *
 * @param WP_Query $posts_query WP_Query object.
 */
function bp_parse_query( $posts_query ) {

	// Bail if $posts_query is not the main loop.
	if ( ! $posts_query->is_main_query() ) {
		return;
	}

	// Bail if filters are suppressed on this query.
	if ( true == $posts_query->get( 'suppress_filters' ) ) {
		return;
	}

	// Bail if in admin.
	if ( is_admin() ) {
		return;
	}

	/**
	 * Fires at the end of the bp_parse_query function.
	 *
	 * Allow BuddyPress components to parse the main query.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param WP_Query $posts_query WP_Query instance. Passed by reference.
	 */
	do_action_ref_array( 'bp_parse_query', array( &$posts_query ) );
}

/**
 * Possibly intercept the template being loaded.
 *
 * Listens to the 'template_include' filter and waits for any BuddyPress specific
 * template condition to be met. If one is met and the template file exists,
 * it will be used; otherwise.
 *
 * Note that the _edit() checks are ahead of their counterparts, to prevent them
 * from being stomped on accident.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $template The path to the template file that is being used.
 * @return string The path to the template file that is being used.
 */
function bp_template_include_theme_supports( $template = '' ) {

	/**
	 * Filters whether or not to override the template being loaded in parent/child themes.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param bool   $value    Whether or not there is a file override. Default false.
	 * @param string $template The path to the template file that is being used.
	 */
	$new_template = apply_filters( 'bp_get_root_template', false, $template );

	// A BuddyPress template file was located, so override the WordPress
	// template and use it to switch off BuddyPress's theme compatibility.
	if ( ! empty( $new_template ) ) {
		$template = bp_set_template_included( $new_template );
	}

	/**
	 * Filters the final template being loaded in parent/child themes.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $template The path to the template file that is being used.
	 */
	return apply_filters( 'bp_template_include_theme_supports', $template );
}

/**
 * Set the included template.
 *
 * @since BuddyPress 1.8.0
 *
 * @param mixed $template Default: false.
 * @return mixed False if empty. Template name if template included.
 */
function bp_set_template_included( $template = false ) {
	buddypress()->theme_compat->found_template = $template;

	return buddypress()->theme_compat->found_template;
}

/**
 * Is a BuddyPress template being included?
 *
 * @since BuddyPress 1.8.0
 *
 * @return bool True if yes, false if no.
 */
function bp_is_template_included() {
	return ! empty( buddypress()->theme_compat->found_template );
}

/**
 * Attempt to load a custom BP functions file, similar to each themes functions.php file.
 *
 * @since BuddyPress 1.7.0
 *
 * @global string $pagenow
 */
function bp_load_theme_functions() {
	global $pagenow, $wp_query;

	// Do not load our custom BP functions file if theme compat is disabled.
	if ( ! bp_use_theme_compat_with_current_theme() ) {
		return;
	}

	// Do not include on BuddyPress deactivation.
	if ( bp_is_deactivation() ) {
		return;
	}

	// If the $wp_query global is empty (the main query has not been run,
	// or has been reset), load_template() will fail at setting certain
	// global values. This does not happen on a normal page load, but can
	// cause problems when running automated tests.
	if ( ! is_a( $wp_query, 'WP_Query' ) ) {
		return;
	}

	// Only include if not installing or if activating via wp-activate.php.
	if ( ! defined( 'WP_INSTALLING' ) || 'wp-activate.php' === $pagenow ) {
		bp_locate_template( 'buddypress-functions.php', true );
	}
}

/**
 * Get the templates to use as the endpoint for BuddyPress template parts.
 *
 * @since BuddyPress 1.7.0
 * @since BuddyPress 2.4.0 Added singular.php to stack
 *
 * @return string Possible root level wrapper template files.
 */
function bp_get_theme_compat_templates() {
	return bp_get_query_template(
		'buddypress',
		array(
			'plugin-buddypress.php',
			'buddypress.php',
			'community.php',
			'generic.php',
			'page.php',
			'single.php',
			'singular.php',
			'index.php',
		)
	);
}

/**
 * Show/Hide WordPress toolbar based on the plugin settings.
 * BuddyBoss > Settings > General > General Settings.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_show_hide_toolbar() {

	if ( is_user_logged_in() ) {

		$old_user = false;
		if ( class_exists( 'BP_Core_Members_Switching' ) ) {
			$old_user = BP_Core_Members_Switching::get_old_user();
		}

		$user = wp_get_current_user();

		if ( ! empty( $old_user ) ) {
			if ( $user->ID != $old_user->ID ) {
				$userdata          = get_userdata( $user->ID );
				$current_user_caps = isset( $userdata->allcaps ) ? $userdata->allcaps : array();
			}
		} else {
			$userdata          = get_userdata( $user->ID );
			$current_user_caps = isset( $userdata->allcaps ) ? $userdata->allcaps : array();
		}

		if ( current_user_can( 'administrator' ) && in_array( 'administrator', $current_user_caps ) ) {
			if ( true === bp_show_admin_adminbar() ) {
				add_filter( 'show_admin_bar', '__return_true' );
			} else {
				add_filter( 'show_admin_bar', '__return_false' );
			}
		} else {
			if ( true === bp_show_login_adminbar() ) {
				add_filter( 'show_admin_bar', '__return_true' );
			} else {
				add_filter( 'show_admin_bar', '__return_false' );
			}
		}
	} else {
		if ( false === bp_hide_loggedout_adminbar() ) {
			add_filter( 'show_admin_bar', '__return_true' );
		}
	}

}
