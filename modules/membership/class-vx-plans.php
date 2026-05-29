<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Definición de planes disponibles de Vitrinexo.
 * Fuente única de verdad sobre precios, duraciones y beneficios.
 */
class VX_Plans
{
    const PLANS = [
        'fundador' => [
            'nombre'              => 'Socio Fundador',
            'precio'              => 0,
            'duracion_dias'       => 180,
            'precio_renovacion'   => null,
            'descripcion'         => 'Acceso completo durante el período fundacional',
        ],
        'mensual' => [
            'nombre'              => 'Plan Mensual',
            'precio'              => 2900,
            'duracion_dias'       => 30,
            'precio_renovacion'   => 2900,
            'descripcion'         => 'Acceso mensual a toda la plataforma',
        ],
        'anual' => [
            'nombre'              => 'Plan Anual',
            'precio'              => 24900,
            'duracion_dias'       => 365,
            'precio_renovacion'   => 24900,
            'descripcion'         => 'Acceso anual con descuento (equivale a 10 meses)',
        ],
    ];

    /**
     * Devuelve la definición de un plan específico.
     *
     * @param string $plan_id
     * @return array|null
     */
    public static function get( string $plan_id ): ?array
    {
        return self::PLANS[ $plan_id ] ?? null;
    }

    /**
     * Devuelve todos los planes disponibles.
     *
     * @return array
     */
    public static function all(): array
    {
        return self::PLANS;
    }

    /**
     * Verifica si un plan_id es válido.
     *
     * @param string $plan_id
     * @return bool
     */
    public static function is_valid( string $plan_id ): bool
    {
        return array_key_exists( $plan_id, self::PLANS );
    }
}
