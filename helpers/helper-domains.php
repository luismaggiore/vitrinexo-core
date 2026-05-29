<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper para detección de dominios de email genéricos vs institucionales.
 */
class VX_Domain_Helper
{
    /**
     * Lista de dominios de email considerados genéricos (personales).
     * Los emails con estos dominios requieren verificación manual.
     */
    const GENERIC_DOMAINS = [
        'gmail.com', 'googlemail.com',
        'yahoo.com', 'yahoo.es', 'yahoo.com.mx', 'yahoo.com.ar', 'yahoo.co.uk',
        'hotmail.com', 'hotmail.es', 'hotmail.com.ar', 'hotmail.com.mx',
        'outlook.com', 'outlook.es',
        'live.com', 'live.cl', 'live.com.mx', 'live.com.ar',
        'msn.com',
        'icloud.com', 'me.com', 'mac.com',
        'protonmail.com', 'proton.me',
        'tutanota.com',
        'zoho.com',
        'aol.com',
        'yandex.com', 'yandex.ru',
        'mail.com',
        'inbox.com',
        'gmx.com', 'gmx.net',
    ];

    /**
     * Devuelve true si el dominio del email es genérico (personal).
     * Dominio institucional → verificación automática.
     * Dominio genérico → verificación manual por el equipo.
     *
     * @param string $email
     * @return bool
     */
    public static function is_generic( string $email ): bool
    {
        $email = strtolower( trim( $email ) );
        $parts = explode( '@', $email );

        if ( count( $parts ) !== 2 || empty( $parts[1] ) ) {
            return true; // email inválido → tratar como genérico
        }

        $domain = $parts[1];

        return in_array( $domain, self::GENERIC_DOMAINS, true );
    }

    /**
     * Devuelve true si el email es institucional (no genérico).
     *
     * @param string $email
     * @return bool
     */
    public static function is_institutional( string $email ): bool
    {
        return ! self::is_generic( $email );
    }
}
