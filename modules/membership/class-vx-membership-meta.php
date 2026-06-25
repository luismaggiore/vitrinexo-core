<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Constantes de meta keys de membresía. Centralizado aquí para evitar typos.
 * Ver también VX_User_Meta para las keys de plan que viven en user meta.
 */
class VX_Membership_Meta
{
    const PLAN                 = 'vx_plan';
    const PLAN_ESTADO          = 'vx_plan_estado';
    const PLAN_INICIO          = 'vx_plan_inicio';
    const PLAN_VENCIMIENTO     = 'vx_plan_vencimiento';
    const PRECIO_PREFERENTE    = 'vx_precio_preferente';
    const GATEWAY_CUSTOMER_ID  = 'vx_gateway_customer_id';
    const GATEWAY_SUBSCRIPTION = 'vx_gateway_subscription_id';

    // Tracking de avisos de vencimiento enviados (JSON: {'30d':1,'7d':1,'1d':1})
    const AVISOS_ENVIADOS      = 'vx_plan_avisos_enviados';
}
