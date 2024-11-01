import React from 'react';
import PropTypes from 'prop-types';
import {
  StepEnterKeys,
  StepConnectSite,
  StepSyncUsers,
  StepSummary,
} from '../step-controller/steps';

const DisplayCurrentStep = ({ stepKey }) => {
  switch (stepKey) {
    case 'enter-keys':
      return <StepEnterKeys />;

    case 'connect-site':
      return <StepConnectSite />;

    case 'sync-users':
      return <StepSyncUsers />;

    case 'summary':
      return <StepSummary />;

    default:
      return <StepEnterKeys />;
  }
};

DisplayCurrentStep.propTypes = {
  stepKey: PropTypes.string.isRequired,
};

export default DisplayCurrentStep;
