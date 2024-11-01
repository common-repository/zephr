import React from 'react';
import { __ } from '@wordpress/i18n';
import StepController from './step-controller';
import StepHeader from './step-header';

import './style.css';

const OnboardingWizard = () => (
  <>
    <div className="zephr-onboarding-wizard__container">
      <StepHeader />
      <h1 className="zephr-onboarding-wizard__container-heading">{__('Connect to Zephr', 'zephr')}</h1>
      <StepController />
    </div>
  </>
);

export default OnboardingWizard;
