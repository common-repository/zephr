import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import OnboardingWizard from './components/onboarding-wizard';
import { OnboardingContext } from './contexts/onboarding-context';

import './style.css';

const App = () => {
  const [currentStep, setCurrentStep] = useState('enter-keys');
  const [apiKeys, setApiKeys] = useState({
    apiKey: null,
    apiSecret: null,
    tenantId: null,
  });
  const [syncUsers, setSyncUsers] = useState(false);
  const [selectedSite, setSelectedSite] = useState({});
  const [steps, setSteps] = useState([
    {
      key: 'enter-keys',
      heading: __('Enter Your API Keys', 'zephr'),
      label: __('Keys', 'zephr'),
    },
    {
      key: 'connect-site',
      heading: __('Choose a Site', 'zephr'),
      label: __('Site', 'zephr'),
    },
    {
      key: 'sync-users',
      heading: __('Sync Users', 'zephr'),
      label: __('Users', 'zephr'),
    },
    {
      key: 'summary',
      heading: __('All Set!', 'zephr'),
      label: __('Finish', 'zephr'),
    },
  ]);

  return (
    <OnboardingContext.Provider value={{
      currentStep,
      setCurrentStep,
      apiKeys,
      setApiKeys,
      syncUsers,
      setSyncUsers,
      selectedSite,
      setSelectedSite,
      steps,
      setSteps,
    }}
    >
      <OnboardingWizard />
    </OnboardingContext.Provider>
  );
};

export default App;
