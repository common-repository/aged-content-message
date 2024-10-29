<?php
/**
 * Renders ouput in the front-end.
 */

add_filter( 'the_content', 'aged_content_message__the_content' );
add_action( 'wp_head', 'aged_content_message__print_css' );

/**
 * Conditionally add a message to content.
 *
 * @param  string $content Post content.
 * @return string          Post content, possibly prepended by message
 */
function aged_content_message__the_content( $content ) {

	// Not needed on other views than single (or filtered condition).
	if ( apply_filters( 'aged_content_message__the_content_condition', ! is_single() ) ) {
		return $content;
	}

	if ( ! aged_content_message__is_activated() ) {
		return $content;
	}

	// Calculate age in years as a float.
	$years_diff = ( time() - get_the_time( 'U' ) ) / YEAR_IN_SECONDS;
	$age        = apply_filters( 'aged_content_message__the_content_age', floor( $years_diff ) );

	$options = get_option( 'aged_content_message__settings' );

	// Return original content if no HTMl is set up.
	if ( ! isset( $options['html'] ) ) {
		return $content;
	}

	// Return original content if not too old.
	if ( $years_diff < absint( $options['min_age'] ) ) {
		return $content;
	}

	// Render message.
	$msg = apply_filters(
		'aged_content_message__the_content_message',
		aged_content_message__message_render( $age ),
		$age
	);

	return $msg . $content;
}

/**
 * Print default CSS styles to <head> section.
 * Can be done in a nicer way, but hey, an HTTP request for 20 lines of CSS?
 *
 * @return void
 */
function aged_content_message__print_css() {

	// Not needed on other views than single (or filtered condition).
	if ( apply_filters( 'aged_content_message__the_content_condition', ! is_single() ) ) {
		return;
	}

	$options = get_option( 'aged_content_message__settings' );

	// Empty value: user has removed CSS, go no further.
	if ( isset( $options['css'] ) && '' === trim( $options['css'] ) ) {
		return;
	}

	// Print CSS to <head> section.
	printf(
		'<style type="text/css" media="screen" id="aged-content-message-css">%s</style>',
		wp_kses_post( $options['css'] )
	);
}
