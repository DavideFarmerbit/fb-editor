<?php

declare(strict_types=1);

namespace Fb\Blocks\Storage;

/**
 * REST controller for the theme's design posts (block defaults, variants):
 * reading requires the same capability as editing (edit_theme_options),
 * like core's global styles endpoint. The stock posts controller would let
 * anyone read published ones.
 */
final class ThemeDesignController extends \WP_REST_Posts_Controller
{
	/**
	 * @param \WP_REST_Request $request
	 */
	public function get_items_permissions_check( $request ): bool|\WP_Error
	{
		return $this->canManage() ? parent::get_items_permissions_check( $request ) : $this->forbidden();
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function get_item_permissions_check( $request ): bool|\WP_Error
	{
		return $this->canManage() ? parent::get_item_permissions_check( $request ) : $this->forbidden();
	}

	private function canManage(): bool
	{
		return current_user_can( 'edit_theme_options' );
	}

	private function forbidden(): \WP_Error
	{
		return new \WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to view this.', 'fb-editor' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
}
