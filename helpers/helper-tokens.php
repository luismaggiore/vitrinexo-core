<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper para generación y validación de tokens UUID v4.
 */
class VX_Token_Helper
{
    /**
     * Genera un UUID v4 usando wp_generate_uuid4() (disponible desde WP 4.7).
     */
    public static function generate(): string
    {
        return wp_generate_uuid4();
    }

    /**
     * Valida que el string tenga formato UUID v4 correcto.
     *
     * @param string $token
     * @return bool
     */
    public static function is_valid_format( string $token ): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $token
        );
    }

    /**
     * Valida un token comparándolo con el esperado (constant-time comparison).
     *
     * @param string $token     Token recibido.
     * @param string $expected  Token almacenado.
     * @return bool
     */
    public static function validate( string $token, string $expected ): bool
    {
        if ( empty( $token ) || empty( $expected ) ) {
            return false;
        }
        return hash_equals( $expected, $token );
    }
}
