<?php
/**
 * BuddyBoss Groups Template loop class.
 *
 * @package BuddyBoss
 * @since BuddyPress 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main Groups template loop class.
 *
 * Responsible for loading a group of groups into a loop for display.
 *
 * @since BuddyPress 1.2.0
 */
#[\AllowDynamicProperties]
class BP_Groups_Template {

	/**
	 * The loop iterator.
	 *
	 * @var int
	 * @since BuddyPress 1.2.0
	 */
	public $current_group = -1;

	/**
	 * The number of groups returned by the paged query.
	 *
	 * @var int
	 * @since BuddyPress 1.2.0
	 */
	public $group_count;

	/**
	 * Array of groups located by the query.
	 *
	 * @var array
	 * @since BuddyPress 1.2.0
	 */
	public $groups;

	/**
	 * The group object currently being iterated on.
	 *
	 * @var object
	 * @since BuddyPress 1.2.0
	 */
	public $group;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @var bool
	 * @since BuddyPress 1.2.0
	 */
	public $in_the_loop;

	/**
	 * The page number being requested.
	 *
	 * @var string
	 * @since BuddyPress 1.2.0
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @var string
	 * @since BuddyPress 1.2.0
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @var string
	 * @since BuddyPress 1.2.0
	 */
	public $pag_links;

	/**
	 * The total number of groups matching the query parameters.
	 *
	 * @var int
	 * @since BuddyPress 1.2.0
	 */
	public $total_group_count;

	/**
	 * Whether the template loop is for a single group page.
	 *
	 * @var bool
	 * @since BuddyPress 1.2.0
	 */
	public $single_group = false;

	/**
	 * Field to sort by.
	 *
	 * @var string
	 * @since BuddyPress 1.2.0
	 */
	public $sort_by;

	/**
	 * Sort order.
	 *
	 * @var string
	 * @since BuddyPress 1.2.0
	 */
	public $order;

	/**
	 * URL parameter key for pagination.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var string
	 */
	public $pag_arg = '';

