import React from 'react';

/**
 * WordPress dependencies
 */
import {
  InnerBlocks,
} from '@wordpress/block-editor';

import './style.scss';

const Edit = () => (
  <div className="zephr-block-preview">
    <InnerBlocks />
  </div>
);

export default Edit;
