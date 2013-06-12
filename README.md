# FeedInput #

A WordPress plugin for pulling items from RSS and ATOM feeds into WordPress as posts.

## API ##

### feedinput_register_feed ###

```php
<?php feedinput_register_feed( $feed_name, $feed_urls, $options ); ?>
```

  
@param string $feed_name - The name of this feed set
@param array $feed_urls - Array of URLs with optional meta data
@param array $options - Various options for this feed set

$feed_urls:
  It can be an array of strings that are the URL.
```
    array( 'http://example.com/feed/', 'http://wordpress.org/feed' )

  Or each can be an array with meta data.
    array(
      array(
        'url' => 'http://example.com/feed',
        'custom_term' => 'Example',
      ),
      array(
        'url' => 'http://wordpress.org/feed',
        'custom_term' => 'WordPress'
      )
    )
```

$options:
```
 array(
   // Maps the item data to the post and meta fields
   // The key is the name of the post field (eg. post_content) or post meta key.
   // The value is an array with two values: the type is 'literal', 'field', or 'callback';
   // the value is the either the literal value, the name of the field in the item data,
   // or a callback that accepts the data array.
   'convert' => array(
     // Maps the item data to the post fields
     'post' => array( 'post_field_name' => array( 'type' => 'field', 'value' => 'field_name') ),
     // Maps the item data to the post meta data
     'meta' => array( 'metakey' => array( 'type' => 'callback', 'value => 'callback_name' ) ),
   ),

   // Flag to automatically convert the items to a post
   'convert_to_post' => true,

   // The post type to save the converted items to
   'convert_post_type' => 'post',

   // Duration of days before deleting the hidden post types (feedinput_item) for downloaded feed items.
   // Warning: The feedinput_item is the only way to know if an item has been pulled.
   // If the item is deleted but the item is still in the feed the next time it is checked, the item
   // will be pulled down again.
   // Set to false to not delete
   'days_before_delete_items' => 356
 )
```

### feedinput_force_update_feed ####

```php
<?php feedinput_force_update_feed( $feed_name ); ?>
```

### feedinput_get_feed ###

```php
<?php feedinput_get_feed( $feed_name ); ?>
```