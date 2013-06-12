(function( $ ) {
  console.log( 'hi' );
  var form = $( 'form.feedinput-admin-content' );

  var feedList = form.find( '.feeds ul' );

  var template = form.find( 'script[data-template="row"]').html();

  // Add Feed button
  form.find( '[data-action="add-row"]' ).on( 'click', function() {
    feedList.append( template.replace( /\[%UID%\]/g, '['+(new Date().getTime())+']' ) );
    return false;
  });

  // Delete buttons
  feedList.on( 'click', 'a[data-action="delete"]', function( event ) {
    var li = $(this).closest( 'li' );
    li.remove();
    return false;
  });
})( jQuery );