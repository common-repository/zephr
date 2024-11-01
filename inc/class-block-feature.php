<?php
/**
 * Class File for adding features to a Gutenberg block.
 *
 * @package Zephr
 */

namespace Zephr;

use Exception;

/**
 * Adds filters to support adding Zephr features to Gutenberg blocks.
 */
class Block_Feature {
	use Instance;

	/**
	 * Sets everything up.
	 */
	public function __construct() {
		add_filter( 'render_block', [ $this, 'add_css_selector_to_block' ], 100, 2 );
		add_filter( 'post_class', [ $this, 'add_class_to_post' ] );
	}

	/**
	 * Adds a css selector to the block.
	 *
	 * @param string $block_content The existing block content.
	 * @param array  $parsed_block  The parsed block values.
	 * @return string
	 */
	public function add_css_selector_to_block( $block_content, $parsed_block ) {
		$zephr_feature = $parsed_block['attrs']['zephr_feature'] ?? '';
		if ( empty( $zephr_feature ) ) {
			return $block_content;
		}

		try {
			$element = new \SimpleXMLElement( $block_content );
		} catch ( Exception $e ) {
			return $block_content;
		}

		$attrs_array     = (array) $element->attributes();
		$attribs         = $attrs_array['@attributes'] ?? [];
		$new_attribs     = $this->get_attributes_from_feature( $zephr_feature ) ?? [];
		$merged_array    = array_merge_recursive( $attribs, $new_attribs );
		$flattened_array = array_map(
			function( $item ) {
				if ( ! is_array( $item ) ) {
					return $item;
				}
				return implode( ' ', $item );
			},
			$merged_array
		);
		$this->update_attributes( $element, $flattened_array );
		return $element->asXML();
	}

	/**
	 * Parses the css selector and returns the required attributes.
	 *
	 * @see https://support.zephr.com/documentation/products/features/creating-and-managing-zephr-features/html-features
	 *
	 * @param string $zephr_feature The name of the zephr feature.
	 * @return array
	 */
	public function get_attributes_from_feature( $zephr_feature ) {
		$feature = Rest_API::instance()->get_feature( $zephr_feature );
		if ( empty( $feature ) ) {
			return [];
		}
		$css_selector = $feature['featureVersion']['cssSelector'];

		if ( empty( $css_selector ) ) {
			return [];
		}

		/**
		 * The following Selectors are supported by Zephr:
		 *
		 * *    any element    *.
		 * <tag>    elements with the given tag name    div.
		 * *|E    elements of type E in any namespace ns    *|name finds <fb:name> elements.
		 * ns|E    elements of type E in the namespace ns    fb|name finds <fb:name> elements.
		 * #id    elements with attribute ID of “id”    div#wrap, #logo.
		 * .class    elements with a class name of “class”    div.left, .result.
		 * [attr]    elements with an attribute named “attr” (with any value)    a[href], [title].
		 * [^attrPrefix]    elements with an attribute name starting with “attrPrefix”. Use to find elements with HTML5 datasets    [^data-], div[^data-].
		 * [attr=val]    elements with an attribute named “attr”, and value equal to “val”    img[width=500], a[rel=nofollow].
		 * [attr=”val”]    elements with an attribute named “attr”, and value equal to “val”    span[hello=”Cleveland”][goodbye=”Columbus”], a[rel=”nofollow”].
		 * [attr^=valPrefix]    elements with an attribute named “attr”, and value starting with “valPrefix”    a[href^=http:].
		 * [attr$=valSuffix]    elements with an attribute named “attr”, and value ending with “valSuffix”    img[src$=.png].
		 * [attr*=valContaining]    elements with an attribute named “attr”, and value containing “valContaining”    a[href*=/search/].
		 * [attr~=regex]    elements with an attribute named “attr”, and value matching the regular expression    img[src~=(?i)\\.(png|jpe?g)].
		 * The above may be combined in any order    div.header[title].
		*/

		// #id    elements with attribute ID of “id”    div#wrap, #logo.
		if ( preg_match( '/^\#([^ ]*)$/', $css_selector, $matches ) ) {
			return [
				'id' => $matches[1],
			];
		}

		// .class    elements with a class name of “class”    div.left, .result.
		if ( preg_match( '/^\.([^ ]*)$/', $css_selector, $matches ) ) {
			return [
				'class' => $matches[1],
			];
		}

		// [attr]    elements with an attribute named “attr” (with any value)    a[href], [title].
		if ( preg_match( '/^\[([^ =]*)\]$/', $css_selector, $matches ) ) {
			return [
				$matches[1] => null,
			];
		}

		// [attr=val]    elements with an attribute named “attr”, and value equal to “val”    img[width=500], a[rel=nofollow].
		// [attr=”val”]    elements with an attribute named “attr”, and value equal to “val”    span[hello=”Cleveland”][goodbye=”Columbus”], a[rel=”nofollow”].
		if ( preg_match( '/^\[([^ ]*)="?([^ ]*)"?\]$/', $css_selector, $matches ) ) {
			return [
				$matches[1] => $matches[2],
			];
		}
	}

	/**
	 * Updates the attributes of a SimpleXMLElement.
	 *
	 * @param \SimpleXMLElement $node           The SimpleXMLElement object.
	 * @param array             $new_attributes The array of new attributes.
	 */
	public function update_attributes( \SimpleXMLElement &$node, array $new_attributes ) {
		$attributes = $node->attributes();
		foreach ( $new_attributes as $attribute_name => $attribute_value ) {
			if ( isset( $attributes->$attribute_name ) ) {
				$attributes->$attribute_name = $attribute_value;
			} else {
				$node->addAttribute( $attribute_name, $attribute_value );
			}
		}
	}

	/**
	 * Adds class from css-selector of selected Zephr Feature to the post's class.
	 *
	 * @param array $classes The existing array of classes.
	 * @return array
	 */
	public function add_class_to_post( $classes ) {
		if ( ! is_singular() ) {
			return $classes;
		}

		if ( ! is_array( $classes ) ) {
			$classes = [ $classes ];
		}

		$zephr_feature = get_post_meta( get_the_ID(), 'zephr_feature', true );
		if ( ! empty( $zephr_feature ) ) {
			$new_attribs = $this->get_attributes_from_feature( $zephr_feature ) ?? [];
			if ( ! empty( $new_attribs['class'] ) ) {
				$classes[] = $new_attribs['class'];
			}
		}
		return $classes;
	}
}
