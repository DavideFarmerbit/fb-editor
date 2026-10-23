import { registerPlugin } from '@wordpress/plugins';

import './store';
import '../Blocks/blocks';
import BreakpointPreview from '../Breakpoints/Preview/preview';

registerPlugin( 'fb-breakpoint-preview', {
	render: BreakpointPreview,
} );
