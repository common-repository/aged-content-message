<?php
/**
 * Adds a settings page to Settings menu.
 */

add_action( 'admin_notices', 'aged_content_message__admin_print_avtivation_notice' );
add_action( 'admin_menu', 'aged_content_message__add_admin_menu' );
add_action( 'admin_init', 'aged_content_message__settings_init' );

/**
 * URL for plugin settings page.
 *
 * @return string Settings page URL
 */
function aged_content_message__admin_get_settings_page_url() {

	return add_query_arg( array( 'page' => 'aged-content-message' ), admin_url( 'options-general.php' ) );
}

/**
 * Settings link on the plugin page.
 *
 * @param array $plugin_links Plugin links, typically Activate|Deactivate and Edit.
 */
function aged_content_message__plugins_page_settings_link( $plugin_links ) {

	// Thou shalt not edit plugins in your back-end.
	if ( isset( $plugin_links['edit'] ) ) {
		unset( $plugin_links['edit'] );
	}

	// Thou shalt, however, edit your settings quickly.
	$plugin_links['aged-content-message-settings'] = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( aged_content_message__admin_get_settings_page_url() ),
		__( 'Settings', 'aged-content-message' )
	);

	return $plugin_links;
}

/**
 * The settings page.
 * I thought I was never going to do this.
 *
 * @return void
 */
function aged_content_message__add_admin_menu() {

	add_options_page(
		__( 'Aged Content Message', 'aged-content-message' ),
		__( 'Aged Content', 'aged-content-message' ),
		'manage_options',
		'aged-content-message',
		'aged_content_message__settings_page'
	);
}

/**
 * Adding all the sections and fields.
 *
 * @return void
 */
function aged_content_message__settings_init() {

	$defaults = aged_content_message__defaults();
	$options  = get_option( 'aged_content_message__settings' );

	// First run: fill option.
	if ( empty( $options ) ) {
		update_option( 'aged_content_message__settings', $defaults );
	}

	// Later runs: check for and merge new defaults.
	$diff = array_diff_key( (array) $defaults, (array) $options );

	if ( ! empty( $diff ) ) {
		$options = wp_parse_args( $options, $defaults );
		update_option( 'aged_content_message__settings', $options );
	}

	register_setting( 'aged_content_message', 'aged_content_message__settings' );

	add_settings_section(
		'aged_content_message_preview',
		__( 'Preview', 'aged-content-message' ),
		'aged_content_message__settings_preview',
		'aged_content_message'
	);

	add_settings_field(
		'activate',
		__( 'Activate Message', 'aged-content-message' ),
		'aged_content_message__activate_render',
		'aged_content_message',
		'aged_content_message_preview',
		array( 'label_for' => 'aged_content_message__settings[activate]' )
	);

	add_settings_section(
		'aged_content_message_settings',
		__( 'Settings', 'aged-content-message' ),
		'aged_content_message__settings_fields',
		'aged_content_message'
	);

	add_settings_field(
		'min_age',
		__( 'Minimal Post Age', 'aged-content-message' ),
		'aged_content_message__min_age_render',
		'aged_content_message',
		'aged_content_message_settings'
	);

	add_settings_field(
		'heading',
		__( 'Message Heading', 'aged-content-message' ),
		'aged_content_message__heading_render',
		'aged_content_message',
		'aged_content_message_settings'
	);

	add_settings_field(
		'body_singular',
		__( 'Message Body (Singular)', 'aged-content-message' ),
		'aged_content_message__body_singular_render',
		'aged_content_message',
		'aged_content_message_settings'
	);

	add_settings_field(
		'body_plural',
		__( 'Message Body (Plural)', 'aged-content-message' ),
		'aged_content_message__body_plural_render',
		'aged_content_message',
		'aged_content_message_settings'
	);

	add_settings_field(
		'class',
		__( 'Message Class Attribute', 'aged-content-message' ),
		'aged_content_message__class_render',
		'aged_content_message',
		'aged_content_message_settings'
	);

	add_settings_field(
		'html',
		__( 'Message HTML', 'aged-content-message' ),
		'aged_content_message__html_render',
		'aged_content_message',
		'aged_content_message_settings'
	);

	add_settings_field(
		'css',
		__( 'Message CSS', 'aged-content-message' ),
		'aged_content_message__css_render',
		'aged_content_message',
		'aged_content_message_settings'
	);
}

/**
 * Activate Message setting.
 *
 * @return void
 */
function aged_content_message__activate_render() {

	$options     = get_option( 'aged_content_message__settings' );
	$value       = isset( $options['activate'] ) ? absint( $options['activate'] ) : 0;
	$description = __( '<strong>Ready?</strong> Activate the message on your website now.', 'aged-content-message' );

	if ( ! empty( $value ) ) {
		$description = __( '<strong>The message is being displayed on your website right now.</strong> Uncheck to deactivate and re-configure.', 'aged-content-message' );
	}
	?>
	<label for="aged_content_message__settings[activate]">
		<input type="checkbox" id="aged_content_message__settings[activate]" name="aged_content_message__settings[activate]" <?php checked( esc_attr( (string) $value ), 1 ); ?> value="1">
		<?php echo $description; ?>
	</label>
	<?php
}

/**
 * Minimal Post Age setting.
 *
 * @return void
 */
function aged_content_message__min_age_render() {

	$options = get_option( 'aged_content_message__settings' );
	$value   = absint( $options['min_age'] );

	$input = sprintf(
		'<input type="number" min="1" id="aged_content_message__settings[min_age]" name="aged_content_message__settings[min_age]" value="%s" class="small-text">',
		esc_attr( (string) $value )
	);
	?>
	<label for="aged_content_message__settings[min_age]"><?php printf( __( 'Display message for posts older than %s year(s).', 'aged-content-message' ), $input ); ?></label>
	<?php
}

