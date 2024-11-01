/* global zephrPlugin */
import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import AddNewFeature from '../addNewFeature';

const FeatureSelector = ({
  useDataAttribute = true,
  selected,
  onSelect,
}) => {
  const [options, setOptions] = useState([]);

  const updateOptionsFromGlobal = async () => (
    apiFetch(
      {
        method: 'GET',
        path: '/zephr/v1/features',
      },
    ).then((response) => {
      zephrPlugin.features = response;
      setOptions(response.map((item) => ({ value: item.id, label: item.label })));
    })
  );

  const updateAndSelect = (value) => {
    updateOptionsFromGlobal().then(() => {
      onSelect(value);
    });
  };

  useEffect(() => {
    updateOptionsFromGlobal();
  }, []);

  return (
    <>
      <div style={{ width: '100%', flexDirection: 'column' }}>
        <SelectControl
          label={__('Feature:', 'zephr')}
          value={selected}
          onChange={onSelect}
          options={[
            { value: null, label: __('Select a Feature', 'zephr') },
            ...options,
          ]}
        />
        {selected ? (
          <p>
            <a
              href={sprintf('https://console.zephr.com/products/features/html/%s', selected)}
              target="_blank"
              rel="noreferrer"
            >
              {__('Edit in Zephr Console', 'zephr')}
            </a>
          </p>
        ) : null}
        <AddNewFeature useDataAttribute={useDataAttribute} updateFunction={updateAndSelect} />
      </div>
    </>
  );
};

/**
 * Default props.
 * @type {object}
 */
FeatureSelector.defaultProps = {
  useDataAttribute: true,
  selected: '',
};

/**
 * Set PropTypes for this component.
 * @type {object}
 */
FeatureSelector.propTypes = {
  useDataAttribute: PropTypes.bool,
  selected: PropTypes.string,
  onSelect: PropTypes.func.isRequired,
};

export default FeatureSelector;
