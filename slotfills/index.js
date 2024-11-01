import { registerPlugin } from '@wordpress/plugins';

// Sections.
import PostFeature from './sections/post-feature';

registerPlugin('zephr-post-feature', { render: PostFeature });
