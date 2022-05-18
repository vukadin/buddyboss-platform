<?php
/**
 * Core Notification attachment class.
 *
 * @since      BuddyBoss [BBVERSION]
 * @subpackage Core
 * @package    BuddyPress
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment Notification class.
 *
 * Extends BP Attachment to manage the avatar uploads.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Attachment_Notification extends BP_Attachment {

	/**
	 * Construct Upload parameters.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @see   BP_Attachment::__construct() for list of parameters
	 */
	public function __construct() {
		// Allowed avatar types.
		$allowed_types = bp_core_get_allowed_avatar_types();

		parent::__construct(
			array(
				'action'                => 'bp_avatar_upload',
				'file_input'            => 'file',
				'original_max_filesize' => bp_core_avatar_original_max_filesize(),

				// Specific errors for avatars.
				'upload_error_strings'  => array(
					/* translators: %s: Max file size for the profile photo */
					9  => sprintf( _x( 'That photo is too big. Please upload one smaller than %s', 'profile photo upload error', 'buddypress' ), size_format( bp_core_avatar_original_max_filesize() ) ),

					/* translators: %s: comma separated list of file types allowed for the profile photo */
					10 => sprintf( _nx( 'Please upload only this file type: %s.', 'Please upload only these file types: %s.', count( $allowed_types ), 'profile photo upload error', 'buddypress' ), self::get_avatar_types( $allowed_types ) ),
				),
			)
		);
	}

	/**
	 * Gets the available avatar types.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $allowed_types Array of allowed avatar types.
	 *
	 * @return string comma separated list of allowed avatar types.
	 */
	public static function get_avatar_types( $allowed_types = array() ) {
		$types = array_map( 'strtoupper', $allowed_types );
		$comma = _x( ',', 'avatar types separator', 'buddypress' );

		return join( $comma . ' ', $types );
	}

	/**
	 * Set Upload Dir data for avatars.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function set_upload_dir() {
		if ( bp_core_avatar_upload_path() && bp_core_avatar_url() ) {
			$this->upload_path = bp_core_avatar_upload_path();
			$this->url         = bp_core_avatar_url();
			$this->upload_dir  = bp_upload_dir();
		} else {
			parent::set_upload_dir();
		}
	}

	/**
	 * Avatar specific rules.
	 *
	 * Adds an error if the avatar size or type don't match BuddyPress needs.
	 * The error code is the index of $upload_error_strings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $file the temporary file attributes (before it has been moved).
	 *
	 * @return array the file with extra errors if needed.
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error.
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		// File size is too big.
		if ( ! bp_core_check_avatar_size( array( 'file' => $file ) ) ) {
			$file['error'] = 9;

			// File is of invalid type.
		} elseif ( ! bp_core_check_avatar_type( array( 'file' => $file ) ) ) {
			$file['error'] = 10;
		}

		// Return with error code attached.
		return $file;
	}

	/**
	 * Maybe shrink the attachment to fit maximum allowed width.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @since 2.4.0 Add the $ui_available_width parameter, to inform about the Avatar UI width.
	 *
	 * @param string $file               The absolute path to the file.
	 * @param int    $ui_available_width Available width for the UI.
	 *
	 * @return false|string|WP_Image_Editor|WP_Error
	 */
	public static function shrink( $file = '', $ui_available_width = 0 ) {
		// Get image size.
		$avatar_data = parent::get_image_data( $file );

		// Init the edit args.
		$edit_args = array();

		// Defaults to the Avatar original max width constant.
		$original_max_width = bp_core_avatar_original_max_width();

		// The ui_available_width is defined and it's smaller than the Avatar original max width.
		if ( ! empty( $ui_available_width ) && $ui_available_width < $original_max_width ) {
			/**
			 * In this case, to make sure the content of the image will be fully displayed
			 * during the cropping step, let's use the Avatar UI Available width.
			 */
			$original_max_width = $ui_available_width;

			// $original_max_width has to be larger than the avatar's full width.
			if ( $original_max_width < bp_core_avatar_full_width() ) {
				$original_max_width = bp_core_avatar_full_width();
			}
		}

		// Do we need to resize the image?
		if ( isset( $avatar_data['width'] ) && $avatar_data['width'] > $original_max_width ) {
			$edit_args = array(
				'max_w' => $original_max_width,
				'max_h' => $original_max_width,
			);
		}

		// Do we need to rotate the image?
		$angles = array(
			3 => 180,
			6 => - 90,
			8 => 90,
		);

		if ( isset( $avatar_data['meta']['orientation'] ) && isset( $angles[ $avatar_data['meta']['orientation'] ] ) ) {
			$edit_args['rotate'] = $angles[ $avatar_data['meta']['orientation'] ];
		}

		// No need to edit the avatar, original file will be used.
		if ( empty( $edit_args ) ) {
			return false;

			// Add the file to the edit arguments.
		} else {
			$edit_args['file'] = $file;
		}

		return parent::edit_image( 'avatar', $edit_args );
	}

	/**
	 * Check if the image dimensions are smaller than full avatar dimensions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $file the absolute path to the file.
	 *
	 * @return bool
	 */
	public static function is_too_small( $file = '' ) {
		$uploaded_image = @getimagesize( $file );
		$full_width     = bp_core_avatar_full_width();
		$full_height    = bp_core_avatar_full_height();

		if ( isset( $uploaded_image[0] ) && $uploaded_image[0] < $full_width || $uploaded_image[1] < $full_height ) {
			return true;
		}

		return false;
	}

	/**
	 * Crop the avatar.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Array of arguments for the cropping.
	 *
	 * @return array The cropped avatars (full, thumb and the timestamp).
	 * @see   BP_Attachment::crop for the list of parameters
	 */
	public function crop( $args = array() ) {
		// Bail if the original file is missing.
		if ( empty( $args['original_file'] ) ) {
			return false;
		}

		if ( ! bp_attachments_current_user_can( 'edit_avatar', $args ) ) {
			return false;
		}

		if ( 'user' === $args['object'] ) {
			$avatar_dir = 'avatars';
		} else {
			$avatar_dir = sanitize_key( $args['object'] ) . '-avatars';
		}

		$args['item_id'] = (int) $args['item_id'];

		/**
		 * Original file is a relative path to the image
		 * eg: /avatars/1/avatar.jpg
		 */
		$relative_path = sprintf( '/%s/%s/%s', $avatar_dir, $args['item_id'], basename( $args['original_file'] ) );
		$absolute_path = $this->upload_path . $relative_path;

		// Bail if the avatar is not available.
		if ( ! file_exists( $absolute_path ) ) {
			return false;
		}

		if ( empty( $args['item_id'] ) ) {

			/** This filter is documented in bp-core/bp-core-avatars.php */
			$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', dirname( $absolute_path ), $args['item_id'], $args['object'], $args['avatar_dir'] );
		} else {

			/** This filter is documented in bp-core/bp-core-avatars.php */
			$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', $this->upload_path . '/' . $args['avatar_dir'] . '/' . $args['item_id'], $args['item_id'], $args['object'], $args['avatar_dir'] );
		}

		// Bail if the avatar folder is missing for this item_id.
		if ( ! file_exists( $avatar_folder_dir ) ) {
			return false;
		}

		// Get the existing avatar files for the object.
		$existing_avatar = bp_core_fetch_avatar(
			array(
				'object'  => $args['object'],
				'item_id' => $args['item_id'],
				'html'    => false,
			)
		);

		/**
		 * Check that the new avatar doesn't have the same name as the
		 * old one before moving the previous one into history.
		 */
		if ( ! empty( $existing_avatar ) && $existing_avatar !== $this->url . $relative_path ) {
			// Avatar history is disabled, simply delete the existing avatar files.
			if ( bp_avatar_history_is_disabled() ) {
				bp_core_delete_existing_avatar(
					array(
						'object'      => $args['object'],
						'item_id'     => $args['item_id'],
						'avatar_path' => $avatar_folder_dir,
					)
				);
			} else {
				// Add a new revision for the existing avatar.
				$avatars = bp_attachments_list_directory_files( $avatar_folder_dir );

				if ( $avatars ) {
					foreach ( $avatars as $avatar_file ) {
						if ( ! isset( $avatar_file->name, $avatar_file->id, $avatar_file->path ) ) {
							continue;
						}

						$is_full  = preg_match( '/-bpfull/', $avatar_file->name );
						$is_thumb = preg_match( '/-bpthumb/', $avatar_file->name );

						if ( $is_full || $is_thumb ) {
							$revision = $this->add_revision(
								'avatar',
								array(
									'file_abspath' => $avatar_file->path,
									'file_id'      => $avatar_file->id,
								)
							);

							if ( is_wp_error( $revision ) ) {
								error_log( $revision->get_error_message() );
							}
						}
					}
				}
			}
		}

		// Make sure we at least have minimal data for cropping.
		if ( empty( $args['crop_w'] ) ) {
			$args['crop_w'] = bp_core_avatar_full_width();
		}

		if ( empty( $args['crop_h'] ) ) {
			$args['crop_h'] = bp_core_avatar_full_height();
		}

		// Get the file extension.
		$data = @getimagesize( $absolute_path );
		$ext  = 'image/png' === $data['mime'] ? 'png' : 'jpg';

		$args['original_file'] = $absolute_path;
		$args['src_abs']       = false;

		$avatar_types = array(
			'full'  => '',
			'thumb' => '',
		);
		$timestamp    = bp_core_current_time( true, 'timestamp' );

		foreach ( $avatar_types as $key_type => $type ) {
			if ( 'thumb' === $key_type ) {
				$args['dst_w'] = bp_core_avatar_thumb_width();
				$args['dst_h'] = bp_core_avatar_thumb_height();
			} else {
				$args['dst_w'] = bp_core_avatar_full_width();
				$args['dst_h'] = bp_core_avatar_full_height();
			}

			$filename         = wp_unique_filename( $avatar_folder_dir, $timestamp . "-bp{$key_type}.{$ext}" );
			$args['dst_file'] = $avatar_folder_dir . '/' . $filename;

			$avatar_types[ $key_type ] = parent::crop( $args );
		}

		// Remove the original.
		@unlink( $absolute_path );

		// Return the full, thumb cropped avatars and the timestamp.
		return array_merge(
			$avatar_types,
			array(
				'timestamp' => $timestamp,
			)
		);
	}

	/**
	 * Get the user id to set its avatar.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return integer The user ID.
	 */
	private function get_user_id() {
		$bp      = buddypress();
		$user_id = 0;

		if ( bp_is_user() ) {
			$user_id = bp_displayed_user_id();
		}

		if ( ! empty( $bp->members->admin->user_id ) ) {
			$user_id = $bp->members->admin->user_id;
		}

		return $user_id;
	}

	/**
	 * Get the group id to set its avatar.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return integer The group ID.
	 */
	private function get_group_id() {
		$group_id = 0;

		if ( bp_is_group() ) {
			$group_id = bp_get_current_group_id();
		}

		return $group_id;
	}

	/**
	 * Build script data for the Uploader UI.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array The javascript localization data.
	 */
	public function script_data() {
		// Get default script data.
		$script_data = parent::script_data();

		// Defaults to Avatar Backbone script.
		$js_scripts = array( 'bp-avatar' );

		// Default object.
		$object = '';

		// Get the possible item ids.
		$user_id  = $this->get_user_id();
		$group_id = $this->get_group_id();

		if ( ! empty( $user_id ) ) {
			// Should we load the the Webcam Avatar javascript file.
			if ( bp_avatar_use_webcam() ) {
				$js_scripts = array( 'bp-webcam' );
			}

			$script_data['bp_params'] = array(
				'object'     => 'user',
				'item_id'    => $user_id,
				'has_avatar' => bp_get_user_has_avatar( $user_id ),
				'nonces'     => array(
					'set'    => wp_create_nonce( 'bp_avatar_cropstore' ),
					'remove' => wp_create_nonce( 'bp_delete_avatar_link' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'There was a problem cropping your profile photo.', 'buddypress' ),
				2 => __( 'Your new profile photo was uploaded successfully.', 'buddypress' ),
				3 => __( 'There was a problem deleting your profile photo. Please try again.', 'buddypress' ),
				4 => __( 'Your profile photo was deleted successfully!', 'buddypress' ),
				5 => __( 'Your profile photo was recycled successfully!', 'buddypress' ),
				6 => __( 'The profile photo was permanently deleted successfully!', 'buddypress' ),
			);
		} else {

			/**
			 * Use this filter to include specific BuddyPress params for your object.
			 * e.g. Blavatar.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $value The avatar specific BuddyPress parameters.
			 */
			$script_data['bp_params'] = apply_filters( 'bp_attachment_avatar_params', array() );
		}

		// Include the specific css.
		$script_data['extra_css'] = array( 'bp-avatar' );

		// Include the specific css.
		$script_data['extra_js'] = $js_scripts;

		// Set the object to contextualize the filter.
		if ( isset( $script_data['bp_params']['object'] ) ) {
			$object = $script_data['bp_params']['object'];
		}

		/**
		 * Use this filter to override/extend the avatar script data.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $script_data The avatar script data.
		 * @param string $object      The object the avatar belongs to (eg: user or group).
		 */
		return apply_filters( 'bp_attachments_notification_script_data', $script_data, $object );
	}
}