/**
 * Message Heading setting.
 *
 * @return void
 */
function aged_content_message__heading_render() {

	$options = get_option( 'aged_content_message__settings' );
	$value   = sanitize_post_field( 'post_title', $options['heading'], 0, 'db' );
	?>
	<input type="text" id="aged_content_message__settings[heading]" name="aged_content_message__settings[heading]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
	<?php
}

/**
 * Message Body (Singular) setting.
 *
 * @return void
 */
function aged_content_message__body_singular_render() {

	$options = get_option( 'aged_content_message__settings' );
	$value   = sanitize_post_field( 'post_content', $options['body_singular'], 0, 'db' );
	?>
	<textarea cols="46" rows="6" id="aged_content_message__settings[body_singular]" name="aged_content_message__settings[body_singular]" class="regular-text"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( '<code>%s</code> = post age in years (rounded)', 'aged-content-message' ); ?></p>
	<?php
}

/**
 * Message Body (Plural) setting.
 *
 * @return void
 */
function aged_content_message__body_plural_render() {

	$options = get_option( 'aged_content_message__settings' );
	$value   = sanitize_post_field( 'post_content', $options['body_plural'], 0, 'db' );
	?>
	<textarea cols="46" rows="6" id="aged_content_message__settings[body_plural]" name="aged_content_message__settings[body_plural]" class="regular-text"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( '<code>%s</code> = post age in years (rounded)', 'aged-content-message' ); ?></p>
	<?php
}

/**
 * Message Class setting.
 *
 * @return void
 */
function aged_content_message__class_render() {

	$options = get_option( 'aged_content_message__settings' );
	$value   = aged_content_message__sanitize_html_class_names( $options['class'] );
	?>
	<input type="text" id="aged_content_message__settings[class]" name="aged_content_message__settings[class]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
	<p class="description"><?php esc_html_e( 'Multiple class names allowed. If you change this, remember to update your CSS settings below.', 'aged-content-message' ); ?></p>
	<?php
}

/**
 * Message HTML setting.
 *
 * @return void
 */
function aged_content_message__html_render() {

	$options = (array) get_option( 'aged_content_message__settings' );
	$html    = force_balance_tags( $options['html'] );
	$value   = sanitize_post_field( 'post_content', $html, 0, 'db' );
	?>
	<textarea cols="40" rows="6" id="aged_content_message__settings[html]" name="aged_content_message__settings[html]" class="regular-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( '<code>%1$s</code> = message heading; <code>%2$s</code> = message body', 'aged-content-message' ); ?></p>
	<?php
}

/**
 * Message CSS setting.
 *
 * @return void
 */
function aged_content_message__css_render() {

	$options = (array) get_option( 'aged_content_message__settings' );
	$value   = sanitize_post_field( 'post_content', $options['css'], 0, 'db' );
	?>
	<textarea cols="40" rows="6" name="aged_content_message__settings[css]" class="regular-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php esc_html_e( 'Leave blank in order to not apply any styling, e.g. when using an external stylesheet.', 'aged-content-message' ); ?></p>
	<?php
}

/**
 * Settings section.
 *
 * @return void
 */
function aged_content_message__settings_fields() {

	?>
	<p><?php esc_html_e( 'Configure your settings, then review the message above and activate it for your website.', 'aged-content-message' ); ?></p>
	<?php
}

/**
 * Renders settings page.
 *
 * @return void
 */
function aged_content_message__settings_page() {

	?>
	<form action='options.php' method='post'>

		<h1><?php esc_html_e( 'Aged Content Message', 'aged-content-message' ); ?></h1>

		<?php
		settings_fields( 'aged_content_message' );
		do_settings_sections( 'aged_content_message' );
		submit_button();
		?>

	</form>
	<?php
}

/**
 * Print admin notice after plugin activation.
 *
 * @return void
 */
function aged_content_message__admin_print_avtivation_notice() {

	$status = get_option( 'aged_content_message__status' );

	// Been there, done that?
	if ( 'dismissed' === $status ) {
		return;
	}

	$message = sprintf(
		__( '<strong>Aged Content Message</strong> has been activated. Edit your <a href="%s">settings</a> in order to see the actual notice displayed on your site.', 'aged-content-message' ),
		esc_url( aged_content_message__admin_get_settings_page_url() )
	);

	// Print notice.
	printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', $message );

	// Prevent annoyance.
	update_option( 'aged_content_message__status', 'dismissed' );
}

/**
 * Preview section.
 *
 * @return void
 */
function aged_content_message__settings_preview() {

	$options = get_option( 'aged_content_message__settings' );
	$age     = absint( $options['min_age'] );
	$css     = wp_kses_post( $options['css'] );
	?>
	<p>
		<?php esc_html_e( 'This is a preview of how your notice looks like with your current settings.', 'aged-content-message' ); ?><br>
		<?php esc_html_e( 'Note: Styling may vary dependent on theme styles inherited on your website.', 'aged-content-message' ); ?>
	</p>
	<div class="aged-content-message-preview">
		<style type="text/css" media="screen" scoped>
			.aged-content-message-preview {
				background: #212121;
				border: 10px solid #212121;
				-webkit-box-sizing: border-box;
				   -moz-box-sizing: border-box;
				    -ms-box-sizing: border-box;
				     -o-box-sizing: border-box;
				        box-sizing: border-box;
				max-width: 36rem;
				padding: 0;
				width: 95%;
			}

			<?php echo $css; ?>

		</style>

		<?php echo aged_content_message__message_render( $age ); ?>

	</div>
	<?php
}
