import { registerPlugin } from '@wordpress/plugins';

import './store';
import '../Blocks/blocks';
import { DefaultsSidebar } from '../Blocks/Defaults/defaults-sidebar';
import BreakpointPreview from '../Breakpoints/Preview/preview';

registerPlugin( 'fb-breakpoint-preview', {
	render: BreakpointPreview,
} );

registerPlugin( 'fb-block-defaults', {
	render: DefaultsSidebar,
} );
