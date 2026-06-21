<?php
/**
 * Fired when the plugin is uninstalled (deleted) via the WordPress admin.
 *
 * Removes the transient and object-cache data the plugin creates. Bibliography
 * blocks stored in post_content are user content and are left in place.
 *
 * @package BibliographyBuilder
 */

// Bail unless invoked by WordPress' uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/uninstall-cleanup.php';

bibliography_builder_uninstall_cleanup();
