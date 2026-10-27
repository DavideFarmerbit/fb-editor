/**
 * Frontend half of Fb\Site\AdminSidebar\AdminSidebar: opens and closes the
 * sidebar (corner button, Escape, a click outside) and runs "Clear cache".
 * All markup and text come from PHP.
 */
import './admin-sidebar.scss';

const OPEN = 'is-open';
const BUSY = 'is-busy';

function setup(): void {
	const toggle = document.querySelector< HTMLButtonElement >( '.fb-admin-sidebar-toggle' );
	const sidebar = document.querySelector< HTMLElement >( '.fb-admin-sidebar' );
	if ( toggle === null || sidebar === null ) {
		return;
	}

	const setOpen = ( open: boolean ): void => {
		sidebar.classList.toggle( OPEN, open );
		toggle.classList.toggle( OPEN, open );
		toggle.setAttribute( 'aria-expanded', String( open ) );
		// Closed: out of the tab order and of the accessibility tree.
		sidebar.inert = ! open;
	};
	const isOpen = (): boolean => sidebar.classList.contains( OPEN );

	toggle.addEventListener( 'click', () => setOpen( ! isOpen() ) );
	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && isOpen() ) {
			setOpen( false );
			toggle.focus();
		}
	} );
	document.addEventListener( 'click', ( event ) => {
		const target = event.target;
		if ( isOpen() && target instanceof Node && ! sidebar.contains( target ) && ! toggle.contains( target ) ) {
			setOpen( false );
		}
	} );

	sidebar.querySelectorAll< HTMLButtonElement >( '[data-fb-clear-cache]' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => void clearCache( button ) );
	} );
}

/** Calls the cache plugin's endpoint; the result shows on the button itself. */
async function clearCache( button: HTMLButtonElement ): Promise< void > {
	const { fbClearCache: url, fbDone: done, fbFailed: failed } = button.dataset;
	const label = button.querySelector( 'span' );
	if ( url === undefined || label === null ) {
		return;
	}
	const original = label.textContent;

	button.classList.add( BUSY );
	button.disabled = true;
	let ok = false;
	try {
		ok = ( await fetch( url, { credentials: 'same-origin' } ) ).ok;
	} catch {
		ok = false;
	}
	button.classList.remove( BUSY );
	button.disabled = false;

	label.textContent = ( ok ? done : failed ) ?? original;
	window.setTimeout( () => {
		label.textContent = original;
	}, 2500 );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', setup );
} else {
	setup();
}
