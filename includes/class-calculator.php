<?php
namespace Carotte\PriceCalculator;

defined( 'ABSPATH' ) || exit;

final class Calculator {
	/**
	 * Only accept one unambiguous quantity with an optional supported suffix.
	 * Bare numbers mean EUR/kg for prices and kilograms for weights.
	 */
	private static function parse( string $value, bool $weight ): ?float {
		$value = preg_replace( '/[\s\x{00a0}\x{202f}]+/u', ' ', trim( $value ) );
		if ( ! is_string( $value ) ) {
			return null;
		}
		$number = '([0-9]+(?:[.,][0-9]+)?)';
		$kg     = '(?:kg|kilos?|kilogrammes?|kilograms?)';
		$grams  = '(?:g|gr|grammes?|grams?)';
		$suffix = $weight ? '(' . $kg . '|' . $grams . ')?' : '(?:(?:€|eur(?:os?)?)?\s*(?:(?:/|par)\s*' . $kg . ')?)';

		if ( ! preg_match( '~^\s*' . $number . '\s*' . $suffix . '\s*$~iu', $value, $matches ) ) {
			return null;
		}
		$amount = (float) str_replace( ',', '.', $matches[1] );
		if ( ! is_finite( $amount ) || $amount < 0 || ( $weight && $amount <= 0 ) ) {
			return null;
		}
		if ( $weight && isset( $matches[2] ) && preg_match( '/^' . $grams . '$/iu', $matches[2] ) ) {
			$amount /= 1000;
		}
		return $amount;
	}

	public static function calculate( string $price, string $weight, int $decimals = 2 ): ?string {
		$price  = self::parse( $price, false );
		$weight = self::parse( $weight, true );
		if ( null === $price || null === $weight || ! is_finite( $price * $weight ) ) {
			return null;
		}
		// Match the browser's half-up rounding, including floating point ties.
		$factor = 10 ** max( 0, min( 8, $decimals ) );
		$total  = $price * $weight;
		if ( ! is_finite( $total * $factor ) ) {
			return null;
		}
		$total  = floor( $total * $factor + 0.5 + PHP_FLOAT_EPSILON * max( 1, $total * $factor ) ) / $factor;
		return number_format( $total, max( 0, min( 8, $decimals ) ), '.', '' );
	}
}
