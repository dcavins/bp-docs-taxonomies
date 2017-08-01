(function ( $ ) {
	"use strict";

	$( document ).ready( function() {
		// Collapse the Tags filter if it contains greater than 10 items
		$( ".term-filter-links" ).each( function( index, value ) {
			if ( $( this ).children( "li" ).length > 10 ) {
				terms_section_collapse( $( this ) );
			}
		} );

		$( ".docs-filter-section" ).on( "click", "a.terms-toggle-list-visibility", function( e ) {
			e.preventDefault();

			var filter_section = $( e.target ).parents( ".docs-filter-section" );
			filter_section.slideUp( 300, function() {
				var tag_button_action = $( e.target ).hasClass( "terms-unhide" ) ? "expand" : "collapse";

				if ( "expand" == tag_button_action ) {
					terms_section_expand( filter_section.children( "ul" ) );
				} else if ( "collapse" == tag_button_action ) {
					terms_section_collapse( filter_section.children( "ul" ) );
				}

				filter_section.slideDown();
			} );
		} );
	} );

	/**
	 * Collapse a terms filter section
	 */
	function terms_section_collapse( section ) {
		section.parents( ".docs-filter-section" ).find( 'a.terms-hide' ).remove();
		var term_list_items = section.children( "li" ),
			term_list_id = section.attr( "id" ),
			hidden_term_count = 0;

		term_list_items.each( function( k, v ) {
			if ( k > 6 ) {
				$( v ).addClass( "hidden-tag" );
				hidden_term_count++;
			}
		} );

		// Add an ellipses item
		var st = '<span class="and-x-more">&hellip; <a href="#" class="terms-unhide terms-toggle-list-visibility">' + bp_docs.and_x_more + '</a></span>';
		st = st.replace( /%d/, hidden_term_count );
		section.append( '<li class="tags-ellipses">' + st + '</li>' );
		section.parent().prepend( '<a class="terms-unhide terms-toggle-list-visibility tags-spanning-button" href="#">show all terms</a>' );
	}

	/**
	 * Expand a terms filter section
	 */
	function terms_section_expand( section ) {
		section.parent().find( "a.terms-unhide" ).remove();
		section.find( ".tags-ellipses" ).remove();
		section.children( "li" ).removeClass( "hidden-tag" );
		section.parent().prepend( '<a class="terms-hide terms-toggle-list-visibility tags-spanning-button" href="#">show fewer terms</a>' );
	}

}(jQuery));