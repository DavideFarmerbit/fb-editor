import { registerPlugin } from '@wordpress/plugins';

// First: core blocks register on domReady and must see this filter.
import '../CoreBlocks/core-block-restrictions';
import '../CoreBlocks/core-block-controls';

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
