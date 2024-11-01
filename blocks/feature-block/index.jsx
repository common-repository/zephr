// Import WordPress block dependencies.
import { InnerBlocks } from '@wordpress/block-editor';
import React from 'react';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import attributes from './attributes.json';
import edit from './edit';

/* eslint-disable quotes */

registerBlockType(
  'zephr/feature-block',
  {
    attributes,
    category: 'widgets',
    description: __(
      'A block used to load a Zephr feature.',
      'zephr',
    ),
    edit,
    icon: 'layout',
    keywords: [
      __('zephr', 'zephr'),
      __('feature', 'zephr'),
      __('block', 'zephr'),
    ],
    save: () => <InnerBlocks.Content />,
    title: __('Zephr Feature Block', 'zephr'),
  },
);
