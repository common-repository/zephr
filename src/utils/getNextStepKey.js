/**
 * Get the next step key.
 *
 * @param   {array}  steps        Array of step objects from context.
 * @param   {string}  currentStep Current step key.
 *
 * @return  {string}              Next step key.
 */
export const getNextStepKey = (steps, currentStep) => {
  const nextStepIndex = steps.findIndex((step) => step.key === currentStep) + 1;
  return steps[nextStepIndex].key ?? 'enter-keys'; // Reset to beginning if no key found.
};
