<?php
/**
 * Template part for block for Zephr Feature Block
 *
 * @global array  $attributes Block attributes passed to the render callback.
 * @global string $content    Block content from InnerBlocks passed to the render callback.
 *
 * @package Zephr
 */
?>

<div>
	<?php echo wp_kses_post( $content ); ?>
</div>
