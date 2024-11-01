import React, { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { getNextStepKey } from '../../../../utils';
import { useOnboardingContext } from '../../../../contexts/onboarding-context';

export const StepSyncUsers = () => {
  const {
    steps,
    currentStep,
    setCurrentStep,
    syncUsers,
    setSyncUsers,
  } = useOnboardingContext();

  const [error, setError] = useState(null);

  // Get the next step after this one.
  const nextStepKey = getNextStepKey(steps, currentStep);

  /**
   * Post the keys to the site options endpoint.
   *
   * @return  {bool|string}  Endpoint callback response.
   */
  const scheduleUserSync = async () => {
    const response = await apiFetch({
      path: 'zephr/v1/sync-users/',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    return response;
  };

  /**
   * Submit the form.
   *
   * @param   {obj}      event  Form submit event.
   * @return  {void|obj}
   */
  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!syncUsers) {
      setCurrentStep(nextStepKey);
      return;
    }
    const response = await scheduleUserSync();

    if (response.scheduled === true) {
      setCurrentStep(nextStepKey);
    } else {
      setError({
        message: __('There was a problem scheduling the user sync. Please try again!', 'zephr'),
      });
    }
  };

  return (
    <div className="zephr-onboarding-wizard__step sync-users">
      <form
        onSubmit={handleSubmit}
      >
        {error ? (
          <div className="form-error">{error.message}</div>
        ) : null}
        <label htmlFor="syncUsers">
          <input
            type="checkbox"
            id="syncUsers"
            value
            checked={syncUsers}
            onChange={() => setSyncUsers(!syncUsers)}
          />
          {__('Sync WordPress Users to Zephr', 'zephr')}
        </label>
        <button type="submit" className="nextbutton">
          {__('Next', 'zephr')}
        </button>
      </form>
    </div>
  );
};
