;(function( $, window, document, undefined ) {
	$(document).ready(function($){

		$( '#post' ).attr( 'enctype', 'multipart/form-data' ).attr( 'encoding', 'multipart/form-data' );

		$( 'a.deletefile' ).click(function () {
			var parent = $( this ).parent(),
				data = $( this ).attr( 'rel' ),
				_wpnonce = $( 'input[name=\'_wpnonce\']' ).val();

			$.post(
				ajaxurl,
				{action: 'pix_meta_unlink_file', _wpnonce: _wpnonce, data: data},
				function(response){
					//$("#info").html(response).fadeOut(3000);
					//alert(data.post);
				},
				'json'
			);
			parent.fadeOut( 'slow' );
			return false;
		});

		$( '.add' ).click(function() {
			$( '#newimages p:first-child' ).clone().insertAfter( '#newimages p:last' ).show();
			return false;
		});

		$( '.remove' ).click(function() {
			$( this ).parent().remove();
		});

		var pixImageSortable = $( '.pix-sortable' );

		pixImageSortable.sortable({
			update: function(event, ui) {
				var loader = $( '.pix-loading-animation' ).show(); // Show the animate loading gif while waiting

				opts = {
					url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
					type: 'POST',
					async: true,
					cache: false,
					dataType: 'json',
					data:{
						action: 'pix_meta_box_image_order', // Tell WordPress how to handle this ajax request
						order: pixImageSortable.sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
					},
					success: function(response) {
						loader.hide(); // Hide the loading animation
						return;
					},
					error: function(xhr,textStatus,e) {  // This can be expanded to provide more information
						alert('There was an error saving the updates');
						loader.hide(); // Hide the loading animation
						return;
					}
				};
				$.ajax( opts );
			}
		});
	});
})( jQuery, this, this.document );