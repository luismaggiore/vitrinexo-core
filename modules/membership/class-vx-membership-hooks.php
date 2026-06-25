<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hooks de membresía — integración con pasarela de pago.
 *
 * El webhook real de Stripe está en rest/rest-stripe.php con verificación de firma.
 * Este archivo registra hooks internos de WordPress y bloquea el endpoint
 * /webhook/payment heredado para evitar activaciones fraudulentas.
 */
class VX_Membership_Hooks
{
    public static function init(): void
    {
        // Hooks internos disparados por rest-stripe.php tras verificar firma
        add_action( 'vx_payment_success', [ self::class, 'on_payment_success' ], 10, 3 );
        add_action( 'vx_payment_failed',  [ self::class, 'on_payment_failed' ],  10, 2 );

        // Bloquear endpoint heredado — siempre 400
        add_action( 'rest_api_init', [ self::class, 'register_disabled_webhook' ] );
    }

    /**
     * Endpoint heredado DESHABILITADO.
     * El webhook real es POST /vitrinexo/v1/stripe/webhook (rest-stripe.php).
     * Este endpoint devuelve 400 siempre para evitar que sea explotado sin firma.
     */
    public static function register_disabled_webhook(): void
    {
        register_rest_route( VX_REST_NAMESPACE, '/webhook/payment', [
            'methods'             => 'POST',
            'callback'            => fn() => new WP_REST_Response(
                [ 'error' => 'endpoint_deprecado', 'message' => 'Usar POST /vitrinexo/v1/stripe/webhook' ],
                400
            ),
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Pago exitoso — guarda datos del gateway.
     * Disparado por rest-stripe.php DESPUÉS de verificar firma de Stripe.
     *
     * @param int    $user_id
     * @param string $plan_id
     * @param array  $session  Stripe session (array)
     */
    public static function on_payment_success( int $user_id, string $plan_id, array $session ): void
    {
        $membresia = VX_Membership::get( $user_id );

        $customer_id = $session['customer'] ?? '';
        if ( $customer_id ) $membresia->set_gateway_customer_id( $customer_id );

        $subscription_id = $session['subscription'] ?? '';
        if ( $subscription_id ) $membresia->set_gateway_subscription_id( $subscription_id );
    }

    public static function on_payment_failed( int $user_id, array $session ): void
    {
        error_log( sprintf( '[Vitrinexo] Payment failed for user #%d', $user_id ) );
    }
}
