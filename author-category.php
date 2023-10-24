<?php
/*
Plugin Name: Author Category
Plugin URI: https:// en.bainternet.info
Description: A fork of a simple lightweight plugin that limits authors to post just in one category.
Version: 0.10.0
Author: Bainternet
Author URI: https:// en.bainternet.info
License: GPL 3.0
*/
/**
 * Author Category
 *
 * A fork of a simple lightweight plugin that limits authors to post just in one category.
 *
 * PHP versions 7 and 8
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https:// www.gnu.org/licenses/>.
 *
 * @category   Plugin
 * @package    author_category
 * @author     Ohad Raz <admin@bainternet.info>
 * @copyright  2012 - 2023 Ohad Raz
 * @license    https:// www.gnu.org/licenses/gpl-3.0.en.html   GNU General Public License 3.0
 * @version    0.10.0  (forked version of the original)
 * @link       https:// en.bainternet.info Main site for the Author Category plugin
 * @since      0.1
 */

/* Disallow direct access to the plugin file */
defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

if ( ! class_exists( 'Author_Category' ) ) {
	// WordPress strict standards dislike having a class variable with the text domain.
	// Note: Code Sniffer still complains!!
	if ( ! defined( 'AUTHOR_CATEGORY_TEXT_DOMAIN' ) ) {
		define( 'AUTHOR_CATEGORY_TEXT_DOMAIN', 'author-cat' );
	}

	/**
	 * Main class for the author_category plugin.
	 *
	 * @since 0.1
	 */
	class Author_Category {
		/**
		 * Class constructor.
		 *
		 * Sets up whatever hooks are appropriate for the user,
		 * adding admin-specific hooks if the user has admin powers.
		 *
		 * @author Ohad Raz
		 * @since 0.1
		 */
		public function __construct() {
			$this->hooks();

			if ( is_admin() ) {
				$this->admin_hooks();
			}
			esc_html( AUTHOR_CATEGORY_TEXT_DOMAIN );
		}

		/**
		 * Hooks adds all action and filter hooks.
		 *
		 * @since 0.6
		 * @return void
		 */
		public function hooks() {
			// save user field.
			add_action( 'personal_options_update', array( $this, 'save_extra_user_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_extra_user_profile_fields' ) );
			// add user field.
			add_action( 'show_user_profile', array( $this, 'extra_user_profile_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'extra_user_profile_fields' ) );

			// XMLRPC post insert hook and quickpress.
			add_filter( 'xmlrpc_wp_insert_post_data', array( $this, 'user_default_category' ), 2 );
			add_filter( 'pre_option_default_category', array( $this, 'user_default_category_option' ) );

			// Post by email category.
			add_filter( 'publish_phone', array( $this, 'post_by_email_cat' ) );
		}

		/**
		 * Add all action and filter hooks for admin side.
		 *
		 * @since 0.7
		 * @return void
		 */
		public function admin_hooks() {
			// translations.
			$this->load_translation();
			// remove quick and bulk edit.
			global $pagenow;
			if ( 'edit.php' === $pagenow ) {
				add_action( 'admin_print_styles', array( &$this, 'remove_quick_edit' ) );
			}
			// add metabox.
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			// plugin links row.
			add_filter( 'plugin_row_meta', array( $this, 'my_plugin_links' ), 10, 2 );

			// add admin panel.
			if ( ! class_exists( 'SimplePanel' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'inc/Simple_Panel_class.php';
				require_once plugin_dir_path( __FILE__ ) . 'inc/Author_Category_Panel_class.php';
			}
		}

		/**
		 * Function to overwrite the defult category option per user.
		 *
		 * @author Ohad   Raz
		 * @since 0.3
		 *
		 * @param  bool $myfalse  Unknown and unused.
		 * @return mixed|bool   Category ID if user has a category set and false otherwise.
		 */
		public function user_default_category_option( $myfalse ) {
			$cat = $this->get_user_cat();
			if ( ! empty( $cat ) && count( $cat ) > 0 ) {
				return $cat;
			}
			return false;
		}

		/**
		 * Function to handle XMLRPC calls.
		 *
		 * @author Ohad Raz
		 * @since 0.3
		 *
		 * @param  array $post_data  post data.
		 * @param  array $con_stactu XMLRPC post data.
		 * @return array
		 */
		public function user_default_category( $post_data, $con_stactu ) {
			$cat = $this->get_user_cat( $post_data['post_author'] );
			if ( ! empty( $cat ) && $cat > 0 ) {
				$post_data['tax_input']['category'] = array( $cat );
			}
			return $post_data;
		}

		/**
		 * Post by email category.
		 *
		 * @author Ohad   Raz
		 * @since 0.5
		 *
		 * @param  int $post_id  Post ID.
		 * @return void
		 */
		public function post_by_email_cat( $post_id ) {
			$p_id = get_post( $post_id );
			$cat  = $this->get_user_cat( $p_id['post_author'] );
			if ( $cat ) {
				$email_post                  = array();
				$email_post['ID']            = $post_id;
				$email_post['post_category'] = array( $cat );
				wp_update_post( $email_post );
			}
		}

		/**
		 * Remove quick edit.
		 *
		 * @author Ohad Raz
		 * @since 0.1
		 * @return void
		 */
		public function remove_quick_edit() {
			$cat = $this->get_user_cat( get_current_user_id() );
			if ( ! empty( $cat ) && count( $cat ) > 0 ) {
				echo '<style>.inline-edit-categories{display: none !important;}</style>';
			}
		}

		/**
		 * Adds the meta box container.
		 *
		 * @author Ohad Raz
		 * @since 0.1
		 */
		public function add_meta_box() {
			// get author categories.
			$cat = $this->get_user_cat( get_current_user_id() );
			if ( ! empty( $cat ) && count( $cat ) > 0 ) {
				// remove default metabox.
				remove_meta_box( 'categorydiv', 'post', 'side' );
				// add user specific categories.
				add_meta_box(
					'author_cat',
					__( 'Author category', AUTHOR_CATEGORY_TEXT_DOMAIN ),
					array( &$this, 'render_meta_box_content' ),
					'post',
					'side',
					'low'
				);
			}
		}


		/**
		 * Render Meta Box content.
		 *
		 * @author Ohad   Raz
		 * @since 0.1
		 * @return void
		 */
		public function render_meta_box_content() {
			$cats = get_user_meta( get_current_user_id(), '_author_cat', true );
			$cats = (array) $cats;
			// Use nonce for verification.
			wp_nonce_field( plugin_basename( __FILE__ ), 'author_cat_noncename' );
			if ( ! empty( $cats ) && count( $cats ) > 0 ) {
				if ( count( $cats ) === 1 ) {
					$metabox_cat = get_category( $cats[0] );
					esc_html_e( 'this will be posted in: <strong>', AUTHOR_CATEGORY_TEXT_DOMAIN ) . $metabox_cat->name
					. __( '</strong> Category', AUTHOR_CATEGORY_TEXT_DOMAIN );
					echo '<input name="post_category[]" type="hidden" value="' . esc_html( $metabox_cat->term_id ) . '">';
				} else {
					echo '<span style="color: #f00;">' . esc_html__( 'Make sure you select only the categories you want: <strong>', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</span><br />';
					$options = get_option( 'author_cat_option' );
					$checked = ( ! isset( $options['check_multi'] ) ) ? ' checked="checked"' : '';

					foreach ( $cats as $cat ) {
						$metabox_cat = get_category( $cat );
						echo '<label><input name="post_category[]" type="checkbox"' . esc_html( $checked )
						. ' value="' . esc_html( $metabox_cat->term_id ) . '"> ' . esc_html( $metabox_cat->name ) . '</label><br />';
					}
				}
			}
			do_action( 'in_author_category_metabox', get_current_user_id() );
		}

		/**
		 * This will generate the category field on the users profile
		 *
		 * @author Ohad   Raz
		 * @since 0.1
		 * @param  (object) $user  User to generate category fields for.
		 * @return bool FALSE if current user cannot manage option, TRUE otherwise.
		 */
		public function extra_user_profile_fields( $user ) {
			// only admin can see and save the categories.
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}
			if ( get_current_user_id() === $user->ID ) {
				return false;
			}
			$select = wp_dropdown_categories(
				array(
					'orderby'      => 'name',
					'show_count'   => 0,
					'hierarchical' => 1,
					'hide_empty'   => 0,
					'echo'         => 0,
					'name'         => 'author_cat[]',
				)
			);
			$saved  = get_user_meta( $user->ID, '_author_cat', true );
			foreach ( (array) $saved as $c ) {
				$select = str_replace(
					'value="' . $c . '"',
					'value="' . $c . '" selected="selected"',
					$select
				);
			}
			$select = str_replace( '<select', '<select multiple="multiple"', $select );
			echo '<h3>' . esc_html__( 'Author Category', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</h3>
			<table class="form-table">
				<tr>
					<th><label for="author_cat">' . esc_html__( 'Category', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</label></th>
					<td>
						' . esc_html( $select ) . '
						<br />
					<span class="description">' . esc_html__( 'select a category to limit an author to post just in that category (use Crtl to select more then one).',  AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</span>
					</td>
				</tr>
				<tr>
					<th><label for="author_cat_clear">' . esc_html__( 'Clear Category', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</label></th>
					<td>
						<input type="checkbox" name="author_cat_clear" value="1" />
						<br />
					<span class="description">' . esc_html__( 'Check if you want to clear the limitation for this user.', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</span>
					</td>
				</tr>
			</table>';
			return true;
		}

		/**
		 * This will save category field on the users profile
		 *
		 * @author Ohad   Raz
		 * @since 0.1
		 * @param  (int) $user_id  User ID.
		 * @return bool  False if current user can manage options, true otherwise.
		 */
		public function save_extra_user_profile_fields( $user_id ) {
			// only admin can see and save the categories!
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}
			/* @todo Missing nonce! */
			update_user_meta( $user_id, '_author_cat', ! empty( $_POST['author_cat'] ) ? wp_unslash( $_POST['author_cat'] ) : '' );

			if ( isset( $_POST['author_cat_clear'] ) && 1 === $_POST['author_cat_clear'] ) {
				delete_user_meta( $user_id, '_author_cat' );
			}
			return true;
		}

		/**
		 * Save category on post
		 *
		 * @author Ohad Raz
		 * @since 0.1
		 * @deprecated 0.3
		 * @param  (int) $post_id Post ID.
		 * @return void
		 */
		public function author_cat_save_meta( $post_id ) {
			/* do nothing */
		}

		/**
		 * Returns an array of categories assigned to this user ID
		 * or 0 if none/invalid user ID.
		 *
		 * @param int|null $user_id  User ID.
		 *
		 * @return int|array  0 on failure; array of categories for this user ID otherwise.
		 */
		public function get_user_cat( $user_id = null ) {
			if ( null === $user_id ) {
				global $current_user;
				wp_get_current_user();
				$user_id = $current_user->ID;
			}
			$cat = get_user_meta( $user_id, '_author_cat', true );
			if ( empty( $cat ) || count( $cat ) <= 0 || ! is_array( $cat ) ) {
				return 0;
			}
			return $cat[0];
		}

		/**
		 * Adds links to plugin row
		 *
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.3
		 *
		 * @param  array  $links   Whatever the links are.
		 * @param  string $file    Whatever the filename is.
		 * @return array
		 */
		public function my_plugin_links( $links, $file ) {
			$plugin = plugin_basename( __FILE__ );
			if ( $file === $plugin ) { // only for this plugin!
				return array_merge(
					$links,
					array( '<a href="http:// en.bainternet.info/category/plugins">' . esc_html__( 'Other Plugins by this author', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</a>' ),
					array( '<a href="http:// wordpress.org/support/plugin/author-category">' . esc_html__( 'Plugin Support', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</a>' ),
					array( '<a href="https:// www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K4MMGF5X3TM5L" target="_blank">' . esc_html__( 'Donate', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</a>' )
				);
			}
			return $links;
		}

		/**
		 * Loads translations
		 *
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.7
		 *
		 * @return void
		 */
		public function load_translation() {
			load_plugin_textdomain( $this->txt_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	} // end class
} // end if

// initiate the class on admin pages only.
if ( is_admin() ) {
	$ac = new author_category();
}
