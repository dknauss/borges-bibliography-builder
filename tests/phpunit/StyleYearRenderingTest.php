<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Guards the bundled CSL styles against silently dropping the publication year.
 *
 * citeproc-php does not render `<date variable="issued" form="text"/>` (it
 * produces empty output even with the intl extension present), so styles must
 * use an explicit `<date-part name="year"/>`. See
 * docs/external-eccentricities.md. Three styles (Chicago notes, OSCOLA, MLA)
 * regressed this way and dropped the year on every entry.
 *
 * @package BibliographyBuilder
 */
final class StyleYearRenderingTest extends TestCase {

	private static function styles_dir() {
		return dirname( __DIR__, 2 ) . '/packages/citation-style-language-styles';
	}

	public static function styleProvider() {
		$cases = array();
		foreach ( glob( self::styles_dir() . '/*.csl' ) as $file ) {
			$cases[ basename( $file, '.csl' ) ] = array( $file );
		}
		return $cases;
	}

	/**
	 * Static guard: no style may use the form="text" issued date that
	 * citeproc-php fails to render.
	 */
	#[DataProvider( 'styleProvider' )]
	public function test_style_does_not_use_unrenderable_issued_date( $style_path ) {
		$xml = file_get_contents( $style_path );
		$this->assertStringNotContainsString(
			'variable="issued" form="text"',
			$xml,
			basename( $style_path ) . ' uses an issued date form citeproc-php does not render; use <date-part name="year"/>'
		);
	}

	/**
	 * Behavioural guard: each style renders the publication year.
	 */
	#[DataProvider( 'styleProvider' )]
	public function test_style_renders_publication_year( $style_path ) {
		if ( ! class_exists( '\\Seboettg\\CiteProc\\CiteProc' ) ) {
			$this->markTestSkipped( 'citeproc-php is not available.' );
		}

		$items = json_decode(
			'[{"id":"a","type":"article-journal","title":"Sample Article","author":[{"family":"Doe","given":"Jane"}],"container-title":"Journal of Tests","issued":{"date-parts":[[1963]]}}]'
		);

		$formatter = new \Seboettg\CiteProc\CiteProc( file_get_contents( $style_path ), 'en-US' );
		$output    = strip_tags( $formatter->render( $items, 'bibliography' ) );

		$this->assertStringContainsString(
			'1963',
			$output,
			basename( $style_path ) . ' should render the publication year'
		);
	}
}
