( function( $ ) {
	$( document ).ready( function() {
		$( function() {
			var from = $( "#rrrlgvwr-from" ).datepicker({});
			var	to = $( "#rrrlgvwr-to" ).datepicker({});
				
				from.on( "change", function(){
					var minDate = from.val();
					to.datepicker( "option", "minDate", minDate );
				});
		});

		$( ".rrrlgvwr-email-field" ).css( "display", "none" );
		$( ".rrrlgvwr-interval-field" ).css( "display", "none" );
		
		$( "#rrrlgvwr-send-email" ).on( "change", function() {
			if ( $( this ).prop( "checked" ) == true ) {
				$( ".rrrlgvwr-email-field" ).css( "display", "table-row" );
				$( ".rrrlgvwr-interval-field" ).css( "display", "table-row" );
			} else {
				$( ".rrrlgvwr-email-field" ).css( "display", "none" );
				$( ".rrrlgvwr-interval-field" ).css( "display", "none" );
			}
		}).trigger( "change" );

		var confirmFilesize = rrrlgvwr_confirm.confirm_filesize;
		var confirmMes		= rrrlgvwr_confirm.confirm_mes;
		var clearmMes		= rrrlgvwr_confirm.clear_mes;
		$( "#rrrlgvwr-show-all" ).on( "click", function() {
			var tenMb = 10485760;
			if ( confirmFilesize > tenMb )
				return confirm( confirmMes );
		});

		$( "#rrrlgvwr-clear-file" ).on( "click", function() {
			return confirm( clearmMes );
		});
	});
})(jQuery);