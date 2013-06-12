(function( $ ) {
  console.log( 'hi' );
  var form = $( 'form.feedinput-admin-content' );

  var feedList = form.find( '.feeds ul' );

  var template = form.find( 'script[data-template="row"]').html();

  // Add Feed button
  form.find( '[data-action="add-row"]' ).on( 'click', function() {
    feedList.append( template );
    return false;
  });
})( jQuery );