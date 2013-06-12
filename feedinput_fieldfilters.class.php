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
	static function post_author( $data ) {
		global $wpdb;

		if ( !empty( $data['authors'][0]['name']) || !empty( $data['authors'][0]['email']) ) {
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

		return 0;
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
		if ( preg_match( '#<img\s[^>]*\s?src=("(?P<url1>[^"]+)"|\'(?P<url2>[^\']+)\')#', $data['content'], $matches ) ) {
			$url = !empty( $matches['url1'] ) ? $matches['url1'] : $matches['url2'];
			
			// Download file to temp location
			$tmp = download_url( $url );

			// Set variables for storage
			// fix file filename for query strings
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $url, $matches );
			$file_array['name'] = basename($matches[0]);
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
		
		return null;
	}



}