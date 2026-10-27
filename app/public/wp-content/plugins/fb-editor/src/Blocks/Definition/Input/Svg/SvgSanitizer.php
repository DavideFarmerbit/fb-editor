<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Svg;

use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\SvgValue;

/**
 * Turns pasted SVG into markup safe to print: an allowlist of drawing
 * elements and presentation attributes, nothing that runs or loads.
 *
 * - no DOCTYPE (so no entities), no scripts, no foreignObject, no images;
 * - no event handlers, no `style` (no url() loads), no class (no clashes);
 * - references (href, url(…)) only to ids inside the same SVG.
 *
 * The editor's preview sanitizes with the same lists (svg.ts).
 */
final class SvgSanitizer
{
	/** Comma-separated (exported to TS). */
	#[ExportToTypeScript]
	public const string ELEMENTS = 'svg,g,path,circle,ellipse,line,polyline,polygon,rect,title,desc,defs,symbol,use,lineargradient,radialgradient,stop,clippath,mask,text,tspan';

	#[ExportToTypeScript]
	public const string ATTRIBUTES = 'viewbox,width,height,fill,fill-opacity,fill-rule,clip-rule,stroke,stroke-width,stroke-linecap,stroke-linejoin,stroke-miterlimit,stroke-dasharray,stroke-dashoffset,stroke-opacity,opacity,transform,d,cx,cy,r,rx,ry,x,y,x1,y1,x2,y2,points,id,offset,stop-color,stop-opacity,gradientunits,gradienttransform,spreadmethod,clip-path,mask,href,xlink:href,preserveaspectratio,xmlns,xmlns:xlink,font-size,font-family,font-weight,text-anchor,dominant-baseline,vector-effect,role,aria-hidden,aria-label,focusable';

	/** Pasted icons are small: anything bigger is not an icon. */
	#[ExportToTypeScript]
	public const int MAX_LENGTH = 100000;

	/** Attribute values that may only reference ids in the same SVG. */
	private const string LOCAL_REFERENCE = '/^#[A-Za-z_][\w.-]*$/';

	public static function sanitize( string $raw ): ?SvgValue
	{
		$raw = trim( $raw );
		if ( '' === $raw || strlen( $raw ) > self::MAX_LENGTH || 1 === preg_match( '/<!DOCTYPE|<!ENTITY/i', $raw ) ) {
			return null;
		}

		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded   = $document->loadXML( $raw, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$root = $loaded ? $document->documentElement : null;
		if ( null === $root || 'svg' !== strtolower( $root->localName ?? '' ) ) {
			return null;
		}

		self::clean( $root, explode( ',', self::ELEMENTS ), explode( ',', self::ATTRIBUTES ) );
		$markup = $document->saveXML( $root );

		return false === $markup ? null : new SvgValue( $markup );
	}

	/**
	 * @param list<string> $elements
	 * @param list<string> $attributes
	 */
	private static function clean( \DOMElement $element, array $elements, array $attributes ): void
	{
		foreach ( iterator_to_array( $element->attributes ?? [] ) as $attribute ) {
			if ( ! $attribute instanceof \DOMAttr || ! self::keepsAttribute( $attribute, $attributes ) ) {
				$element->removeAttributeNode( $attribute );
			}
		}

		foreach ( iterator_to_array( $element->childNodes ) as $child ) {
			if ( $child instanceof \DOMElement ) {
				if ( in_array( strtolower( $child->localName ?? '' ), $elements, true ) ) {
					self::clean( $child, $elements, $attributes );
				} else {
					$element->removeChild( $child );
				}
			} elseif ( ! $child instanceof \DOMText ) {
				// Comments, CDATA, processing instructions.
				$element->removeChild( $child );
			}
		}
	}

	/**
	 * @param list<string> $attributes
	 */
	private static function keepsAttribute( \DOMAttr $attribute, array $attributes ): bool
	{
		$name  = strtolower( $attribute->nodeName );
		$value = trim( $attribute->value );

		if ( ! in_array( $name, $attributes, true ) ) {
			return false;
		}
		if ( 'href' === $name || 'xlink:href' === $name ) {
			return 1 === preg_match( self::LOCAL_REFERENCE, $value );
		}
		// url(…) anywhere (fill, clip-path, mask…) only to a local id.
		if ( str_contains( strtolower( $value ), 'url(' ) ) {
			return 1 === preg_match( '/^url\(\s*#[A-Za-z_][\w.-]*\s*\)$/i', $value );
		}

		return ! str_contains( strtolower( $value ), 'javascript:' );
	}
}
