import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __, sprintf } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Notice } from '@wordpress/components';

// Services.
import usePostMeta from '../../services/hooks/use-post-meta';

// Components.
import FeatureSelector from '../../components/feature-selector';

const PostFeature = () => {
  const [{
    zephr_feature: zephrFeature = '',
  }, setMeta] = usePostMeta();
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

  const onSelect = (value) => {
    setMeta('zephr_feature', value);
  };

  const CssSelectorAlert = () => {
    if (cssSelector === '') {
      return null;
    }

    if (cssSelector[0] !== '.') {
      return (
        <Notice status="error" isDismissible={false}>
          <p>
            {
              sprintf(
                __('Zephr Feature must use a Class CSS Selector. Current selector is "%s".', 'zephr'),
                cssSelector,
              )
            }
          </p>
          <p>
            {__('Update it by clicking the "Edit in Zephr Console" link above and change the CSS selector under "Developer Interface".', 'zephr')}
          </p>
        </Notice>
      );
    }
    return (
      <Notice status="success" isDismissible={false}>
        {
          sprintf(
            __('Zephr Feature CSS Selector is "%s"', 'zephr'),
            cssSelector,
          )
        }
      </Notice>
    );
  };

  return (
    <PluginDocumentSettingPanel
      icon="cloud"
      name="postfeature"
      title={__('Zephr Feature', 'zephr')}
    >
      <>
        <FeatureSelector
          onSelect={onSelect}
          selected={zephrFeature}
          useDataAttribute={false}
        />
        <CssSelectorAlert />
      </>
    </PluginDocumentSettingPanel>
  );
};

export default PostFeature;
