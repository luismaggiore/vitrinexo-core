<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Planes de FACTURACIÓN de Vitrinexo.
 *
 * El badge "Afiliado Original" (vx_es_fundador) es INDEPENDIENTE del plan.
 * duracion_dias=0 → sin vencimiento automático (plan gratuito indefinido).
 */
class VX_Plans
{
    const PLANS = [
        'gratuito' => [
            'nombre'            => 'Gratuito',
            'precio'            => 0,
            'duracion_dias'     => 0,
            'precio_renovacion' => 0,
            'descripcion'       => 'Acceso gratuito (fase beta o plan base)',
        ],
        'mensual' => [
            'nombre'            => 'Plan Mensual',
            'precio'            => 2900,
            'duracion_dias'     => 30,
            'precio_renovacion' => 2900,
            'descripcion'       => 'Acceso mensual a toda la plataforma',
        ],
        'anual' => [
            'nombre'            => 'Plan Anual',
            'precio'            => 24900,
            'duracion_dias'     => 365,
            'precio_renovacion' => 24900,
            'descripcion'       => 'Acceso anual con descuento (equivale a 10 meses)',
        ],
        'preferencial' => [
            'nombre'            => 'Plan Fundador',
            'precio'            => 1900,
            'duracion_dias'     => 30,
            'precio_renovacion' => 1900,
            'descripcion'       => 'Precio exclusivo para Socios Fundadores',
            'solo_fundadores'   => true,
        ],
    ];

    public static function get( string $plan_id ): ?array  { return self::PLANS[ $plan_id ] ?? null; }
    public static function all(): array                    { return self::PLANS; }
    public static function is_valid( string $plan_id ): bool { return array_key_exists( $plan_id, self::PLANS ); }

    /**
     * Calcula el timestamp de vencimiento para un plan.
     * Devuelve 0 (sin vencimiento) para planes con duracion_dias=0.
     */
    public static function compute_expiry( string $plan_id ): int
    {
        $plan = self::get( $plan_id );
        if ( ! $plan || 0 === ( $plan['duracion_dias'] ?? 0 ) ) return 0;
        return time() + ( $plan['duracion_dias'] * DAY_IN_SECONDS );
    }
}
