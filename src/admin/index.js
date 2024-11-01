import apiFetch from '@wordpress/api-fetch';

const addDismissHandler = () => {
  document.querySelector('#zephr-user-migrate .notice-dismiss')?.addEventListener('click', () => {
    apiFetch({
      path: '/zephr/v1/sync-users/clear',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    });
  });
};

window.addEventListener('load', addDismissHandler);
