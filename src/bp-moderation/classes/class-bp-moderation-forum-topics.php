<?php
/**
 * BuddyBoss Moderation Forum Topics Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Topics.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Forum_Topics extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum_topic';

	/**
	 * BP_Moderation_Forum_Topics constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend oror Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_forum_topic_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bbp_get_topic', array( $this, 'restrict_single_item' ), 10, 2 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button", array( $this, 'update_button' ), 10, 2 );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ) );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $topic_id Topic id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $topic_id ) {
		return get_post_field( 'post_author', $topic_id );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $topic_id topic id.
	 *
	 * @return string
	 */
	public static function get_permalink( $topic_id ) {
		$url = get_the_permalink( $topic_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Forum Discussions', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query remove hidden/blocked user's forum's topic
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where   forum's topic Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		$sql = $this->exclude_where_query();
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		return $where;
	}

	/**
	 * Validate the topic is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param object $post   Current topic object.
	 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT.
	 *
	 * @return object|array|null
	 */
	public function restrict_single_item( $post, $output ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $post;
		}

		$post_id = ( ARRAY_A === $output ? $post['ID'] : ( ARRAY_N === $output ? current( $post ) : $post->ID ) );

		if ( $this->is_content_hidden( (int) $post_id ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Function to modify the button class
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $button      Button args.
	 * @param string $is_reported Item reported.
	 *
	 * @return string
	 */
	public function update_button( $button, $is_reported ) {

		if ( $is_reported ) {
			$button['button_attr']['class'] = 'button item-button bp-secondary-action outline reported-content';
		} else {
			$button['button_attr']['class'] = 'button item-button bp-secondary-action outline report-content';
		}

		return $button;
	}

	/**
	 * Filter to check the topic is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $retval  Check item is valid or not.
	 * @param string $item_id item id.
	 *
	 * @return bool
	 */
	public function validate_single_item( $retval, $item_id ) {
		if ( empty( $item_id ) ) {
			return $retval;
		}

		$topic = bbp_get_topic( (int) $item_id );

		if ( empty( $topic ) || empty( $topic->ID ) ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Function to change report button text.
	 *
	 * @since BuddyBoss X.X.X
	 *
	 * @param string $button_text Button text.
	 *
	 * @return string|void
	 */
	public function report_button_text( $button_text ) {
		$button_text = __( 'Report Topic', 'buddyboss' );

		return $button_text;
	}
}
