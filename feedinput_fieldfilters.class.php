<?php

/**
 * Collection of callbacks to use with converting feed item data into
 * a post.
 */
class FeedInput_FieldFilters {

	/**
	 * Generate the post_name from the item's title
	 */
	static function post_name( $data ) {
		return sanitize_title( $data['title'] );
	}


	/**
	 * Try to match the first author with a WordPress user.
	 */
	static function post_author( $data, $feedset, $args ) {
		global $wpdb;

		// Get the configuration
		$default_options = array(
			'post_author' => array(
				'find_wordpress_user' => true,
				'default_author_id' => 0,
			)
		);
		$feedset_options = $feedset->options;
		$feed_options = $feedset->get_feed_settings( $data['feed_url'] );
		$options = array_merge( $default_options, $feedset_options, $feed_options );

		$find_wordpress_user = isset( $options['post_author']['find_wordpress_user'] ) ? $options['post_author']['find_wordpress_user'] : true;

		// Try to find a matching WordPress user
		if ( $find_wordpress_user &&  !empty( $data['authors'][0]['name']) || !empty( $data['authors'][0]['email']) ) {
			$where = array();
			if ( !empty( $data['authors'][0]['name']) ) {
				$where[] = $wpdb->prepare( "user_nicename = '%s'", $data['authors'][0]['name'] );
				$where[] = $wpdb->prepare( "user_login = '%s'", $data['authors'][0]['name'] );
			}
			if ( !empty( $data['authors'][0]['email']) ) {
				$where[] = $wpdb->prepare( "user_email = '%s'", $data['authors'][0]['email'] );
			}
			$query = "SELECT user.ID FROM {$wpdb->users} AS user WHERE " . implode( ' OR ', $where ) . ' LIMIT 0, 1';
			$user = $wpdb->get_row( $query );

			if ( !empty( $user->ID ) ) {
				return $user->ID;
			}
		}

		// Fallback to the default author
		$default_author_id = isset( $options['post_author']['default_author_id'] ) ? $options['post_author']['default_author_id'] : 0;
		return $default_author_id;
	}


	/**
	 * Convert the categories into tags
	 */
	static function tax_input( $data, $feedset, $args=array() ) {
		if ( empty($data) ) {
			return array();
		}

		$taxonomy = !empty($args['taxonomy']) ? $args['taxonomy'] : 'post_tag';

		$terms = array();
		foreach ( $data['categories'] as $term ) {
			$terms[] = $term['label'];
		}

		return array( "$taxonomy" => $terms );
	}


	/**
	 * Find an image in the content to make into the featured image
	 */
	static function featured_image( $data ) {
		//get download_url, media_handle_sideload, etc
		if ( !defined('WP_ADMIN') && WP_ADMIN ) {
			require_once( ABSPATH . '/wp-admin/includes/admin.php' );
		}

		try {
			if ( preg_match( '#<img\s[^>]*\s?src=("(?P<url1>[^"]+)"|\'(?P<url2>[^\']+)\')#', $data['content'], $matches ) ) {
				$url = !empty( $matches['url1'] ) ? $matches['url1'] : $matches['url2'];

				// Download file to temp location
				$tmp = download_url( $url );

				// Set variables for storage
				// fix file filename for query strings
				$file_array['name'] = ( preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $url, $matches ) ) ? basename($matches[0]) : basename( $url );
				$file_array['tmp_name'] = $tmp;

				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
					return null;
				}

				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, 0 );

				// Image is bad
				if ( is_wp_error( $id ) ) {
					@unlink($file_array['tmp_name']);
					return null;
				}
				return $id;
			}
		} catch ( Exception $e ) {
			error_log( $e->getTraceAsString() );
		}

		return null;
	}

	/**
	 * Allow each feed to define the literal value
	 *
	 * Pass an associative array to $args of:
	 *   array(
	 *     'default_value' => 'value',
	 *     'field_name' => 'name'
	 *   )
	 */
	static function literal_per_feed( $data, $feedset, $args ) {
		$feedset_options = $feedset->options;
		$feed_options = $feedset->get_feed_settings( $data['feed_url'] );
		$options = array_merge( $feedset_options, $feed_options );

		$default_value = isset( $args['default_value'] ) ? $args['default_value'] : null;
		$field_name = !empty( $args['field_name'] ) ? $args['field_name'] : false;

		if ( $field_name == false || !isset( $options[$field_name] ) ) {
			return $default_value;
		}

		return $options[$field_name];
	}


	/**
	 *
	 */
	static function post_content( $data, $feedset, $args ) {
		$feedset_options = $feedset->options;
		$feed_options = $feedset->get_feed_settings( $data['feed_url'] );
		$options = array_merge( $feedset_options, $feed_options );

		$content = $data['content'];

		if ( isset( $options['post_content'] ) && is_array( $options['post_content'] ) ) {

			$prefix = isset( $options['post_content']['prefix'] ) ? $options['post_content']['prefix'] : '';
			$postfix = isset( $options['post_content']['postfix'] ) ? $options['post_content']['postfix'] : '';

			$content = $prefix . $content . $postfix;
		}

		return $content;
	}
}