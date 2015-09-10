( function( $ ) {
	$( document ).ready( function() {

		$( function() {
			$( "#rrrlgvwr-from" ).datepicker( {
				minDate: new Date( 2015, 0, 1 ), 
				onClose: function( selectedDate ) {
					$( "#rrrlgvwr-to" ).datepicker( "option", "minDate", selectedDate );
				}
			});
			$( "#rrrlgvwr-to" ).datepicker( {
				maxDate: new Date( 2016, 0, 1 ),
				onClose: function( selectedDate ) {
					$( "#rrrlgvwr-from" ).datepicker( "option", "maxDate", selectedDate );
				}
			});
		});

		$( "#rrrlgvwr-settings input" ).bind( "click select", function() {
			if ( $( this ).attr( "type" ) != 'submit' ) {
				$( ".updated.fade" ).css( "display", "none" );
				$( "#rrrlgvwr-settings-notice" ).css( "display", "block" );
			};
		});

		$( ".rrrlgvwr-email-field" ).css( "display", "none" );
		$( ".rrrlgvwr-interval-field" ).css( "display", "none" );
		
		$( "#rrrlgvwr-send-email" ).change( function() {
			if ( $( this ).prop( "checked" ) == true ) {
				$( ".rrrlgvwr-email-field" ).css( "display", "table-row" );
				$( ".rrrlgvwr-interval-field" ).css( "display", "table-row" );
			} else {
				$( ".rrrlgvwr-email-field" ).css( "display", "none" );
				$( ".rrrlgvwr-interval-field" ).css( "display", "none" );
			}
		}).change();

		var confirmFilesize = rrrlgvwr_confirm.confirm_filesize;
		var confirmMes		= rrrlgvwr_confirm.confirm_mes;
		var clearmMes		= rrrlgvwr_confirm.clear_mes;
		$( "#rrrlgvwr-show-all" ).click( function() {
			if ( confirmFilesize > 10485760 )
				return confirm( confirmMes );
		});

		$( "#rrrlgvwr-clear-file" ).click( function() {
			return confirm( clearmMes );
		});

	});
})(jQuery);