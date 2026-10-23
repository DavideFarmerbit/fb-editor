<?php

declare(strict_types=1);

namespace Fb;

/**
 * Filesystem and URL locations inside the plugin.
 */
final readonly class PluginPaths
{
	public function __construct(
		private string $dir,
		private string $url,
	) {
	}

	public static function fromPluginFile( string $file ): self
	{
		return new self( plugin_dir_path( $file ), plugin_dir_url( $file ) );
	}

	public function path( string $relative ): string
	{
		return $this->dir . ltrim( $relative, '/' );
	}

	public function url( string $relative ): string
	{
		return $this->url . ltrim( $relative, '/' );
	}

	/**
	 * Reads the `<entry>.asset.php` file emitted by @wordpress/scripts.
	 */
	public function buildAsset( string $entry ): ?BuildAsset
	{
		$file = $this->path( "build/{$entry}.asset.php" );

		return is_readable( $file ) ? BuildAsset::fromFile( $file ) : null;
	}
}
