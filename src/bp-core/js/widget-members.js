jQuery(document).ready( function() {
	member_widget_click_handler();

	// WP 4.5 - Customizer selective refresh support.
	if ( 'undefined' !== typeof wp && wp.customize && wp.customize.selectiveRefresh ) {
		wp.customize.selectiveRefresh.bind( 'partial-content-rendered', function() {
			member_widget_click_handler();
		} );
	}
    
    // Set the interval and the namespace event
    if ( typeof wp !== 'undefined' && typeof wp.heartbeat !== 'undefined' ) {
        jQuery( document ).on( 'heartbeat-send', function ( event, data ) {
            if ( jQuery( '#boss_whos_online_widget_heartbeat' ).length ) {
                data.boss_whos_online_widget = jQuery( '#boss_whos_online_widget_heartbeat' ).data( 'max' );
            }
            if ( jQuery( '#boss_recently_active_widget_heartbeat' ).length ) {
                data.boss_recently_active_widget = jQuery( '#boss_recently_active_widget_heartbeat' ).data( 'max' );
            }
            jQuery( '.bs-heartbeat-reload' ).removeClass( 'hide' );
        } );

        jQuery( document ).on( 'heartbeat-tick', function ( event, data ) {
            // Check for our data, and use it.
            if ( jQuery( '#boss_whos_online_widget_total_heartbeat' ).length ) {
                jQuery( '#boss_whos_online_widget_total_heartbeat' ).html( data.boss_whos_online_widget_total );
            }
            if ( jQuery( '#boss_whos_online_widget_heartbeat' ).length ) {
                jQuery( '#boss_whos_online_widget_heartbeat' ).html( data.boss_whos_online_widget );
            }
            if ( jQuery( '#boss_recently_active_widget_heartbeat' ).length ) {
                jQuery( '#boss_recently_active_widget_heartbeat' ).html( data.boss_recently_active_widget );
            }
            jQuery( '.bs-heartbeat-reload' ).addClass( 'hide' );
        } );

    }
});

function member_widget_click_handler() {
	jQuery('.widget div#members-list-options a').on('click',
		function() {
			var link = this;
			jQuery(link).addClass('loading');

			jQuery('.widget div#members-list-options a').removeClass('selected');
			jQuery(this).addClass('selected');

			jQuery.post( ajaxurl, {
				action: 'widget_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery('input#_wpnonce-members').val(),
				'max-members': jQuery('input#members_widget_max').val(),
				'filter': jQuery(this).attr('id')
			},
			function(response)
			{
				jQuery(link).removeClass('loading');
				member_widget_response(response);
			});

			return false;
		}
	);
}

function member_widget_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] !== '-1' ) {
		jQuery('.widget ul#members-list').fadeOut(200,
			function() {
				jQuery('.widget ul#members-list').html(response[1]);
				jQuery('.widget ul#members-list').fadeIn(200);
			}
		);

	} else {
		jQuery('.widget ul#members-list').fadeOut(200,
			function() {
				var message = '<p>' + response[1] + '</p>';
				jQuery('.widget ul#members-list').html(message);
				jQuery('.widget ul#members-list').fadeIn(200);
			}
		);
	}
}
