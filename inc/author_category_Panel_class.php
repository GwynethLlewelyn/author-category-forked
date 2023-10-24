<?php
/**
 * Author_Category_Panel is an extension class of SimplePanel.
 *
 * @package author_category
 * @author Ohad Raz <admin@bainternet.info>
 * @copyright 2013 Ohad Raz
 * @see SimplePanel
 */

defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
if ( ! class_exists( 'Author_Category_Panel' ) ) {
	if ( ! defined( 'AUTHOR_CATEGORY_TEXT_DOMAIN' ) ) {
		define( 'AUTHOR_CATEGORY_TEXT_DOMAIN', 'author-cat' );
	}

	/**
	 * Author_Category_Panel
	 */
	class Author_Category_Panel extends SimplePanel {
		/**
		 * Deals with the administration menu.
		 *
		 * @return void
		 */
		public function admin_menu() {
			$this->slug = add_users_page(
				$this->title,
				$this->name,
				$this->capability,
				__CLASS__,
				array( $this, 'show_page' )
			);

			// help tabs.
			add_action( 'load-' . $this->slug, array( $this, 'my_help_tab' ) );
			add_action( __FILE__ . 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		}

		/**
		 * Adds all necessary meta boxes.
		 *
		 * @return void
		 */
		public function add_meta_boxes() {
			add_meta_box( 'save_sidebar', __( 'Save changes', AUTHOR_CATEGORY_TEXT_DOMAIN ), array( $this, 'savec' ), __FILE__, 'side', 'low' );
			add_meta_box( 'Credit_sidebar', __( 'Credits', AUTHOR_CATEGORY_TEXT_DOMAIN ), array( $this, 'credits' ), __FILE__, 'side', 'low' );
			add_meta_box( 'News', __( 'Latest From Bainternet', AUTHOR_CATEGORY_TEXT_DOMAIN ), array( $this, 'news' ), __FILE__, 'side', 'low' );
			add_meta_box( 'main_settings', __( 'Author category settings', AUTHOR_CATEGORY_TEXT_DOMAIN ), array( $this, 'main_settings' ), __FILE__, 'normal', 'low' );
		}

		/**
		 * Fetches a RSS feed of news from the BAinternet website.
		 *
		 * @return void
		 */
		public function news() {
			if ( ! function_exists( 'fetch_feed' ) ) {
				include_once ABSPATH . WPINC . '/feed.php';
			}
			// Get a SimplePie feed object from the specified feed source.
			$rss      = fetch_feed( 'https://en.bainternet.info/feed' );
			$maxitems = 0;

			if ( ! is_wp_error( $rss ) ) {
				$maxitems  = $rss->get_item_quantity( 5 );
				$rss_items = $rss->get_items( 0, $maxitems );
			}
			?>
			<ul>
			<?php
			if ( 0 === $maxitems ) {
				echo '<li>' . __( 'No items.', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</li>';
				return;
			}
			// Loop through each feed item and display each item as a hyperlink.
			foreach ( $rss_items as $item ) :
			?>
				<li>
					<span><?php echo __( 'Posted ', AUTHOR_CATEGORY_TEXT_DOMAIN ) . $item->get_date( 'j F Y | g:i a' ); ?></span><br/>
					<a target="_blank" href='<?php echo esc_url( $item->get_permalink() ); ?>'
					title='<?php echo __( 'Posted ', AUTHOR_CATEGORY_TEXT_DOMAIN ) . $item->get_date( 'j F Y | g:i a' ); ?>'>
					<?php echo esc_html( $item->get_title() ); ?></a>
				</li>
			<?php
			endforeach;
			?>
			</ul>
			<?php
		}

		/**
		 * Save a form, using the button for submission.
		 *
		 * @return void
		 */
		public function savec() {
			submit_button();
		}

		/**
		 * Create a table with all the main settings.
		 *
		 * @return void
		 */
		public function main_settings() {
			foreach ( $this->sections as $s ) {
				echo '<table class="form-table">';
				do_settings_fields( __FILE__, $s['id'] );
				echo '</table>';
			}
		}

		/**
		 * Display the credits.
		 *
		 * @return void
		 */
		public function credits() {
			?>
			<p><strong>
				<?php _e( 'Want to help make this plugin even better? All donations are used to improve and support, so donate $20, $50 or $100 now!', AUTHOR_CATEGORY_TEXT_DOMAIN ); ?></strong></p>
			<a class="" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K4MMGF5X3TM5L" target="_blank"><img type="image" src="https://www.paypalobjects.com/<?php echo get_locale(); ?>/i/btn/btn_donate_LG.gif" border="0" alt="PayPal Ñ The safer, easier way to pay online."></a>
			<p><?php _e( 'Or you could:', AUTHOR_CATEGORY_TEXT_DOMAIN ); ?></p>
			<ul>
					<li><a href="https://wordpress.org/plugins/author-category/"><?php _e( 'Rate the plugin 5&#9733; on WordPress.org', AUTHOR_CATEGORY_TEXT_DOMAIN ); ?></a></li>
					<li><a href="https://wordpress.org/plugins/author-category/"><?php _e( 'Blog about it &amp; link to the plugin page', AUTHOR_CATEGORY_TEXT_DOMAIN ); ?></a></li>
			</ul>
			<?php
		}

		/**
		 * Show the whole page!
		 *
		 * @return void
		 */
		public function show_page() {
			wp_enqueue_script( 'post' );
			do_action( __FILE__ . 'add_meta_boxes' );
			?>
			<div class="wrap">
				<?php screen_icon( 'options-general' ); ?>
				<h2><?php echo esc_html( $this->name ); ?></h2>
				<form action="options.php" method="POST">
					<div id="poststuff" class="metabox-holder has-right-sidebar">
						<div class="inner-sidebar">
							<!-- SIDEBAR BOXES -->
							<?php do_meta_boxes( __FILE__, 'side', $this ); ?>
						</div>
						<div id="post-body">
							<div id="post-body-content">
								<div id="titlediv"></div>
								<div id="postdivrich" class="postarea"></div>
								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<!-- BOXES -->
									<?php
									foreach ( $this->sections as $s ) {
										settings_fields( $s['option_group'] );
									}
									do_meta_boxes( __FILE__, 'normal', $this );
									?>
								</div>
							</div>
						</div>
						<br class="clear">
					</div>
				</form>
			</div>
			<?php
		}

		/**
		 * Registers all settings for the plugin.
		 *
		 * @return void
		 */
		public function register_settings() {
			foreach ( $this->sections as $s ) {
				add_settings_section( $s['id'], $s['title'], array( $this, 'section_callback' ), __FILE__ );
				register_setting( $s['option_group'], $this->option, array( $this, 'sanitize_callback' ) );
			}
			foreach ( $this->fields as $f ) {
				add_settings_field( $f['id'], $f['label'], array( $this, 'show_field' ), __FILE__, $f['section'], $f );
			}
		}
	} // end class

	$p = new Author_Category_Panel(
		array(
			'title'      => __( 'Author category settings', AUTHOR_CATEGORY_TEXT_DOMAIN ),
			'name'       => __( 'Author category', AUTHOR_CATEGORY_TEXT_DOMAIN ),
			'capability' => 'manage_options',
			'option'     => 'author_cat_option',
		)
	);
	// Section.
	$setting = $p->add_section(
		array(
			'option_group'      => 'author-cat-group',
			'sanitize_callback' => null,
			'id'                => 'author_cat_id',
			'title'             => __( 'Author Category settings', AUTHOR_CATEGORY_TEXT_DOMAIN ),
		)
	);
	// Checkbox field.
	$p->add_field(
		array(
			'label'   => __( 'Check none by default', AUTHOR_CATEGORY_TEXT_DOMAIN ),
			'std'     => false,
			'id'      => 'check_multi',
			'type'    => 'checkbox',
			'section' => $setting,
			'desc'    => __( 'When using multiple categories they are all checked by default, check this box to disable that.', AUTHOR_CATEGORY_TEXT_DOMAIN ),
		)
	);
	$p->add_help_tab(
		array(
			'id'      => 'author_cat_help_id',
			'title'   => __( 'Author Category', AUTHOR_CATEGORY_TEXT_DOMAIN ),
			'content' => '<div style="min-height: 350px">
				<h2 style="text-align: center;">' . __( 'Author Category', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</h2>
				<div>
						<p>' . __( 'If you have any questions or problems head over to', AUTHOR_CATEGORY_TEXT_DOMAIN ) . ' <a href="https://wordpress.org/support/plugin/author-category">' . __( 'Plugin Support', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</a></p>
						<p>' . __( 'If you like my work then please ', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '<a class="button button-primary" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K4MMGF5X3TM5L" target="_blank">' . __( 'Donate', AUTHOR_CATEGORY_TEXT_DOMAIN ) . '</a>
				</div>
		</div>
		',
		)
	);
} // end if
