<?php


class FeedInput_ExpireConvertedPosts {

  static function init() {
    add_action( 'feedinput_delete_items', array( 'FeedInput_ExpireConvertedPosts', 'trash_expired_posts' ) );
    add_filter( 'feedinput_convert_post_meta_data', array( 'FeedInput_ExpireConvertedPosts', 'add_post_meta_map' ), 10, 3 );
  }

  /**
   * This will delete expired converted posts
   */
  static function trash_expired_posts () {
    $feedsets = feedinput_get_all_feeds();
    
    foreach ( $feedsets as $feedset ) {
      if ( !empty( $feedset->options['convert_post_type'] ) ) {
        do {
          $query = new WP_Query( array(
            'posts_per_page' => 30,
            'post_type'   => $feedset->options['convert_post_type'],
            'post_status' => 'draft',
            'meta_query'  => array(
              array(
                'key'     => 'feedinput_expiration_date',
                'value'   => date( 'Y-m-d' ),
                'compare' => '<',
                'type'    => 'date',
              ),
              array(
                'key'     => 'feed_name',
                'value'   => $feedset->name, 
              )
            )
          ) );

          foreach ( $query->posts as $post ) {
            wp_trash_post( $post->ID );
          }
        } while( $query->max_num_pages > 1 );
      }
    }
  }


  /**
   * Add an expiration meta data to feeds with it set
   */
  static function add_post_meta_map( $postmeta, $data, $feedset ) {
    $feedset_options = $feedset->options;
    $feed_options = $feedset->get_feed_settings( $data['feed_url'] );
    $options = array_merge( $feedset_options, $feed_options );

    if ( empty( $options['expire_converted_posts'] ) || !is_array($options['expire_converted_posts']) || $options['expire_converted_posts']['days_before_expire'] > 0) {
      $postmeta['feedinput_expiration_date'] = date( 'Y-m-d', time() + 60*60*24*$options['expire_converted_posts']['days_before_expire'] );
    }

    return $postmeta;
  }


  /**
   * Force an update of the expiration date
   */
  static function update_expiration_dates( $feedset ) {
    foreach ( $feedset->urls as $feed ) {
      $feedset_options = $feedset->options;
      $feed_options = $feed;
      $options = array_merge( $feedset_options, $feed_options );

      if ( empty( $options['expire_converted_posts'] ) || !is_array($options['expire_converted_posts']) || $options['expire_converted_posts']['days_before_expire'] > 0) {
        // Update
        $new_duration = 60*60*24*$options['expire_converted_posts']['days_before_expire'];
      } else {
        // Remove the items
        $new_duration = null;
      }

      do {
        $query = new WP_Query( array(
          'posts_per_page' => 30,
          'post_type'   => $feedset->options['convert_post_type'],
          'meta_query'  => array(
            array(
              'key'     => 'feed_url',
              'value'   => $feed['url'],
            ),
            array(
              'key'     => 'feed_name',
              'value'   => $feedset->name, 
            )
          )
        ) );

        foreach ( $query->posts as $post ) {
          // Get the created date
          $created_date = get_post_meta( $post->ID, 'feedinput_converted_date', true );
          if ( !empty( $created_date ) ) {
            $created_date = strtotime( $created_date );

            if ( empty( $new_duration ) ) {
              // Delete the field
              delete_post_meta( $post->ID, 'feedinput_expiration_date' );
            } else {
              // Update the field
              update_post_meta( $post->ID, 'feedinput_expiration_date', date( 'Y-m-d', $created_date+$new_duration ) );
            }
          }
        }
      } while( $query->max_num_pages > 1 );
    }
  }
}

FeedInput_ExpireConvertedPosts::init();
