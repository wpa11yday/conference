jQuery(($) => {
	var zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
	// Handle Internet Explorer's lack of timezone info.
	if ( undefined === zone ) {
		zone = 'your local time';
	}
	$( '.event-time' ).each( function ( index ) {
		var utcTime  = $( this ).attr( 'data-time' );
		var userTime = new Date( utcTime ).toLocaleTimeString().replace( ':00 ', ' ' );
		var userDate = new Date( utcTime ).toLocaleDateString();

		$( this ).append( '<span class="localtime">' + userDate + ' at ' + userTime + ' ' + zone + '</span>' );
	});
	$( 'h2.talk-time' ).each( function ( index ) {
		var utcTime  = $( this ).attr( 'data-time' );
		var userTime = new Date( utcTime ).toLocaleTimeString().replace( ':00 ', ' ' );

		$( this ).find( '.time-wrapper' ).append( '<span class="localtime">' + userTime + ' ' + zone + '</span>' );
	});

});
