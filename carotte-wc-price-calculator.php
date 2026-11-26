<?php
/**
 * Plugin Name: Carotte WC Price Calculator
 * Description: Calcule le prix normal des produits simples depuis le prix au kg et le conditionnement Carotte.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Author: Carotte
 * License: GPL-2.0-or-later
 * Text Domain: carotte-wc-price-calculator
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-calculator.php';
require_once __DIR__ . '/includes/class-plugin.php';

add_action( 'plugins_loaded', static function () {
	if ( class_exists( 'WooCommerce' ) ) {
		( new \Carotte\PriceCalculator\Plugin( __FILE__ ) )->register();
	} else {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Carotte WC Price Calculator nécessite WooCommerce.', 'carotte-wc-price-calculator' ) . '</p></div>';
		} );
	}
} );
