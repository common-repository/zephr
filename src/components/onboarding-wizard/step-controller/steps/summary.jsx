import React from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingContext } from '../../../../contexts/onboarding-context';

export const StepSummary = () => {
  const { apiKeys, selectedSite, syncUsers } = useOnboardingContext();
  const secretLast = apiKeys.apiSecret.substr(-12);
  const maskedSecret = `xxxxxxxx-xxxx-xxxx-xxxx-${secretLast}`;

  const setZephrOnboarded = async () => {
    await apiFetch({
      path: 'zephr/v1/zephr-options/',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ onboarded: '1' }),
    }).catch((err) => {
      console.log('Error from summary fetch: ', err); // eslint-disable-line no-console
    });
  };

  const handleFinish = async () => {
    await setZephrOnboarded();

    const adminUrl = '?page=zephr';
    window.location = `${location.pathname}${adminUrl}`; // eslint-disable-line no-restricted-globals
  };

  return (
    <div className="zephr-onboarding-wizard__step summary">
      <h2>{__('You\'ve successfully connected to Zephr!', 'zephr')}</h2>
      <div className="details">
        <p>
          <span>{__('API Key: ', 'zephr')}</span>
          {apiKeys.apiKey}
        </p>
        <p>
          <span>{__('Secret: ', 'zephr')}</span>
          {maskedSecret}
        </p>
        <p>
          <span>{__('Site: ', 'zephr')}</span>
          {selectedSite.title}
        </p>
        {syncUsers ? (
          <p>
            <span>{__('Users: ', 'zephr')}</span>
            {__('Users will be synced from WordPress to Zephr', 'zephr')}
          </p>
        ) : null}
      </div>
      <button type="button" onClick={handleFinish} className="nextbutton">{__('Finish', 'zephr')}</button>
    </div>
  );
};
