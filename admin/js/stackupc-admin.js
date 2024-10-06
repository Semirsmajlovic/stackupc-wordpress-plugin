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
	});

})(jQuery);
