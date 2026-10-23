import { registerPlugin } from '@wordpress/plugins';

import './store';
import BreakpointPreview from './breakpoint-preview';

registerPlugin( 'fb-breakpoint-preview', {
	render: BreakpointPreview,
} );
