<?php
namespace Carotte\PriceCalculator;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public function __construct( private string $file ) {}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save' ), 20 );
	}

	public function enqueue(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}
		wp_enqueue_script( 'carotte-price-calculator', plugins_url( 'assets/admin.js', $this->file ), array( 'jquery', 'wc-admin-product-meta-boxes' ), '0.1.0', true );
		wp_localize_script( 'carotte-price-calculator', 'carottePriceCalculator', array(
			'decimals'  => wc_get_price_decimals(),
			'separator' => wc_get_price_decimal_separator(),
			'invalid'   => __( 'Calcul Carotte : renseignez un prix au kg (ex. 12,50 €/kg) et un poids (ex. 3,6 kg ou 250 g). Sans unité, le poids est en kg. Prix normal inchangé.', 'carotte-wc-price-calculator' ),
			'updated'   => __( 'Calcul Carotte : prix normal calculé depuis le prix au kg et le conditionnement.', 'carotte-wc-price-calculator' ),
		) );
	}

	/**
	 * Called only by the classic WooCommerce product save, after its nonce and
	 * capability checks. Do not hook generic saves, imports or the REST API.
	 */
	public function save( \WC_Product $product ): void {
		if ( ! $product->is_type( 'simple' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$price  = $_POST['_carotte_price_per_unit'] ?? null;
		$weight = $_POST['_carotte_unit'] ?? null;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! is_string( $price ) || ! is_string( $weight ) ) {
			return;
		}
		$price  = sanitize_text_field( wp_unslash( $price ) );
		$weight = sanitize_text_field( wp_unslash( $weight ) );
		$total  = Calculator::calculate( $price, $weight, wc_get_price_decimals() );
		if ( null !== $total ) {
			$product->set_regular_price( $total );
		} elseif ( '' !== trim( $price ) || '' !== trim( $weight ) ) {
			\WC_Admin_Meta_Boxes::add_error( __( 'Calcul Carotte impossible : vérifiez le prix au kg et le poids du conditionnement. Le prix normal saisi a été conservé.', 'carotte-wc-price-calculator' ) );
		}
	}
}
