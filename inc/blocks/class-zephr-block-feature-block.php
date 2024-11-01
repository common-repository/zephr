<?php
/**
 * Block definition for Zephr Feature Block
 *
 * @package Zephr
 */

/**
 * Class for the feature-block block.
 */
class Zephr_Block_Feature_Block extends Zephr_Block {
	
	/**
	 * Name of the custom block.
	 *
	 * @var string
	 */
	public $name = 'feature-block';

	/**
	 * Namespace of the custom block.
	 *
	 * @var string
	 */
	public $namespace = 'zephr';
}
$zephr_block_feature_block = new Zephr_Block_Feature_Block();
