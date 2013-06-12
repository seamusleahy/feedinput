<?php
/**
 * Collection of callbacks to use with converting feed items into posts.
 */
class FeedInput_ConvertCallbacks {
  
  /**
   * Add the converted posts to a taxonomy term for what URL it came from
   */
  static function source_taxonomy( $post, $data, $feedset ) {
    $options = $feedset->get_feed_settings( $data['feed_url'] );

    if ( isset($options['source_taxonomy']) && is_array($options['source_taxonomy']) ) {
      $taxonomy = $options['source_taxonomy']['taxonomy'];

      if ( empty( $taxonomy ) ) {
        return;
      }

      $term = $data['feed_title']; // Default

      if ( !empty( $options['source_taxonomy']['term'] ) ) {
        $term = $options['source_taxonomy']['term'];
      } elseif ( !empty( $options['name'] ) ) {
        $term = $options['name'];
      }

      wp_set_post_terms( $post->ID, $term, $taxonomy, true );
    }
  }


  
}