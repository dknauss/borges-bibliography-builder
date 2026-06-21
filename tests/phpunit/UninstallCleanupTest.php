<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for the uninstall cleanup routine.
 *
 * The plugin only persists durable state as transients (keyed
 * `_transient_bbb_<md5>` / `_transient_timeout_bbb_<md5>` in the options
 * table) and object-cache entries in two named groups. These tests verify
 * uninstall removes exactly that and nothing else.
 *
 * @package BibliographyBuilder
 */

require_once dirname( __DIR__, 2 ) . '/uninstall-cleanup.php';

// ── Minimal mocks for WP functions not provided by bootstrap.php ───────────────

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() {
		return ! empty( $GLOBALS['bbb_test_multisite'] );
	}
}

if ( ! function_exists( 'wp_cache_flush_group' ) ) {
	function wp_cache_flush_group( $group ) {
		$GLOBALS['bbb_test_flushed_groups'][] = $group;
		return true;
	}
}

if ( ! function_exists( 'get_sites' ) ) {
	function get_sites( $args = array() ) {
		return $GLOBALS['bbb_test_sites'] ?? array();
	}
}

if ( ! function_exists( 'switch_to_blog' ) ) {
	function switch_to_blog( $blog_id ) {
		$GLOBALS['bbb_test_switched'][] = (int) $blog_id;
		return true;
	}
}

if ( ! function_exists( 'restore_current_blog' ) ) {
	function restore_current_blog() {
		$GLOBALS['bbb_test_restored'] = ( $GLOBALS['bbb_test_restored'] ?? 0 ) + 1;
		return true;
	}
}

/**
 * Convert a SQL LIKE pattern (as produced by esc_like + %) into a regex so the
 * fake $wpdb can emulate a DELETE ... WHERE option_name LIKE ... query.
 */
function bbb_test_like_to_regex( $like ) {
	$regex = '';
	$len   = strlen( $like );
	for ( $i = 0; $i < $len; $i++ ) {
		$ch = $like[ $i ];
		if ( '\\' === $ch && $i + 1 < $len ) {
			$regex .= preg_quote( $like[ ++$i ], '/' );
		} elseif ( '%' === $ch ) {
			$regex .= '.*';
		} elseif ( '_' === $ch ) {
			$regex .= '.';
		} else {
			$regex .= preg_quote( $ch, '/' );
		}
	}
	return '/^' . $regex . '$/';
}

/**
 * Fake $wpdb that holds an in-memory options table and executes the single
 * DELETE ... LIKE query the cleanup routine issues.
 */
class BBB_Test_WPDB {
	public $options = 'wp_options';
	public $store   = array();
	public $queries = array();

	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) {
			$quoted = "'" . str_replace( "'", "\\'", (string) $arg ) . "'";
			$query  = preg_replace( '/%s/', $quoted, $query, 1 );
		}
		return $query;
	}

	public function query( $sql ) {
		$this->queries[] = $sql;
		preg_match_all( "/LIKE '([^']*)'/i", $sql, $matches );
		$patterns = array_map( 'bbb_test_like_to_regex', $matches[1] );
		$deleted  = 0;
		foreach ( array_keys( $this->store ) as $name ) {
			foreach ( $patterns as $regex ) {
				if ( preg_match( $regex, $name ) ) {
					unset( $this->store[ $name ] );
					++$deleted;
					break;
				}
			}
		}
		return $deleted;
	}
}

final class UninstallCleanupTest extends TestCase {

	protected function setUp(): void {
		bibliography_builder_test_reset_state();
		$GLOBALS['bbb_test_flushed_groups'] = array();
		$GLOBALS['bbb_test_multisite']      = false;
		$GLOBALS['bbb_test_sites']          = array();
		$GLOBALS['bbb_test_switched']       = array();
		$GLOBALS['bbb_test_restored']       = 0;
	}

	private function seed_wpdb() {
		global $wpdb;
		$wpdb        = new BBB_Test_WPDB();
		$hash        = md5( 'bibliography_builder_formatter:format_demo' );
		$pmid_hash   = md5( 'bibliography_builder_pmid:pmid_123' );
		$wpdb->store = array(
			'_transient_bbb_' . $hash             => 'cached html',
			'_transient_timeout_bbb_' . $hash     => '9999999999',
			'_transient_bbb_' . $pmid_hash        => 'cached pmid',
			'_transient_timeout_bbb_' . $pmid_hash => '9999999999',
			'_transient_other_plugin'             => 'keep me',
			'_transient_timeout_other_plugin'     => 'keep me',
			'siteurl'                             => 'https://example.org',
			'blogname'                            => 'Demo',
		);
		return $wpdb;
	}

	public function test_deletes_plugin_transients_from_options_table() {
		$wpdb = $this->seed_wpdb();

		bibliography_builder_uninstall_cleanup();

		foreach ( array_keys( $wpdb->store ) as $name ) {
			$this->assertStringNotContainsString(
				'bbb_',
				$name,
				"Leftover plugin transient row: {$name}"
			);
		}
	}

	public function test_preserves_unrelated_options() {
		$wpdb = $this->seed_wpdb();

		bibliography_builder_uninstall_cleanup();

		$this->assertArrayHasKey( '_transient_other_plugin', $wpdb->store );
		$this->assertArrayHasKey( '_transient_timeout_other_plugin', $wpdb->store );
		$this->assertArrayHasKey( 'siteurl', $wpdb->store );
		$this->assertArrayHasKey( 'blogname', $wpdb->store );
	}

	public function test_flushes_both_object_cache_groups() {
		$this->seed_wpdb();

		bibliography_builder_uninstall_cleanup();

		$this->assertContains( 'bibliography_builder_formatter', $GLOBALS['bbb_test_flushed_groups'] );
		$this->assertContains( 'bibliography_builder_pmid', $GLOBALS['bbb_test_flushed_groups'] );
	}

	public function test_single_site_does_not_switch_blogs() {
		$this->seed_wpdb();

		bibliography_builder_uninstall_cleanup();

		$this->assertSame( array(), $GLOBALS['bbb_test_switched'] );
	}

	public function test_multisite_cleans_each_site() {
		global $wpdb;
		$wpdb                          = new BBB_Test_WPDB();
		$hash                          = md5( 'bibliography_builder_formatter:format_demo' );
		$wpdb->store                   = array( '_transient_bbb_' . $hash => 'x' );
		$GLOBALS['bbb_test_multisite'] = true;
		$GLOBALS['bbb_test_sites']     = array( 1, 2, 3 );

		bibliography_builder_uninstall_cleanup();

		$this->assertSame( array( 1, 2, 3 ), $GLOBALS['bbb_test_switched'] );
		$this->assertSame( 3, $GLOBALS['bbb_test_restored'] );
	}
}
