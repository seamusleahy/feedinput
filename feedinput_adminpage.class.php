<?php
require_once __DIR__ . '/sh_walker_taxonomydropdown.class.php' ;

/**
 * Creates the admin UI page
 */
class FeedInput_AdminPage {
	var $feed_urls;

	function __construct() {
		add_action( 'admin_menu', array( &$this, 'admin_menu') );
		add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );
		add_action( 'init', array( &$this, 'register_feedset') );
		add_action( 'feedinput_convert_to_post-feedinput_admin', array(&$this, 'convert_post_action'), 3, 10 );
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'manage_taxonomies_for_feed-item_columns', array( &$this, 'manage_taxonomies_columns' ) );
		add_action( 'restrict_manage_posts', array( &$this, 'post_table_filters' ) );
	}


	/**
	 * Add our admin page
	 */
	function admin_menu() {
		$this->hook_suffix = add_options_page('FeedInput','FeedInput','manage_options','feedinput', array($this, 'page_content') );
		add_action( 'load-'.$this->hook_suffix, array(&$this, 'process') );
	}


	/**
	 * Output the content of the page
	 */
	function page_content() {
		$feeds = $this->get_feed_urls();

		$expire_options = apply_filters( 'feedinput_admin_expire_drafts_options', array(
			'1 day'   => 1,
			'7 days'  => 7,
			'15 days' => 15,
			'30 days' => 30,
			'90 days' => 90,
			'Never'   => 0
		));
		?>
		<pre>
		</pre>
		<div class="wrap">
			<h2><?php _e('FeedInput', 'feedinput'); ?></h2>

			<form method="POST" action="options-general.php?page=feedinput" class="feedinput-admin-content">
				<?php wp_nonce_field( 'update_feed_urls', 'feedinput_nonce' ); ?>
				<input type="hidden" name="feedinput" value="1" />

				<div class="feeds">
					<h3>Feed Sources</h3>
					<ul>
						<?php
						$uid = 0;
						foreach ( $feeds as $feed ): 
							++$uid;
						?>
						<li>
							<a data-action="delete" class="delete-button" title="<?php esc_attr_e( 'Delete', 'feedinput' ); ?>">&minus;</a>
							<p>
								<label>
									<span class="label"><?php _e( 'Media Source Name', 'feedinput' ); ?></span>
									<input class="text" type="text" name="name[<?php echo $uid; ?>]" value="<?php echo esc_attr( $feed['name'] ); ?>" />
								</label>
							</p>

							<p>
								<label>
									<span class="label"><?php _e( 'Media Feed URL', 'feedinput' ); ?></span>
									<input class="text" type="text" name="url[<?php echo $uid; ?>]" value="<?php echo esc_attr( $feed['url'] ); ?>"  />
								</label>
							</p>

							<p>
								<label>
									<span class="label"><?php _e( 'Default Author', 'feedinput' ); ?></span>
									<?php wp_dropdown_users( array( 'name' => 'default_author['.$uid.']', 'selected' => $feed['default_author'] ) ); ?>
								</label>
							</p>

							<p>
								<label>
									<span class="label"><?php _e( 'Expire Drafts', 'feedinput' ); ?></span>
									<select name="expire_draft[<?php echo $uid; ?>]">
										<?php 
										foreach ( $expire_options as $label => $val ) {
											echo '<option value="', $val, '" ', selected( $val, $feed['expire_draft']), '>', __( $label, 'feedinput') ,'</option>';
										} ?>
									</select>
								</label>
							</p>

							<p>
								<label>
									<span class="label"><?php _e( 'Auto Publish', 'feedinput' ); ?></span>
									<input type="checkbox" name="auto_publish[<?php echo $uid; ?>]" <?php checked( $feed['auto_publish'] ); ?> />
								</label>
							</p>

							<p>
								<label>
									<span class="label"><?php _e( 'Add Credit', 'feedinput' ); ?></span>
									<input type="checkbox" name="add_credit[<?php echo $uid; ?>]" <?php checked( $feed['add_credit'] ); ?> />
								</label>
							</p>
						</li>
						<?php endforeach; ?>
					</ul>

					<div class="buttons">
						<a class="button" data-action="add-row"><?php _e( 'Add Feed', 'feedinput' ); ?></a>
					</div>
				</div>

				<script type="template" data-template="row">
					<li>
						<a data-action="delete" class="delete-button" title="<?php esc_attr_e( 'Delete', 'feedinput' ); ?>">&minus;</a>
						<p>
							<label>
								<span class="label"><?php _e( 'Media Source Name', 'feedinput' ); ?></span>
								<input class="text" type="text" name="name[%UID%]" />
							</label>
						</p>

						<p>
							<label>
								<span class="label"><?php _e( 'Media Feed URL', 'feedinput' ); ?></span>
								<input class="text" type="text" name="url[%UID%]" />
							</label>
						</p>

						<p>
							<label>
								<span class="label"><?php _e( 'Default Author', 'feedinput' ); ?></span>
								<?php wp_dropdown_users( array( 'name' => 'default_author[%UID%]') ); ?>
							</label>
						</p>

						<p>
							<label>
								<span class="label"><?php _e( 'Expire Drafts', 'feedinput' ); ?></span>
								<select name="expire_draft[%UID%]">
									<?php 
									foreach ( $expire_options as $label => $val ) {
										echo '<option value="', $val, '">', __( $label, 'feedinput') ,'</option>';
									} ?>
								</select>
							</label>
						</p>

						<p>
							<label>
								<span class="label"><?php _e( 'Auto Publish', 'feedinput' ); ?></span>
								<input type="checkbox" name="auto_publish[%UID%]" />
							</label>
						</p>

						<p>
							<label>
								<span class="label"><?php _e( 'Add Credit', 'feedinput' ); ?></span>
								<input type="checkbox" name="add_credit[%UID%]" />
							</label>
						</p>
					</li>
				</script>

<?php /*
				<label for="feed_urls"><?php _e('Feed URLs', 'feedinput'); ?></label>
				<p><?php _e('Enter each feed URL on a separate line. You can prefix a URL with the <a href="' . admin_url('edit-tags.php?taxonomy=media-sources') .  '">media source</a> term name to assign the converted posts. Example: Arnold Times | http://arnoldtimesonline.com/feed', 'feedinput'); ?></p>
				<textarea id="feed_urls" name="feed_urls" style="width: 100%" rows="10"><?php
					$lines = array();
					foreach ( $feed_urls as $url ) {
						if ( !empty( $map[$url] ) ) {
							$lines = $map[$url] . ' | ' . $url;
						} else {
							$lines = $url;
						}
					}
					echo implode( "\n", $lines );
				?></textarea> */ ?>

				<div class="submit-buttons">
					<button type="submit" class="button-primary"><?php _e('Save'); ?></button>
				</div>
			</form>
		</div>
		<?php
	}


	/**
	 * Add our JS and CSS for the admin page
	 */
	function admin_enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix != $this->hook_suffix ) {
			return;
		}


		wp_enqueue_script( 'feedinput-admin', plugins_url( 'feedinput-admin.js', __FILE__ ), array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'feedinput-admin', plugins_url( 'feedinput-admin.css', __FILE__ ), '1.0' );
	}


	/**
	 * Attempts to process the form submission
	 */
	function process() {
		$current_screen = get_current_screen();

		if ( $current_screen->id == 'settings_page_feedinput' && filter_input(INPUT_POST, 'feedinput', FILTER_VALIDATE_BOOLEAN) ) {
			if ( empty($_POST['feedinput_nonce']) || !wp_verify_nonce($_POST['feedinput_nonce'],'update_feed_urls') ) {
				// The nonce did not match
				return;
			}

			// Store for later use
			$old_feeds = $this->get_feed_urls();

			// Get input

			$urls = $_POST['url'];
			$names = $_POST['name'];
			$default_authors = $_POST['default_author'];
			$auto_publishes = $_POST['auto_publish'];
			$expire_drafts = $_POST['expire_draft'];
			$add_credits = $_POST['add_credit'];

			$feeds = array();
			$feed_urls = array();
			if ( is_array( $urls ) ) {
				foreach( $urls as $key => $url ) {
					if ( !empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
						$feed_urls[] = $url;
						$feeds[] = array(
							'url'            => $url,
							'name'           => $names[$key],
							'default_author' => (int) $default_authors[$key],
							'auto_publish'   => !empty( $auto_publishes[$key] ) ? filter_var( $auto_publishes[$key], FILTER_VALIDATE_BOOLEAN ) : false,
							'expire_draft'   => (int) $expire_drafts[$key],
							'add_credit'     => !empty( $add_credits[$key] ) ? filter_var( $add_credits[$key], FILTER_VALIDATE_BOOLEAN ) : false,
							'source_taxonomy' => array(
								'taxonomy' => 'media-sources',
								'term' => $names[$key],
							),
						);
					}
				}
			}


			// echo '<pre>';
			// var_dump( $feeds );
			// echo '</pre>';

			// // Get input
			// $feed_urls_raw = filter_input( INPUT_POST, 'feed_urls', FILTER_SANITIZE_STRING );
			// $lines = explode( "\n", $feed_urls_raw );
			// $urls  = array();
			// $taxonomy_map = array();

			// foreach ( $lines as $line ) {
			// 	preg_match( '#[\s|]+([^\s|]+)\s*$#', $line, $matches );
			// 	if ( isset( $matches[1] ) ) {
			// 		$urls = $matches[1];
			// 		$taxonomy_map[$matches[1]] = str_replace( $matches[0], '', $line );
			// 	} else {
			// 		$urls = trim($line);
			// 	}
			// }

			// $feed_urls = array();
			// foreach ( $urls as $url ) {
			// 	$url = filter_var( $url, FILTER_SANITIZE_URL );

			// 	if ( $url != false ) {
			// 		$feed_urls = $url;
			// 	}
			// }

			// Save the feeds
			$this->set_feed_urls( $feeds );
			//$this->set_taxonomy_map( $taxonomy_map );
			$this->register_feedset();

			// Check if there are new feeds added, if not then force an initial update
			
			$old_feed_urls = array();
			foreach ( $old_feeds as $feed ) {
				$old_feed_urls[] = $feed['url'];
			}
			$new_feed_urls = array_diff( $feed_urls, $old_feed_urls );
			
			if ( count( $new_feed_urls ) != 0 ) {
				feedinput_force_update_feed( 'feedinput_admin' );
			}

			add_action('admin_notices', array( $this , 'notify_change') );
		}
	}


	/**
	 * Display a notify message at the top of the page
	 */
	function notify_change() {
		echo '<div class="updated"><p>Sources updated.</p></div>';
	}


	/**
	 * Init hook
	 */
	function register_feedset() {
		$feed_urls = $this->get_feed_urls();

		if ( count( $feed_urls ) > 0 ) {
			$options = array(
				'convert_to_post' => true,
				'convert_post_type' => 'feed-item',
				'convert' => array(
					'post' => array(),
					'meta' => array(
						'largo_byline_text' => array( 'type' => 'field', 'value' => array('authors', 0, 'name') ),
						'largo_byline_link' => array( 'type' => 'field', 'value' => array('authors', 0, 'link') ),
					),
					'callbacks' => array(
					)
				)
			);

			$options = apply_filters( 'feedinput_admin_feed_set_options', $options, $feed_urls );
			feedinput_register_feed( 'feedinput_admin', $feed_urls, $options );
		}
	}


	/**
	 * Get the feeds
	 */
	function get_feed_urls() {
		if ( !is_array( $this->feed_urls ) ) {
			$this->feed_urls = get_option( 'feedinput_feeds', array() );
		}
		return $this->feed_urls;
	}


	/**
	 * Set the feeds
	 */
	function set_feed_urls( $feed_urls ) {
		$this->feed_urls = $feed_urls;
		update_option( 'feedinput_feeds', $feed_urls );
	}

	// function get_taxonomy_map() {
	// 	if ( !isset( $this->taxonomy_map ) || !is_array( $this->taxonomy_map ) ) {
	// 		$this->taxonomy_map = get_option( 'feedinput_taxonomy_map', array() );
	// 	}
	// 	return $this->taxonomy_map;
	// }

	// function set_taxonomy_map( $taxonomy_map ) {
	// 	$this->taxonomy_map = $taxonomy_map;
	// 	update_option( 'feedinput_taxonomy_map', $taxonomy_map );
	// }

	/**
	 * Action for converting a post to add custom Largo taxonomy
	 */
	function convert_post_action( $post, $data, $feedset ) {
		// $map = $this->get_taxonomy_map();
		// $feed_url = $data['feed_url'];

		// if ( !empty( $map[$feed_url] ) ) {
		// 	wp_set_post_terms( $post->ID, $map[$feed_url], 'media-sources', true );
		// }
	}


	/**
	 * Register the post type and taxonomy
	 */
	function register_post_type() {
		// The post type for the public display of feed items
		register_post_type( 'feed-item', array(
			'public' => true,
			'labels' => array(
				'name' =>          'Feed Items',
				'singular_name' => 'Feed Item',
				'edit_item' =>     'Edit Item',
				'new_item' =>      'New Item',
				'all_items' =>     'All Items',
				'view_item' =>     'View Item',
				'search_items' =>  'Search Items',
				'not_found' =>     'No items found',
				'not_found_in_trash' => 'No items found in Trash',
				'menu_name' =>     'Feed Items',
			),
			'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'revisions', 'page-attributes'),
		));

		register_taxonomy('media-sources',
			array( 'feed-item'),
			array(
				'labels' => array(
					'singular_name' => __('Media Source', 'feedinput'),
					'name' => __( 'Media Sources', 'feedinput' ),
				),
				'public' => true,
			) );
	}

	
	/**
	 * Manage the taxonomy columns
	 */
	function manage_taxonomies_columns( $taxonomies ) {
		$taxonomies[] = 'media-sources';
		return $taxonomies;
	}


	/**
	 * Add media source filter to the post table
	 */
	function post_table_filters() {
		global $wp_query;
		$screen = get_current_screen();

		if ( $screen->id == 'edit-feed-item' ) {
			$taxonomy = get_taxonomy( 'media-sources' );
			wp_dropdown_categories(array(
				'show_option_all' =>  __("Show All {$taxonomy->label}"),
				'taxonomy'        =>  'media-sources',
				'name'            =>  'media-sources',
				'orderby'         =>  'name',
				'selected'        =>  $wp_query->query['media-sources'],
				'hierarchical'    =>  false,
				'show_count'      =>  false, // Show # listings in parens
				'hide_empty'      =>  false,
				'walker'          => new SH_Walker_TaxonomyDropdown(),
				'value'           =>'slug',
			 ));
		}
	}


	

	


}

// Kickoff
new FeedInput_AdminPage;