<?php
/**
 * Zephr General Settings
 *
 * phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
 *
 * @package Zephr
 */

?>
<div class="wrap zephr-settings">
	<h1><?php esc_html_e( 'Zephr', 'zephr' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'zephr' ); ?>
		<?php do_settings_sections( 'zephr' ); ?>
		<?php submit_button(); ?>
	</form>
</div>
