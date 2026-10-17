<?php
/**
 * Apoio dos testes de licença (1.6.0): emite tokens assinados como o ML License Hub
 * emitiria, com um par de chaves Ed25519 gerado só para os testes.
 *
 * A chave pública de produção (MLPP_License::PUBLIC_KEY) não muda: os testes trocam,
 * por Reflection, a chave do objeto cliente já criado e devolvem a original no fim.
 * O código do plugin não tem nenhuma porta de entrada para trocar a chave.
 *
 * @package ML_Popup_Pro
 */

final class MLPP_Test_Hub {

	/** @var string|null */
	private static $keypair = null;

	private static function keypair(): string {
		if ( null === self::$keypair ) {
			self::$keypair = sodium_crypto_sign_keypair();
		}
		return self::$keypair;
	}

	public static function test_public_key(): string {
		return base64_encode( sodium_crypto_sign_publickey( self::keypair() ) );
	}

	private static function b64url( string $raw ): string {
		return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
	}

	/** Token no formato do Hub: b64url(json).b64url(assinatura). */
	public static function token( array $overrides = [], ?string $secret_key = null ): string {
		$client  = MLPP_License::client();
		$payload = array_merge(
			[
				'product'    => MLPP_License::PRODUCT_ID,
				'instance'   => $client->instance_id(),
				'premium'    => true,
				'status'     => 'active',
				'plan'       => 'trial',
				'exp'        => time() + 7 * DAY_IN_SECONDS,
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 20 * DAY_IN_SECONDS ),
			],
			$overrides
		);
		$json = (string) json_encode( $payload );
		$sk   = $secret_key ?? sodium_crypto_sign_secretkey( self::keypair() );
		return self::b64url( $json ) . '.' . self::b64url( sodium_crypto_sign_detached( $json, $sk ) );
	}

	/** Faz o cliente conferir os tokens com a chave dos testes. Devolve a chave anterior. */
	public static function use_test_key(): string {
		$prop = new ReflectionProperty( ML_License_Client_V2::class, 'public_key' );
		$prop->setAccessible( true );
		$client   = MLPP_License::client();
		$previous = (string) $prop->getValue( $client );
		$prop->setValue( $client, self::test_public_key() );
		return $previous;
	}

	public static function restore_key( string $key ): void {
		$prop = new ReflectionProperty( ML_License_Client_V2::class, 'public_key' );
		$prop->setAccessible( true );
		$prop->setValue( MLPP_License::client(), $key );
	}

	public static function option(): string {
		return MLPP_License::client()->option_name();
	}

	/** Grava o estado local como o cliente gravaria depois de falar com o Hub. */
	public static function store( string $mode, array $overrides = [], string $key = '' ): void {
		update_option(
			self::option(),
			[
				'license_key' => $key,
				'mode'        => $mode,
				'token'       => self::token( $overrides ),
				'trial_used'  => 'trial' === $mode ? 1 : 0,
				'checked_at'  => time(),
			]
		);
		MLPP_License::forget();
	}

	public static function clear(): void {
		delete_option( self::option() );
		MLPP_License::forget();
	}
}
