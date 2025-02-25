import React, { useState } from 'react';
import { PropTypes } from 'prop-types';
import {
  Button,
  SelectControl,
  TextareaControl,
  TextControl,
} from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { cleanForSlug } from '@wordpress/url';

const AddNewFeature = ({ useDataAttribute = true, updateFunction }) => {
  const [showForm, setShowForm] = useState(false);
  const [title, setTitle] = useState('');
  const [desc, setDesc] = useState('');
  const [cssSelector, setCssSelector] = useState('');
  const [contentType, setContentType] = useState('TEXT');
  const [customSelector, setCustomSelector] = useState(false);

  const createFeature = async () => {
    const response = await apiFetch(
      {
        method: 'POST',
        path: '/zephr/v1/features',
        data: {
          title,
          type: 'HTML',
          desc,
          'css-selector': cssSelector,
          'content-type': contentType,
        },
      },
    );
    // TODO: error handling.
    const { createFeatureVersion: { slug } } = response;
    updateFunction(slug);

    setShowForm(false);
    setTitle('');
    setDesc('');
    setCssSelector('');
    setContentType('TEXT');
  };

  /*
   * Update the title and the autogenerated css selector, unless the user has
   * manually updated the css selector.
   *
   * @param string value The new value.
   */
  const handleTitleChange = (value) => {
    setTitle(value);
    if (!customSelector) {
      if (useDataAttribute) {
        setCssSelector(`[data-zephr-block-feature=${cleanForSlug(value)}]`);
      } else {
        setCssSelector(`.zephr-feature-${cleanForSlug(value)}`);
      }
    }
  };

  /*
   * Update the css selector and mark that this is a manually updated selector.
   *
   * @param string value The new value.
   */
  const handleSelectorChange = (value) => {
    setCssSelector(value);
    setCustomSelector(true);
  };

  return (
    <div>
      {showForm ? (
        <>
          <div>
            <TextControl
              label={_x('Title', 'The name of the Zephr feature', 'zephr')}
              value={title}
              onChange={handleTitleChange}
            />
          </div>
          <div>
            <TextareaControl
              label={_x('Description (optional)', 'The description of the Zephr feature', 'zephr')}
              value={desc}
              onChange={setDesc}
            />
          </div>
          <div>
            <TextareaControl
              label={_x('CSS Selector', 'The CSS Selector used to identify the Zephr feature', 'zephr')}
              value={cssSelector}
              onChange={handleSelectorChange}
            />
          </div>
          <div>
            <SelectControl
              label={_x('Content Type', 'The Content Type of the Zephr feature - one of TEXT, IMAGE, VIDEO, ADVERTISING', 'zephr')}
              value={contentType}
              onChange={setContentType}
              options={[
                { value: 'TEXT', label: __('Text', 'zephr') },
                { value: 'IMAGE', label: __('Image', 'zephr') },
                { value: 'VIDEO', label: __('Video', 'zephr') },
                { value: 'ADVERTISING', label: __('Advertising', 'zephr') },
              ]}
            />
          </div>
          <Button
            isPrimary
            onClick={createFeature}
            disabled={!title || !cssSelector || !contentType}
            style={{ marginRight: '7px' }}
          >
            {__('Create Feature', 'zephr')}
          </Button>
          <Button isSecondary onClick={() => setShowForm(false)}>{__('Cancel')}</Button>
        </>
      ) : (
        <Button isSecondary onClick={() => setShowForm(true)}>{__('Add New Feature')}</Button>
      )}
    </div>
  );
};

AddNewFeature.defaultProps = {
  useDataAttribute: true,
};

AddNewFeature.propTypes = {
  useDataAttribute: PropTypes.bool,
  updateFunction: PropTypes.func.isRequired,
};

export default AddNewFeature;
