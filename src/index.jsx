import React from 'react';
import { render } from '@wordpress/element';
import App from './app';

const appRoot = document.getElementById('onboarding-root');

if (appRoot) {
  render(<App />, appRoot);
}
