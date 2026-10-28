<?php
/**
 * One-off: converts stored fb values from the old shape to looks.
 *
 *   {"variant"?, "base": {…}, "states": {"hover": {…}}}
 *   → {"variant"?, "looks": [{"values": {…}}, {"states": ["hover"], "values": {…}}]}
 *
 * Where: the `fb` attribute in every block comment (any post type, except
 * revisions), saved type defaults (whole content), saved variants (each
 * variant's `values`). Already converted values are left alone, so running
 * it twice is harmless.
 *
 * Writes the database directly: no revisions, and no content filters (kses
 * would strip markup when run from WP-CLI, which has no user). Only block
 * comments are rewritten; the markup between them is never touched.
 *
 *   wp eval-file wp-content/plugins/fb-editor/bin/migrate-looks.php           # dry run
 *   wp eval-file wp-content/plugins/fb-editor/bin/migrate-looks.php apply     # write
 *
 * Applying saves the original content of every changed post to
 * uploads/fb-migrate-looks-backup-<time>.json first (post ID → content).
 *
 * Delete this file once every site is converted.
 *
 * Not strict_types: eval-file runs it through eval(), where the declaration
 * can't come first.
 *
 * @package FbEditor
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

/** @var list<string> $args */
$fb_apply = in_array( 'apply', $args ?? [], true );

/**
 * Old values → looks; null when there's nothing old to convert.
 */
$fb_convert = static function ( mixed $values ): ?stdClass {
	if ( ! $values instanceof stdClass || ( ! property_exists( $values, 'base' ) && ! property_exists( $values, 'states' ) ) ) {
		return null;
	}

	$looks = [];
	if ( isset( $values->base ) && ( is_object( $values->base ) || is_array( $values->base ) ) && [] !== (array) $values->base ) {
		$looks[] = (object) [ 'values' => $values->base ];
	}
	if ( isset( $values->states ) && ( is_object( $values->states ) || is_array( $values->states ) ) ) {
		foreach ( (array) $values->states as $state => $stateValues ) {
			if ( ( is_object( $stateValues ) || is_array( $stateValues ) ) && [] !== (array) $stateValues ) {
				$looks[] = (object) [ 'states' => [ (string) $state ], 'values' => $stateValues ];
			}
		}
	}

	$converted = new stdClass();
	foreach ( get_object_vars( $values ) as $key => $value ) {
		if ( 'base' !== $key && 'states' !== $key ) {
			$converted->{$key} = $value;
		}
	}
	if ( [] !== $looks ) {
		$converted->looks = $looks;
	}

	return $converted;
};

/**
 * Post content with every block comment's `fb` attribute converted.
 * Returns [content, blocks changed].
 *
 * @return array{string, int}
 */
$fb_convert_blocks = static function ( string $content ) use ( $fb_convert ): array {
	$changed = 0;
	// Block attributes never contain "-->": core escapes "--" when saving.
	$content = (string) preg_replace_callback(
		'#<!-- wp:([a-z0-9/_-]+) (\{.*?\}) (/)?-->#s',
		static function ( array $match ) use ( $fb_convert, &$changed ): string {
			$attributes = json_decode( $match[2], false );
			if ( ! $attributes instanceof stdClass || ! isset( $attributes->fb ) ) {
				return $match[0];
			}
			$converted = $fb_convert( $attributes->fb );
			if ( null === $converted ) {
				return $match[0];
			}
			$attributes->fb = $converted;
			++$changed;

			return '<!-- wp:' . $match[1] . ' ' . serialize_block_attributes( get_object_vars( $attributes ) ) . ' ' . ( $match[3] ?? '' ) . '-->';
		},
		$content
	);

	return [ $content, $changed ];
};

global $wpdb;

$fb_rows = $wpdb->get_results(
	"SELECT ID, post_type, post_title, post_content FROM {$wpdb->posts} WHERE post_type <> 'revision' AND ( post_content LIKE '%\"fb\":%' OR post_type IN ( 'fb_block_default', 'fb_block_variants' ) )"
);

$fb_total  = 0;
$fb_backup = [];
foreach ( $fb_rows as $fb_row ) {
	$fb_before = (string) $fb_row->post_content;
	$fb_after  = $fb_before;
	$fb_count  = 0;

	if ( 'fb_block_default' === $fb_row->post_type ) {
		$fb_converted = $fb_convert( json_decode( $fb_before, false ) );
		if ( null !== $fb_converted ) {
			$fb_after = (string) wp_json_encode( $fb_converted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION );
			$fb_count = 1;
		}
	} elseif ( 'fb_block_variants' === $fb_row->post_type ) {
		$fb_variants = json_decode( $fb_before, false );
		if ( is_array( $fb_variants ) ) {
			foreach ( $fb_variants as $fb_variant ) {
				$fb_converted = $fb_variant instanceof stdClass && isset( $fb_variant->values ) ? $fb_convert( $fb_variant->values ) : null;
				if ( null !== $fb_converted ) {
					$fb_variant->values = $fb_converted;
					++$fb_count;
				}
			}
			if ( $fb_count > 0 ) {
				$fb_after = (string) wp_json_encode( $fb_variants, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION );
			}
		}
	} else {
		[ $fb_after, $fb_count ] = $fb_convert_blocks( $fb_before );
	}

	if ( 0 === $fb_count ) {
		continue;
	}

	$fb_total += $fb_count;
	WP_CLI::log( sprintf( '%s #%d %s "%s": %d converted', $fb_apply ? 'Updated' : 'Would update', $fb_row->ID, $fb_row->post_type, $fb_row->post_title, $fb_count ) );

	if ( $fb_apply ) {
		$fb_backup[ (int) $fb_row->ID ] = $fb_before;
		$wpdb->update( $wpdb->posts, [ 'post_content' => $fb_after ], [ 'ID' => $fb_row->ID ] );
		clean_post_cache( (int) $fb_row->ID );
	}
}

if ( [] !== $fb_backup ) {
	// The original content of every changed post, by ID: to undo, write it back.
	$fb_file = trailingslashit( wp_upload_dir()['basedir'] ) . 'fb-migrate-looks-backup-' . gmdate( 'Ymd-His' ) . '.json';
	file_put_contents( $fb_file, (string) wp_json_encode( $fb_backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	WP_CLI::log( "Backup of the original content: {$fb_file}" );
}

WP_CLI::success( sprintf( '%d value sets %s.', $fb_total, $fb_apply ? 'converted' : 'to convert (dry run: add "apply" to write)' ) );
