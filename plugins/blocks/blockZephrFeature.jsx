import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { Notice, PanelBody, PanelRow } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import FeatureSelector from '../../components/feature-selector';

addFilter(
  'blocks.registerBlockType',
  'zephr.addExtraAttributes',
  (settings) => ({
    ...settings,
    attributes: {
      ...settings.attributes,
      zephr_feature: {
        type: 'string',
        default: '',
      },
    },
  }),
);

addFilter(
  'editor.BlockEdit',
  'zephr.filterBlockEdit',
  (Edit) => {
    const FilterBlockEdit = (props) => {
      const {
        attributes,
        setAttributes,
      } = props;
      const {
        zephr_feature: zephrFeature,
      } = attributes;

      const onSelect = (value) => {
        setAttributes({ zephr_feature: value });
      };

      const [cssSelector, setCssSelector] = useState('');
      useEffect(() => {
        const updateCssSelector = async () => {
          setCssSelector('');
          const feature = await apiFetch(
            { path: `/zephr/v1/features/${zephrFeature}` },
          );
          const {
            featureVersion: {
              cssSelector: featureCssSelector = '',
            } = {},
          } = feature;
          setCssSelector(featureCssSelector);
        };
        updateCssSelector();
      }, [zephrFeature]);

      const isSupportedSelector = (selector) => {
        /* eslint-disable no-useless-escape */
        const patterns = [
          '^\#[^ ]*$', // #id    elements with attribute ID of “id”    div#wrap, #logo.
          '^\.[^ ]*$', // .class    elements with a class name of “class”    div.left, .result.
          '^\[[^ =]*\]$', // [attr]    elements with an attribute named “attr” (with any value)    a[href], [title].
          '^\[[^ ]*="?[^ ]*"?\]$', // [attr=val]    elements with an attribute named “attr”, and value equal to “val”    img[width=500], a[rel=nofollow].
        ];
        /* eslint-enable no-useless-escape */
        return patterns.some((pattern) => {
          const regex = new RegExp(pattern);
          return regex.test(selector);
        });
      };

      return (
        <>
          {/* eslint-disable-next-line react/jsx-props-no-spreading */}
          <Edit {...props} />

          <InspectorControls>
            <PanelBody
              title={__('Zephr Feature', 'zephr')}
            >
              <PanelRow>
                <FeatureSelector selected={zephrFeature} onSelect={onSelect} />
              </PanelRow>
              {cssSelector !== '' && isSupportedSelector(cssSelector) ? (
                <PanelRow>
                  <Notice status="info" isDismissible={false}>
                    {
                      sprintf(
                        __('CSS Selector: %s', 'zephr'),
                        cssSelector,
                      )
                    }
                  </Notice>
                </PanelRow>
              ) : null}
              {cssSelector !== '' && !isSupportedSelector(cssSelector) ? (
                <PanelRow>
                  <Notice status="error" isDismissible={false}>
                    {
                      sprintf(
                        __('CSS Selector: %s is not supported', 'zephr'),
                        cssSelector,
                      )
                    }
                  </Notice>
                </PanelRow>
              ) : null}
            </PanelBody>
          </InspectorControls>
        </>
      );
    };

    FilterBlockEdit.defaultProps = {
      attributes: {
        zephr_feature: '',
      },
    };

    FilterBlockEdit.propTypes = {
      attributes: PropTypes.shape({
        zephr_feature: PropTypes.string,
      }),
      setAttributes: PropTypes.func.isRequired,
    };

    return FilterBlockEdit;
  },
);