	/**
	 * Constructor method.
	 *
	 * @see BP_Groups_Group::get() for an in-depth description of arguments.
	 *
	 * @param array $args {
	 *     Array of arguments. Accepts all arguments accepted by
	 *     {@link BP_Groups_Group::get()}. In cases where the default
	 *     values of the params differ, they have been discussed below.
	 *     @type int $per_page Default: 20.
	 *     @type int $page Default: 1.
	 * }
	 */
	function __construct( ...$args ){

		$function_args = func_get_args();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args[0] ) || count( $args ) > 1 ) {
			_deprecated_argument( __METHOD__, '1.7', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddyboss' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'user_id',
				1  => 'type',
				2  => 'page',
				3  => 'per_page',
				4  => 'max',
				5  => 'slug',
				6  => 'search_terms',
				7  => 'populate_extras',
				8  => 'include',
				9  => 'exclude',
				10 => 'show_hidden',
				11 => 'page_arg',
			);

			$args = bp_core_parse_args_array( $old_args_keys, $args );
		} else {
			$args = reset( $args );
		}

		$defaults = array(
			'page'               => 1,
			'per_page'           => 20,
			'page_arg'           => 'grpage',
			'max'                => false,
			'type'               => 'active',
			'order'              => 'DESC',
			'orderby'            => 'date_created',
			'show_hidden'        => false,
			'user_id'            => 0,
			'slug'               => false,
			'include'            => false,
			'exclude'            => false,
			'parent_id'          => null,
			'search_terms'       => '',
			'search_columns'     => array(),
			'group_type'         => '',
			'group_type__in'     => '',
			'status'             => array(),
			'group_type__not_in' => '',
			'meta_query'         => false,
			'date_query'         => false,
			'update_meta_cache'  => true,
			'update_admin_cache' => false,
		);

		$r = bp_parse_args( $args, $defaults, 'groups_template' );
		extract( $r );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		if ( bp_current_user_can( 'bp_moderate' ) || ( is_user_logged_in() && $user_id == bp_loggedin_user_id() ) ) {
			$show_hidden = true;
		}

		if ( 'invites' == $type ) {
			$this->groups = groups_get_invites_for_user( $user_id, $this->pag_num, $this->pag_page, $exclude );
		} elseif ( 'single-group' == $type ) {
			$this->single_group = true;

			if ( groups_get_current_group() ) {
				$group = groups_get_current_group();

			} else {
				$group = groups_get_group( BP_Groups_Group::get_id_from_slug( $r['slug'] ) );
			}

			// Backwards compatibility - the 'group_id' variable is not part of the
			// BP_Groups_Group object, but we add it here for devs doing checks against it
			//
			// @see https://buddypress.trac.wordpress.org/changeset/3540
			//
			// this is subject to removal in a future release; devs should check against
			// $group->id instead.
			$group->group_id = $group->id;

			$this->groups = array( $group );

		} else {
			$this->groups = groups_get_groups(
				array(
					'type'               => $type,
					'order'              => $order,
					'orderby'            => $orderby,
					'per_page'           => $this->pag_num,
					'page'               => $this->pag_page,
					'user_id'            => $user_id,
					'search_terms'       => $search_terms,
					'search_columns'     => $search_columns,
					'meta_query'         => $meta_query,
					'date_query'         => $date_query,
					'group_type'         => $group_type,
					'group_type__in'     => $group_type__in,
					'group_type__not_in' => $group_type__not_in,
					'status'             => $status,
					'include'            => $include,
					'exclude'            => $exclude,
					'parent_id'          => $parent_id,
					'update_meta_cache'  => $update_meta_cache,
					'update_admin_cache' => $update_admin_cache,
					'show_hidden'        => $show_hidden,
				)
			);
		}

		if ( 'invites' == $type ) {
			$this->total_group_count = (int) $this->groups['total'];
			$this->group_count       = (int) $this->groups['total'];
			$this->groups            = $this->groups['groups'];
		} elseif ( 'single-group' == $type ) {
			if ( empty( $group->id ) ) {
				$this->total_group_count = 0;
				$this->group_count       = 0;
			} else {
				$this->total_group_count = 1;
				$this->group_count       = 1;
			}
		} else {
			if ( empty( $max ) || $max >= (int) $this->groups['total'] ) {
				$this->total_group_count = (int) $this->groups['total'];
			} else {
				$this->total_group_count = (int) $max;
			}

			$this->groups = $this->groups['groups'];

			if ( ! empty( $max ) ) {
				if ( $max >= count( $this->groups ) ) {
					$this->group_count = count( $this->groups );
				} else {
					$this->group_count = (int) $max;
				}
			} else {
				$this->group_count = count( $this->groups );
			}
		}

		// Build pagination links.
		if ( (int) $this->total_group_count && (int) $this->pag_num ) {
			$pag_args = array(
				$this->pag_arg => '%#%',
			);

			if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
				$base = remove_query_arg( 's', wp_get_referer() );
			} else {
				$base = '';
			}

			$add_args = array(
				'num'    => $this->pag_num,
				'sortby' => $this->sort_by,
				'order'  => $this->order,
			);

			if ( ! empty( $search_terms ) ) {
				$query_arg              = bp_core_get_component_search_query_arg( 'groups' );
				$add_args[ $query_arg ] = urlencode( $search_terms );
			}

			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $pag_args, $base ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_group_count / (int) $this->pag_num ),
					'current'   => $this->pag_page,
					'prev_text' => __( '&larr;', 'buddyboss' ),
					'next_text' => __( '&rarr;', 'buddyboss' ),
					'mid_size'  => 1,
					'add_args'  => $add_args,
				)
			);
		}
	}

	/**
	 * Whether there are groups available in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @see bp_has_groups()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_groups() {
		if ( $this->group_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next group and iterate index.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return object The next group to iterate over.
	 */
	function next_group() {
		$this->current_group++;
		$this->group = $this->groups[ $this->current_group ];

		return $this->group;
	}

	/**
	 * Rewind the groups and reset member index.
	 *
	 * @since BuddyPress 1.2.0
	 */
	function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}

	/**
	 * Whether there are groups left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_groups()} as part of the while loop
	 * that controls iteration inside the groups loop, eg:
	 *     while ( bp_groups() ) { ...
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @see bp_groups()
	 *
	 * @return bool True if there are more groups to show, otherwise false.
	 */
	function groups() {
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 == $this->group_count ) {

			/**
			 * Fires right before the rewinding of groups list.
			 *
			 * @since BuddyPress 1.5.0
			 */
			do_action( 'group_loop_end' );
			// Do some cleaning up after the loop.
			$this->rewind_groups();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current group inside the loop.
	 *
	 * Used by {@link bp_the_group()} to set up the current group data
	 * while looping, so that template tags used during that iteration make
	 * reference to the current member.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @see bp_the_group()
	 */
	function the_group() {
		$this->in_the_loop = true;
		$this->group       = $this->next_group();

		if ( 0 == $this->current_group ) {

			/**
			 * Fires if the current group item is the first in the loop.
			 *
			 * @since BuddyPress 1.1.0
			 */
			do_action( 'group_loop_start' );
		}
	}
}
