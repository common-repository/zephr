import React, { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { getNextStepKey } from '../../../../utils';
import { useOnboardingContext } from '../../../../contexts/onboarding-context';

export const StepEnterKeys = () => {
  const {
    steps,
    currentStep,
    setCurrentStep,
    apiKeys,
    setApiKeys,
  } = useOnboardingContext();

  const [error, setError] = useState(null);

  // Get the next step after this one.
  const nextStepKey = getNextStepKey(steps, currentStep);

  /**
   * Post the keys to the site options endpoint.
   *
   * @return  {bool|string}  Endpoint callback response.
   */
  const sendKeysToValidation = async () => {
    const response = await apiFetch({
      path: 'zephr/v1/zephr-keys/',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        tenant_id: apiKeys.tenantId,
        key: apiKeys.apiKey,
        secret: apiKeys.apiSecret,
      }),
    });

    return response;
  };

  /**
   * Control the form inputs.
   *
   * @param   {obj}  event  Input change event.
   * @return  {void}
   */
  const handleInputChange = (event) => {
    const {
      target: {
        name,
        value,
      },
    } = event;

    setApiKeys({
      ...apiKeys,
      [name]: value,
    });
  };

  /**
   * Submit the form.
   *
   * @param   {obj}      event  Form submit event.
   * @return  {void|obj}
   */
  const handleSubmit = async (event) => {
    event.preventDefault();

    const response = await sendKeysToValidation();

    if (response.validated === true) {
      setCurrentStep(nextStepKey);
    } else {
      setError({
        message: __('There was a problem validating your keys. Please try again!', 'zephr'),
      });
    }
  };

  return (
    <div className="zephr-onboarding-wizard__step enter-keys">
      <form
        onSubmit={handleSubmit}
      >
        {error ? (
          <div className="form-error">{error.message}</div>
        ) : null}
        <label htmlFor="apiKey">{__('Customer/Tenant ID', 'zephr')}</label>
        <input
          type="text"
          name="tenantId"
          value={apiKeys.tenantId ?? ''}
          onChange={handleInputChange}
        />
        <label htmlFor="apiKey">{__('Access Key', 'zephr')}</label>
        <input
          type="text"
          name="apiKey"
          value={apiKeys.apiKey ?? ''}
          onChange={handleInputChange}
        />
        <label htmlFor="apiSecret">{__('Secret Key', 'zephr')}</label>
        <input
          name="apiSecret"
          type="password"
          value={apiKeys.apiSecret ?? ''}
          onChange={handleInputChange}
        />
        <button type="submit" className="nextbutton">
          {__('Next', 'zephr')}
        </button>
      </form>
    </div>
  );
};
