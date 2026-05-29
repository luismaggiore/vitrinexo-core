<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hooks de membresía — integración con pasarela de pago.
 * Esta clase es un stub para la integración futura con Stripe/PayPal.
 * Los pagos se procesan externamente y se activan via webhook.
 */
class VX_Membership_Hooks
{
    public static function init(): void
    {
        // Webhook de pago exitoso
        add_action( 'vx_payment_success', [ self::class, 'on_payment_success' ], 10, 3 );

        // Webhook de cancelación/fallo
        add_action( 'vx_payment_failed',  [ self::class, 'on_payment_failed' ],  10, 2 );

        // Webhook endpoint para Stripe
        add_action( 'rest_api_init', [ self::class, 'register_webhook_endpoint' ] );
    }

    public static function register_webhook_endpoint(): void
    {
        register_rest_route( VX_REST_NAMESPACE, '/webhook/payment', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'handle_webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Procesa el webhook de pago.
     * Verifica la firma del proveedor antes de activar la membresía.
     */
    public static function handle_webhook( WP_REST_Request $request ): WP_REST_Response
    {
        $payload = $request->get_body();
        $sig     = $request->get_header( 'Stripe-Signature' );

        // TODO: verificar firma con STRIPE_WEBHOOK_SECRET
        // Por ahora, solo registrar el evento
        $body = json_decode( $payload, true );
        if ( ! $body ) {
            return new WP_REST_Response( [ 'error' => 'invalid_payload' ], 400 );
        }

        $event_type = $body['type'] ?? '';

        if ( 'checkout.session.completed' === $event_type ) {
            $session    = $body['data']['object'] ?? [];
            $user_id    = (int) ( $session['metadata']['user_id'] ?? 0 );
            $plan_id    = (string) ( $session['metadata']['plan_id'] ?? '' );

            if ( $user_id && $plan_id ) {
                $plan = VX_Plans::get( $plan_id );
                if ( $plan ) {
                    $expiry    = time() + ( $plan['duracion_dias'] * DAY_IN_SECONDS );
                    $membresia = VX_Membership::get( $user_id );
                    $membresia->activate( $plan_id, $expiry );

                    do_action( 'vx_payment_success', $user_id, $plan_id, $session );
                }
            }
        }

        return new WP_REST_Response( [ 'received' => true ], 200 );
    }

    public static function on_payment_success( int $user_id, string $plan_id, array $session ): void
    {
        $user = VX_User::get( $user_id );
        if ( ! $user ) return;

        // Guardar ID de cliente del gateway
        $customer_id = $session['customer'] ?? '';
        if ( $customer_id ) {
            update_user_meta( $user_id, VX_User_Meta::GATEWAY_CUSTOMER_ID, $customer_id );
        }

        $subscription_id = $session['subscription'] ?? '';
        if ( $subscription_id ) {
            update_user_meta( $user_id, VX_User_Meta::GATEWAY_SUBSCRIPTION, $subscription_id );
        }
    }

    public static function on_payment_failed( int $user_id, array $session ): void
    {
        // Logging para debugging
        error_log( sprintf( '[Vitrinexo] Payment failed for user #%d', $user_id ) );
    }
}
