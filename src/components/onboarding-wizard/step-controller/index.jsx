import React from 'react';
import DisplayCurrentStep from '../display-step';
import { useOnboardingContext } from '../../../contexts/onboarding-context';

import './style.css';

const StepController = () => {
  const { steps, currentStep } = useOnboardingContext();

  // Grab current step object from state for heading.
  const displayStep = steps.filter((step) => step.key === currentStep);

  return (
    <div className="zephr-onboarding-wizard__step-container">
      <div className="zephr-onboarding-wizard__step-header">
        <h2>{displayStep[0].heading}</h2>
      </div>
      <div className="zephr-onboarding-wizard__step-content">
        <DisplayCurrentStep stepKey={currentStep} />
      </div>
    </div>
  );
};

export default StepController;
