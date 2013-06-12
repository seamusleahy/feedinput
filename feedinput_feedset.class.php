<?php
/**
 * Represents a set of Feeds.
 */
class FeedInput_FeedSet {

	var $name; // The name of the feeds
	var $urls; // array of URLs
	var $options;

	/**
	 * @param string $feed_name - The name of this feed set
	 * @param array $feed_urls - Array of URLs with optional meta data
	 * @param array $options - Various options for this feed set
	 *
	 * $feed_urls:
   *  It can be an array of strings that are the URL.
   *    array( 'http://example.com/feed/', 'http://wordpress.org/feed' )
   *
   *  Or each can be an array with meta data.
   *    array(
   *      array(
   *        'url' => 'http://example.com/feed',
   *        'custom_term' => 'Example',
   *      ),
   *      array(
   *        'url' => 'http://wordpress.org/feed',
   *        'custom_term' => 'WordPress'
   *      )
   *    )
	 *
	 * $options:
	 *
	 * array(
	 *   // Maps the item data to the post and meta fields
	 *   // The key is the name of the post field (eg. post_content) or post meta key.
	 *   // The value is an array with two values: the type is 'literal', 'field', or 'callback';
	 *   // the value is the either the literal value, the name of the field in the item data,
	 *   // or a callback that accepts the data array.
	 *   'convert' => array(
	 *     // Maps the item data to the post fields
	 *     'post' => array( 'post_field_name' => array( 'type' => 'field', 'value' => 'field_name') ),
	 *     // Maps the item data to the post meta data
	 *     'meta' => array( 'metakey' => array( 'type' => 'callback', 'value => 'callback_name' ) ),
	 *   ),
	 *
	 *   // Flag to automatically convert the items to a post
	 *   'convert_to_post' => true,
	 *
	 *   // The post type to save the converted items to
	 *   'convert_post_type' => 'post',
	 *
	 *   // Duration of days before deleting the hidden post types (feedinput_item) for downloaded feed items.
	 *   // Warning: The feedinput_item is the only way to know if an item has been pulled.
	 *   // If the item is deleted but the item is still in the feed the next time it is checked, the item
	 *   // will be pulled down again.
	 *   // Set to false to not delete
	 *   'days_before_delete_items' => 356
	 * )
	 */
	function __construct( $feed_name, $feed_urls, $options ) {
		$this->name = $feed_name;

		// Convert feed URLs into array with meta data
		$urls = array();
		foreach ( $feed_urls as $feed_url ) {
			if ( is_array( $feed_url ) ) {
				$urls[] = $feed_url;
			} else {
				$urls[] = array(
					'url' => $feed_url,
				);
			}
		}

		$this->urls = $urls;

		$default = array(
			// Options for converting an item into a post
			'convert' => array(),
			'convert_to_post' => true,
			'convert_post_type' => 'post',
			'days_before_delete_items' => 356,
		);

		$this->options = array_merge( $default, $options );

		$this->options['convert'] = array_merge( array(
			'post' => array(),
			'meta' => array(),
			'callbacks' => array(),
		), $this->options['convert'] );
	}


	/**
	 * Get the feed settings by URl of the feed
	 */
	function get_feed_settings( $feed_url ) {
		foreach ( $this->urls as $feed ) {
			if ( $feed['url'] == $feed_url ) {
				return $feed;
			}
		}

		return array();
	}


	/**
	 * Call to update the feed set
	 */
	function update() {

		$urls = array();
		foreach ( $this->urls as $url ) {
			$urls[] = $url['url'];
		}

		$feed = fetch_feed( $urls );

		$items = FeedInput_FeedItem::parse_feed_items( $feed->get_items(), $this );

		foreach ( $items as $item ) {
			$item->save( $this );
		}
		
		if ( $this->options['convert_to_post']) {
			foreach ( $items as $item ) {
				$item->convert_to_post( $this->options['convert'], $this );
			}
		}
	}


	/**
	 * Delete expired items
	 */
	function delete_expired_items() {
		if ( !$this->options['days_before_delete_items'] ) {
			return;
		}

		add_filter( 'posts_where', array( &$this, 'delete_expired_items_posts_where') );

		$query = new WP_Query( array(
			'post_type' => 'feedinput_item',
			'posts_per_page' => 100,
			'post_status' => array( 'publish', 'draft' ),
			'tax_query' => array(
				array(
					'taxonomy' => 'feedinput_feed',
					'field' => 'slug',
					'terms' => $this->name,
				)
			)
		) );

		remove_filter( 'posts_where', array( &$this, 'delete_expired_items_posts_where') );

		foreach ( $query->posts as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}

	/**
	 * Add to the where clause when deleting expired items
	 */
	function delete_expired_items_posts_where( $where ) {
		$where .= " AND post_date < '" . date('Y-m-d', time() - $this->options['days_before_delete_items'] * 60 * 60 * 24 ) . "'";
		return $where;
	}


	/**
	 * Retrieve saved items
	 *
	 * @param int $number_of_items - the number of items to return
	 * @param int $page - the page of results to retrieve
	 */
	function get_items( $number_of_items=10, $page=1 ) {
		return FeedInput_FeedItem::get_items( $this, $number_of_items, $page );
	}
	

	/**
	 * Convert an item in the feed into a post
	 */
	function convert_item_to_post( $uid ) {
		$item = FeedInput_FeedItem::get_item( $this, $uid );
		if ( !empty( $item ) ) {
			return $item->convert_to_post( $this->options['convert'], $this );
		}

		return null;
	}


	function remove_item( $uid ) {
		$item = FeedInput_FeedItem::get_item( $this, $uid );
		if ( !empty( $item ) ) {
			return $item->remove_item();
		}

		return null;
	}
}