<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Link;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\LinkValue;

/**
 * A link: http(s), mailto, tel, or a path / anchor on this site. Anything
 * else (javascript:, data:…) is rejected.
 */
final readonly class LinkInput implements Input
{
	private const array PROTOCOLS = [ 'http', 'https', 'mailto', 'tel' ];

	public function parse( JsonValue $json ): LinkValue
	{
		$raw = trim( $json->get( 'url' )->string() );
		$url = str_starts_with( $raw, '/' ) || str_starts_with( $raw, '#' ) ? esc_url_raw( $raw ) : esc_url_raw( $raw, self::PROTOCOLS );

		if ( '' === $url ) {
			throw new InvalidJson( "{$json->path}.url: not an allowed link" );
		}

		return new LinkValue(
			$url,
			$json->has( 'newTab' ) && $json->get( 'newTab' )->bool(),
			$json->has( 'nofollow' ) && $json->get( 'nofollow' )->bool(),
		);
	}

	/**
	 * @return array{kind: 'link'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'link' ];
	}
}
