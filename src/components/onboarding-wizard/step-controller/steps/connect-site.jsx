import React, { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import { getNextStepKey } from '../../../../utils';
import { useOnboardingContext } from '../../../../contexts/onboarding-context';

export const StepConnectSite = () => {
  const {
    currentStep,
    steps,
    setCurrentStep,
    selectedSite,
    setSelectedSite,
  } = useOnboardingContext();
  const [sites, setSites] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showAddForm, setShowAddForm] = useState(false);
  const [newSiteSettings, setNewSiteSettings] = useState({ title: '', prefix: '' });
  const [selectedSiteSlug, setSelectedSiteSlug] = useState('');

  const getZephrSites = async () => {
    const sitesData = await apiFetch({
      path: 'zephr/v1/sites/',
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    return sitesData;
  };

  /**
   * Filter through the sites data for the LIVE cdn domain.
   *
   * @param   {array}  zephrSites  Site domains data from graphql.
   *
   * @return  {string}             URL from the live, preferred domain.
   */
  const getPreferredLiveUrl = (zephrSites) => {
    const liveSite = zephrSites.map((site) => ({ ...site, domains: site.domains.filter((domain) => domain.environment === 'LIVE') }
    ));

    const [
      {
        title,
        domains: [
          {
            url,
          },
        ],
      },
    ] = liveSite;

    return { title, url };
  };

  useEffect(() => {
    // Fetch sites from graphql endpoint.
    const fetchSitesData = async () => {
      const zephrSites = await getZephrSites();
      setSites(zephrSites);

      if (zephrSites.length === 1) {
        setSelectedSiteSlug(zephrSites[0].slug);
      }

      if (zephrSites.length === 0) {
        setShowAddForm(true);
      }

      setLoading(false);
    };
    fetchSitesData();
  }, [setSelectedSiteSlug]);

  useEffect(() => {
    if (!selectedSiteSlug) {
      setSelectedSite({});
      return;
    }
    const selectedSiteObj = sites.find((site) => site.slug === selectedSiteSlug);
    setSelectedSite(getPreferredLiveUrl([selectedSiteObj]));
  }, [selectedSiteSlug, setSelectedSite, sites]);

  // Get the next step after this one.
  const nextStepKey = getNextStepKey(steps, currentStep);

  /**
   * Post the site to the sites endpoint.
   *
   * @return  {bool|string}  Endpoint callback response.
   */
  const saveSiteOption = async () => {
    const saved = await apiFetch({
      path: 'zephr/v1/zephr-domain/',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ zephr_domain: selectedSite.url }),
    })
      .then((res) => res)
      .catch((err) => {
        console.log('Error from save domain fetch: ', err); // eslint-disable-line no-console
        return false;
      });

    return saved;
  };

  const createSite = async () => {
    const { title, prefix } = newSiteSettings;
    if (!title || !prefix) {
      return false;
    }
    setError({});
    const created = await apiFetch({
      path: 'zephr/v1/sites/',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(newSiteSettings),
    })
      .then((res) => {
        const { createSite: createdSite } = res;
        if (!createdSite) {
          setError({
            message: __('An error occurred creating the site', 'zephr'),
          });
          return false;
        }
        setSites([...sites, createdSite]);
        setSelectedSiteSlug(createdSite.slug);
        setShowAddForm(false);
        setNewSiteSettings({});
        return true;
      })
      .catch((err) => {
        console.log('Error from create site: ', err); // eslint-disable-line no-console
        return false;
      });

    return created;
  };

  /**
   * Handle the select change.
   *
   * @param   {string}      slug  Selected site slug.
   * @return  {void}
   */
  const handleSelect = (slug) => {
    setSelectedSiteSlug(slug);
  };

  /**
   * Cleans up the slug to use only url friendly characters.
   *
   * @param {string} slug The existing slug.
   * @returns {string}
   */
  const cleanPrefix = (slug) => {
    const lastChar = slug.slice(-1);
    const endsWithSpaceOrHyphen = lastChar === ' ' || lastChar === '-';

    return endsWithSpaceOrHyphen ? cleanForSlug(slug) + '-' : cleanForSlug(slug); // eslint-disable-line prefer-template
  };

  /**
   * Control the form inputs.
   *
   * @param   {obj}  event  Input change event.
   * @return  {void}
   */
  const handleInputChange = (event) => {
    const {
      target: {
        name,
        value,
      },
    } = event;

    const slugValue = (name === 'prefix') ? cleanPrefix(value) : value;

    setNewSiteSettings({
      ...newSiteSettings,
      [name]: slugValue,
    });
  };

  /**
   * Handle the form submit.
   *
   * @param   {obj}      event  Form submit event.
   * @return  {void|obj}
   */
  const handleSubmit = async (event) => {
    event.preventDefault();

    const response = await saveSiteOption();

    if (response.saved === true) {
      setCurrentStep(nextStepKey);
    } else {
      setError({
        message: __('There was a problem connecting to the site. Please try again!', 'zephr'),
      });
    }
  };

  const { title, prefix } = newSiteSettings;

  return (
    <div className="zephr-onboarding-wizard__step connect-site">
      { !loading ? (
        <form
          onSubmit={handleSubmit}
        >
          {error ? (
            <div className="form-error">{error.message}</div>
          ) : null}
          <h3>{__('Select a site to connect to:', 'zephr')}</h3>
          <label htmlFor="zephr_site">{__('Select a Site', 'zephr')}</label>
          <select
            name="zephr_site"
            onChange={(event) => handleSelect(event.target.value)}
            value={selectedSiteSlug}
          >
            <option key="select" value="">{__('Select Site or add new', 'zephr')}</option>
            {sites.length > 0 ? (
              sites.map((site) => (
                <option key={site.title} value={site.slug}>{site.title}</option>
              ))) : null}
          </select>
          {showAddForm ? (
            <div>
              <h3>{__('Add New Site:', 'zephr')}</h3>
              <label htmlFor="newSiteTitle">{__('Site Title', 'zephr')}</label>
              <input
                id="newSiteTitle"
                onChange={handleInputChange}
                name="title"
                value={title}
              />
              <label htmlFor="newSitePrefix">{__('Zephr Domain Prefix', 'zephr')}</label>
              <input
                id="newSitePrefix"
                onChange={handleInputChange}
                name="prefix"
                value={prefix}
              />
              <div className="button-group">
                <button
                  type="button"
                  onClick={createSite}
                  disabled={!title || !prefix}
                  className="button"
                >
                  {__('Create Site', 'zephr')}
                </button>
                <button
                  type="button"
                  onClick={() => setShowAddForm(!showAddForm)}
                  className="button"
                >
                  {__('Cancel', 'zephr')}
                </button>
              </div>
            </div>
          ) : (
            <p>
              <button
                type="button"
                onClick={() => setShowAddForm(!showAddForm)}
                className="button button-link"
              >
                {__('Add New Site', 'zephr')}
              </button>
            </p>
          )}
          <button type="submit" className="nextbutton">
            {__('Next', 'zephr')}
          </button>
        </form>
      ) : (
        <div className="loading">{__('Loading sites...', 'zephr')}</div>
      )}
    </div>
  );
};
