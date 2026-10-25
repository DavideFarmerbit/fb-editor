/**
 * Lets a field put compact actions (e.g. the spacing mode switch) into its
 * control frame's header line, keeping every control to as few lines as
 * possible.
 */
import { createContext, createPortal, useContext } from '@wordpress/element';
import type { ReactElement, ReactNode } from 'react';

export const FrameActionsContext = createContext< HTMLElement | null >( null );

export function FrameActions( { children }: { readonly children: ReactNode } ): ReactElement | null {
	const target = useContext( FrameActionsContext );
	return target === null ? null : createPortal( children, target );
}
