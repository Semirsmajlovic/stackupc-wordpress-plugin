(function($) {
	'use strict';

	$(function() {
		$('#stackupc_search_button').on('click', function(e) {
			e.preventDefault(); // Prevent the default form submission
			var upcCode = $('#stackupc_upc_code').val().trim();
			console.log('UPC Code entered:', upcCode);
			if (!upcCode) {
				alert('Please enter a UPC code');
				return;
			}
			performUpcSearch(upcCode);
		});

		function performUpcSearch(upcCode) {
			console.log('Initiating UPC search for:', upcCode);
			$.ajax({
				url: stackupc_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'stackupc_search',
					nonce: stackupc_ajax.nonce,
					upc_code: upcCode
				},
				beforeSend: function() {
					console.log('Sending AJAX request...');
					$('#stackupc_search_button').prop('disabled', true).text('Searching...');
				},
				success: function(response) {
					console.log('AJAX request successful:', response);

					if (response.success) {
						console.log('UPC search successful, updating results container.');
						$('#stackupc_results_container').html(response.data);
					} else {
						console.warn('UPC search failed:', response.data);
						alert(`Error: ${response.data || 'Unexpected error occurred.'}`);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('AJAX Error:', textStatus, errorThrown);
					alert('An error occurred while processing your request. Please try again.');
				},
				complete: function() {
					console.log('AJAX request complete.');
					$('#stackupc_search_button').prop('disabled', false).text('Search');
				}
			});
		}

		$(document).on('click', '.stackupc-import-button', function(e) {
			e.preventDefault();
			var $button = $(this);
			var itemData = $button.data('item');

			$.ajax({
				url: stackupc_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'stackupc_import',
					nonce: stackupc_ajax.import_nonce,
					item_data: JSON.stringify(itemData)
				},
				beforeSend: function() {
					$button.prop('disabled', true).text('Importing...');
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						$button.text('Imported').addClass('button-disabled');
					} else {
						alert('Error: ' + response.data);
						$button.prop('disabled', false).text('Import');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$button.prop('disabled', false).text('Import');
				}
			});
		});
	});

})(jQuery);
