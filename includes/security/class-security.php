<?php
/**
 * Security utilities.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget\Security;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Security primitives for credentials, input, origin checks, and throttling. */
final class Security {
	const CIPHER_PREFIX = 'acw1:';

	/**
	 * Normalize an API key without changing printable key characters.
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_api_key( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = preg_replace( '/[\x00-\x1F\x7F]/', '', trim( wp_unslash( $value ) ) );
		return substr( (string) $value, 0, 512 );
	}

	/** Encrypt a credential using WordPress salts. */
	public static function encrypt( $plaintext ) {
		if ( '' === $plaintext || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$iv  = random_bytes( 12 );
		$key = self::encryption_key();
		$tag = '';
		$raw = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

		if ( false === $raw ) {
			return '';
		}

		return self::CIPHER_PREFIX . base64_encode( $iv . $tag . $raw ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/** Decrypt a stored credential. */
	public static function decrypt( $encrypted ) {
		if ( ! is_string( $encrypted ) || 0 !== strpos( $encrypted, self::CIPHER_PREFIX ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$payload = base64_decode( substr( $encrypted, strlen( self::CIPHER_PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $payload || strlen( $payload ) < 29 ) {
			return '';
		}

		$iv         = substr( $payload, 0, 12 );
		$tag        = substr( $payload, 12, 16 );
		$ciphertext = substr( $payload, 28 );
		$plaintext  = openssl_decrypt( $ciphertext, 'aes-256-gcm', self::encryption_key(), OPENSSL_RAW_DATA, $iv, $tag );

		return false === $plaintext ? '' : $plaintext;
	}

	/** Validate optional conversation ID. */
	public static function sanitize_conversation_id( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = preg_replace( '/[^A-Za-z0-9_.:-]/', '', wp_unslash( $value ) );
		return substr( (string) $value, 0, 128 );
	}

	/** Ensure browser-originated requests come from this WordPress site. */
	public static function is_same_origin_request() {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $origin ) {
			return true;
		}

		$origin_host = wp_parse_url( $origin, PHP_URL_HOST );
		$site_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		return $origin_host && $site_host && hash_equals( strtolower( $site_host ), strtolower( $origin_host ) );
	}

	/**
	 * Apply a fixed-window per-IP rate limit.
	 *
	 * @param string $bucket Bucket name.
	 * @param int    $limit  Maximum requests.
	 * @param int    $window Window in seconds.
	 * @return true|WP_Error
	 */
	public static function rate_limit( $bucket, $limit, $window ) {
		$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$window_key = (int) floor( time() / $window );
		$key        = 'acw_rl_' . hash_hmac( 'sha256', $bucket . '|' . $ip . '|' . $window_key, wp_salt( 'nonce' ) );
		$count      = (int) get_transient( $key );

		if ( $count >= $limit ) {
			$retry_after = $window - ( time() % $window );
			return new WP_Error(
				'ai_chat_rate_limited',
				__( 'Too many chat connection attempts. Please wait a moment and try again.', 'ai-chat-widget' ),
				array( 'status' => 429, 'retry_after' => $retry_after )
			);
		}

		set_transient( $key, $count + 1, $window + 1 );
		return true;
	}

	/** Build deterministic encryption key. */
	private static function encryption_key() {
		return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
	}
}
