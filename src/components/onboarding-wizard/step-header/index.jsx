import React from 'react';
import classnames from 'classnames';
import { useOnboardingContext } from '../../../contexts/onboarding-context';

import './style.css';

const StepHeader = () => {
  const { steps, currentStep } = useOnboardingContext();

  return (
    <div className="zephr-onboarding-wizard__header">
      <div className="zephr-onboarding-wizard__step-markers">
        {steps.map((step, i) => {
          const {
            key,
            label,
          } = step;
          const stepsLength = steps.length;
          const isCurrentStep = key === currentStep;
          const stepClassName = classnames(
            'zephr-onboarding-wizard__step-marker',
            {
              'is-current-step': isCurrentStep,
            },
          );

          return (
            <>
              <div
                key={key}
                className={stepClassName}
              >
                <span className="zephr-onboarding-wizard__step-number">{ i + 1 }</span>
                <div className="zephr-onboarding-wizard__step-label">{label}</div>
              </div>
              {(stepsLength !== i + 1) ? (
                <span className="zephr-onboarding-wizard__separator" />
              ) : null}
            </>
          );
        })}
      </div>
    </div>
  );
};

export default StepHeader;
