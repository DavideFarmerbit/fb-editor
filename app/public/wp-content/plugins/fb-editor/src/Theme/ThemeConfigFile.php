<?php

declare(strict_types=1);

namespace Fb\Theme;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * The active (child) theme's `fb.json`: breakpoints and block defaults.
 * Read once per request. A broken file never breaks the site: it is
 * reported with _doing_it_wrong() and treated as absent.
 */
final class ThemeConfigFile
{
	public const string FILE = 'fb.json';

	private ?JsonValue $json = null;
	private bool $loaded = false;

	/**
	 * @param string|null $path Defaults to the active theme's fb.json.
	 */
	public function __construct(
		private readonly ?string $path = null,
	) {
	}

	public function json(): ?JsonValue
	{
		if ( ! $this->loaded ) {
			$this->loaded = true;
			$this->json   = $this->load();
		}

		return $this->json;
	}

	/**
	 * Optional top-level section, e.g. "breakpoints" or "defaults".
	 */
	public function section( string $key ): ?JsonValue
	{
		$json = $this->json();

		return null !== $json && $json->has( $key ) ? $json->get( $key ) : null;
	}

	/**
	 * Reports a problem in the file without breaking the site.
	 */
	public function report( string $message ): void
	{
		_doing_it_wrong( self::class, esc_html( self::FILE . ': ' . $message ), '0.1.0' );
	}

	private function load(): ?JsonValue
	{
		$file = $this->path ?? get_theme_file_path( self::FILE );

		if ( ! is_readable( $file ) ) {
			return null;
		}

		try {
			return JsonValue::parse( (string) file_get_contents( $file ), self::FILE );
		} catch ( InvalidJson $e ) {
			$this->report( $e->getMessage() );
			return null;
		}
	}
}
