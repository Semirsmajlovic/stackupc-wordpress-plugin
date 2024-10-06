(function($) {
	'use strict';

	$(function() {
		$('#stackupc_search_button').on('click', function() {
			var upcCode = $('#stackupc_upc_code').val();
			if (!upcCode) {
				alert('Please enter a UPC code');
				return;
			}

			$.ajax({
				url: stackupc_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'stackupc_search',
					nonce: stackupc_ajax.nonce,
					upc_code: upcCode
				},
				beforeSend: function() {
					$('#stackupc_search_button').prop('disabled', true).text('Searching...');
				},
				success: function(response) {
					if (response.success) {
						$('#stackupc_results_container').html(response.data);
					} else {
						alert('Error: ' + response.data);
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
				},
				complete: function() {
					$('#stackupc_search_button').prop('disabled', false).text('Search');
				}
			});
		});

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
