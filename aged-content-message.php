<?php
/**
 * Plugin Name: Aged Content Message
 * Text Domain: aged-content-message
 * Domain Path: /languages
 * Description: Displays a message at the top of single posts published x years ago or earlier, informing about content that may be outdated.
 * Author:      Torsten Landsiedel
 * Author URI:  https://torstenlandsiedel.de/
 * Plugin URI:  https://wordpress.org/plugins/aged-content-message
 * License:     GPLv3
 * Version:     1.4.4
 */

/*
Copyright (C)  2014-2023 Caspar Hübinger
Copyright (C)  2023- Torsten Landsiedel

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin
 *
 * @return void Init of the plugin.
 */
function aged_content_message() {

	// Load textdomain.
	load_plugin_textdomain(
		'aged-content-message',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	$inc_dir = dirname( __FILE__ ) . '/inc';

	// Admin functionality.
	if ( is_admin() && current_user_can( 'manage_options' ) ) {

		// Load settings page.
		require_once $inc_dir . '/admin.php';

		// Adds a Settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			'aged_content_message__plugins_page_settings_link'
		);
	}

	// Front-end functionality.
	if ( ! is_admin() ) {
		require_once $inc_dir . '/frontend.php';
	}
}
add_action( 'plugins_loaded', 'aged_content_message' );

/**
 * Render aged content message.
 *
 * @param  integer $post_age Age of post in years, default is 1.
 * @return string Number of years
 */
function aged_content_message__message_render( $post_age = 1 ) {

	$options = get_option( 'aged_content_message__settings' );

	$html     = force_balance_tags( $options['html'] );
	$post_age = absint( $post_age );

	// Singular/plural form message.
	$output = sprintf(
		// Balance those HTML tags.
		wp_kses_post( $html ) . "\n",
		aged_content_message__sanitize_html_class_names( $options['class'] ),
		sanitize_post_field( 'post_title', $options['heading'], 0, 'display' ),
		sprintf(
			_n(
				sanitize_post_field( 'post_content', $options['body_singular'], 0, 'display' ),
				sanitize_post_field( 'post_content', $options['body_plural'], 0, 'display' ),
				$post_age,
				'aged-content-message'
			),
			$post_age
		)
	);
	return $output;
}

/**
 * Default settings initially stored in aged_content_message__settings option.
 *
 * @return array Default settings
 */
function aged_content_message__defaults() {

	$defaults = array();

	// Activate message.
	$defaults['activate'] = 0;

	// Minimum post age.
	$defaults['min_age'] = absint( apply_filters( 'aged_content_message__the_content_min_age', 1 ) );

	// Text.
	$defaults['heading']       = __( 'The times they are a-changin’.', 'aged-content-message' );
	$defaults['body_singular'] = __( 'This post seems to be older than %s year—a long time on the internet. It might be outdated.', 'aged-content-message' );
	$defaults['body_plural']   = __( 'This post seems to be older than %s years—a long time on the internet. It might be outdated.', 'aged-content-message' );

	// Class.
	$defaults['class'] = 'aged-content-message';

	// HTML.
	$defaults['html']  = '<div class="%1$s">' . "\n";
	$defaults['html'] .= '    <h5>%2$s</h5>' . "\n";
	$defaults['html'] .= '    <p>%3$s</p>' . "\n";
	$defaults['html'] .= '</div>';

	// Styles.
	$defaults['css']  = '.aged-content-message {' . "\n";
	$defaults['css'] .= '    background: #f7f7f7;' . "\n";
	$defaults['css'] .= '    border-left: 5px solid #f39c12;' . "\n";
	$defaults['css'] .= '    font-family: inherit;' . "\n";
	$defaults['css'] .= '    font-size: .875rem;' . "\n";
	$defaults['css'] .= '    line-height: 1.5;' . "\n";
	$defaults['css'] .= '    margin: 1.5rem 0;' . "\n";
	$defaults['css'] .= '    padding: 1.5rem;' . "\n";
	$defaults['css'] .= '}' . "\n";
	$defaults['css'] .= '.aged-content-message h5 {' . "\n";
	$defaults['css'] .= '    font-family: inherit;' . "\n";
	$defaults['css'] .= '    font-size: .8125rem;' . "\n";
	$defaults['css'] .= '    font-weight: bold;' . "\n";
	$defaults['css'] .= '    line-height: 2;' . "\n";
	$defaults['css'] .= '    margin: 0;' . "\n";
	$defaults['css'] .= '    padding: 0;' . "\n";
	$defaults['css'] .= '    text-transform: uppercase;' . "\n";
	$defaults['css'] .= '}' . "\n";
	$defaults['css'] .= '.aged-content-message p {' . "\n";
	$defaults['css'] .= '    margin: 0;' . "\n";
	$defaults['css'] .= '    padding: 0;' . "\n";
	$defaults['css'] .= '}';

	return $defaults;
}

/**
 * Conditional tag styled utility function to determine whether or not
 * displaying the message has been activated.
 *
 * @return boolean Setting is active, or not.
 */
function aged_content_message__is_activated() {

	$options = get_option( 'aged_content_message__settings' );

	return apply_filters(
		'aged_content_message__is_activated',
		(bool) isset( $options['activate'] ) && 1 === absint( $options['activate'] )
	);
}

/**
 * Sanitize a string divided by spaces as HTML class name(s).
 *
 * @param  string $class_names A string of 1 or more class names
 * @return string              Sanitized string
 */
function aged_content_message__sanitize_html_class_names( $class_names = '' ) {

	// This can be multiple class names devided by spaces.
	$classes_array = explode( ' ', $class_names );

	// Sanitize each class name from the string.
	foreach ( $classes_array as $k => $class_name ) {

		// Let’s not loose umlauts, but replace them first.
		$sanitized_as_title = sanitize_title( $class_name );

		// Now remove all the junk, but keep the former umlauts.
		$classes_array[ $k ] = sanitize_html_class( $sanitized_as_title );
	}

	// Rebuild the string for output.
	return implode( ' ', $classes_array );
}

/**
 * Do stuff when deactivating the plugin.
 *
 * @return void
 */
function aged_content_message__uninstall() {

	// Show admin notice again as a reminder when re-activating.
	delete_option( 'aged_content_message__status' );

	// Deactivate displaying of message in the frontend.
	$options = get_option( 'aged_content_message__settings' );
	unset( $options['activate'] );

	// Leave other settings untouched.
	update_option( 'aged_content_message__settings', $options );
}
register_deactivation_hook( __FILE__, 'aged_content_message__uninstall' );
