import { __ } from '@wordpress/i18n';
import React from 'react';
import PropTypes from 'prop-types';

import parseCSVFile from '../../services/data/parse-csv-file';

/**
 * A component used to upload a CSV file with hooks for data processing.
 */
export default class CSVUploader extends React.PureComponent {
  /**
   * A callback for the upload form submit action.
   * @param {Event} e - The form submit event.
   */
  static handleSubmit(e) {
    e.preventDefault();
  }

  /**
   * Re-binds class methods to use `this` properly.
   */
  constructor(props) {
    super(props);
    this.handleChange = this.handleChange.bind(this);

    // Set initial state for this component.
    this.state = {
      error: '',
      success: '',
    };
  }

  /**
   * A callback for the change event on the file input.
   * @param {Event} e - The input change event.
   */
  handleChange(e) {
    const {
      attributeName,
      callback,
      setAttributes,
    } = this.props;

    // Ensure there is a file selected.
    if (!e.target.files || !e.target.files[0]) {
      return;
    }

    // Parse the file and send the contents to the callback for transformation.
    parseCSVFile(e.target.files[0])
      .then((data) => (callback ? callback(data) : data))
      .then((data) => {
        if (Array.isArray(data) && data.length > 0) {
          this.setState({
            error: '',
            success: __(
              'Successfully read CSV data.',
              'zephr',
            ),
          });
          setAttributes({
            [attributeName]: JSON.stringify(data),
          });
        } else {
          this.setState({
            error: __(
              'Could not map CSV data. Please check the source file to ensure that it has the correct structure.', // eslint-disable-line max-len
              'zephr',
            ),
            success: '',
          });
        }
      })
      .catch((error) => {
        this.setState({
          error,
          success: '',
        });
      });
  }

  /**
   * Renders this component.
   * @returns {object} - JSX for the component.
   */
  render() {
    const {
      error = '',
      success = '',
    } = this.state;
    return (
      <div className="zephr-block-csv-uploader">
        <h2>{__('Upload CSV', 'zephr')}</h2>
        <form onSubmit={this.handleSubmit}>
          {error !== '' ? (
            <div style={{ color: '#c00' }}>
              <strong>
                {__(
                  'Error:',
                  'zephr',
                )}
              </strong>
              &nbsp;
              {error}
            </div>
          ) : null}
          {success !== '' ? (
            <div style={{ color: '#0c0' }}>
              <strong>
                {__(
                  'Success:',
                  'zephr',
                )}
              </strong>
              &nbsp;
              {success}
            </div>
          ) : null}
          <div>
            <label
              htmlFor="zephr-block-csv-uploader-file"
            >
              <p>
                {__(
                  'Select a file to load data.',
                  'zephr',
                )}
              </p>
              <input
                id="zephr-block-csv-uploader-file"
                onChange={this.handleChange}
                type="file"
              />
            </label>
          </div>
        </form>
      </div>
    );
  }
}

/**
 * Default props.
 * @type {object}
 */
CSVUploader.defaultProps = {
  callback: null,
};

/**
 * Set PropTypes for this component.
 * @type {object}
 */
CSVUploader.propTypes = {
  attributeName: PropTypes.string.isRequired,
  callback: PropTypes.func,
  setAttributes: PropTypes.func.isRequired,
};
