import { useContext, createContext } from 'react';

const OnboardingContext = createContext();

const useOnboardingContext = () => useContext(OnboardingContext);

export { useOnboardingContext, OnboardingContext };
