<?php
/**
 * Standalone tests. Run with PHP 8.1+, without loading WordPress.
 */
define( 'ABSPATH', __DIR__ );
require_once __DIR__ . '/../includes/class-calculator.php';
require_once __DIR__ . '/../includes/class-plugin.php';

function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function wc_get_price_decimals() { return 2; }
function __( $text, $domain ) { return $text; }

class WC_Product {
	public string $regular = '99.00';
	public string $sale = '2.00';
	public function __construct( private string $type = 'simple' ) {}
	public function is_type( $type ) { return $type === $this->type; }
	public function set_regular_price( $value ) { $this->regular = $value; }
}
class WC_Admin_Meta_Boxes {
	public static array $errors = array();
	public static function add_error( $message ) { self::$errors[] = $message; }
}
function same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
	}
}

$cases = json_decode( file_get_contents( __DIR__ . '/cases.json' ), true, 512, JSON_THROW_ON_ERROR );
foreach ( $cases as [ $price, $weight, $decimals, $expected ] ) {
	same( $expected, \Carotte\PriceCalculator\Calculator::calculate( $price, $weight, $decimals ), $price . ' x ' . $weight );
}

$plugin = new \Carotte\PriceCalculator\Plugin( __FILE__ );
$_POST = array( '_carotte_price_per_unit' => '12,50 €/kg', '_carotte_unit' => '250gr' );
$product = new WC_Product();
$plugin->save( $product );
same( '3.13', $product->regular, 'server-side calculation' );
same( '2.00', $product->sale, 'sale untouched' );

foreach ( array( 'variable', 'variation', 'grouped', 'external' ) as $type ) {
	$product = new WC_Product( $type );
	$plugin->save( $product );
	same( '99.00', $product->regular, $type . ' untouched' );
}
foreach ( array(
	array(),
	array( '_carotte_unit' => '1 kg' ),
	array( '_carotte_price_per_unit' => array( '10' ), '_carotte_unit' => '1 kg' ),
	array( '_carotte_price_per_unit' => '10', '_carotte_unit' => array( '1 kg' ) ),
	array( '_carotte_price_per_unit' => '10', '_carotte_unit' => 'panier' ),
	array( '_carotte_price_per_unit' => '', '_carotte_unit' => '' ),
) as $post ) {
	$_POST = $post;
	$product = new WC_Product();
	$plugin->save( $product );
	same( '99.00', $product->regular, 'invalid or missing input preserved' );
}
same( 1, count( WC_Admin_Meta_Boxes::$errors ), 'only nonempty invalid input reports error' );
print "OK: " . count( $cases ) . " calculation cases and product save tests.\n";
