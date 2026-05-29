<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modelo de membresía de un usuario Vitrinexo.
 * Abstracción sobre los user meta de plan — getters y setters centralizados.
 */
class VX_Membership
{
    private int $user_id;

    private function __construct( int $user_id )
    {
        $this->user_id = $user_id;
    }

    /**
     * Factory.
     *
     * @param int $user_id
     * @return VX_Membership
     */
    public static function get( int $user_id ): self
    {
        return new self( $user_id );
    }

    // ── Getters ──────────────────────────────────────────────────

    public function get_plan(): string
    {
        return (string) get_user_meta( $this->user_id, VX_Membership_Meta::PLAN, true );
    }

    public function get_plan_estado(): string
    {
        return (string) get_user_meta( $this->user_id, VX_Membership_Meta::PLAN_ESTADO, true );
    }

    public function get_expiry(): int
    {
        return (int) get_user_meta( $this->user_id, VX_Membership_Meta::PLAN_VENCIMIENTO, true );
    }

    public function get_gateway_customer_id(): string
    {
        return (string) get_user_meta( $this->user_id, VX_Membership_Meta::GATEWAY_CUSTOMER_ID, true );
    }

    public function get_gateway_subscription_id(): string
    {
        return (string) get_user_meta( $this->user_id, VX_Membership_Meta::GATEWAY_SUBSCRIPTION, true );
    }

    // ── Estado ───────────────────────────────────────────────────

    public function is_active(): bool
    {
        if ( 'activo' !== $this->get_plan_estado() ) {
            return false;
        }

        $expiry = $this->get_expiry();
        if ( $expiry > 0 && $expiry < time() ) {
            return false; // vencido
        }

        return true;
    }

    public function is_founder(): bool
    {
        return 'fundador' === $this->get_plan();
    }

    public function has_lifetime_price(): bool
    {
        return (bool) get_user_meta( $this->user_id, VX_Membership_Meta::PRECIO_PREFERENTE, true );
    }

    public function is_expired(): bool
    {
        $expiry = $this->get_expiry();
        return $expiry > 0 && $expiry < time();
    }

    // ── Setters ──────────────────────────────────────────────────

    /**
     * Activa un plan para el usuario.
     *
     * @param string $plan   Plan ID (fundador, mensual, anual)
     * @param int    $expiry Timestamp de vencimiento (0 = sin vencimiento)
     */
    public function activate( string $plan, int $expiry = 0 ): void
    {
        update_user_meta( $this->user_id, VX_Membership_Meta::PLAN,            $plan );
        update_user_meta( $this->user_id, VX_Membership_Meta::PLAN_ESTADO,     'activo' );
        update_user_meta( $this->user_id, VX_Membership_Meta::PLAN_INICIO,     time() );
        update_user_meta( $this->user_id, VX_Membership_Meta::PLAN_VENCIMIENTO, $expiry );

        if ( 'fundador' === $plan ) {
            update_user_meta( $this->user_id, VX_Membership_Meta::PRECIO_PREFERENTE, true );
        }

        do_action( 'vx_membership_activated', $this->user_id, $plan );
    }

    public function cancel(): void
    {
        update_user_meta( $this->user_id, VX_Membership_Meta::PLAN_ESTADO, 'cancelado' );
        do_action( 'vx_membership_cancelled', $this->user_id );
    }

    public function mark_expired(): void
    {
        update_user_meta( $this->user_id, VX_Membership_Meta::PLAN_ESTADO, 'vencido' );
        do_action( 'vx_membership_expired', $this->user_id );
    }

    public function set_gateway_customer_id( string $id ): void
    {
        update_user_meta( $this->user_id, VX_Membership_Meta::GATEWAY_CUSTOMER_ID, $id );
    }

    public function set_gateway_subscription_id( string $id ): void
    {
        update_user_meta( $this->user_id, VX_Membership_Meta::GATEWAY_SUBSCRIPTION, $id );
    }
}
