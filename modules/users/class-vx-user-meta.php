<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registro centralizado de todas las meta keys de usuario de Vitrinexo.
 * Nadie más en el código debe escribir estas strings directamente — usar las constantes.
 */
class VX_User_Meta
{
    // Identidad y perfil público
    const NOMBRE             = 'vx_nombre';
    const APELLIDO           = 'vx_apellido';
    const PERFIL_SLUG        = 'vx_perfil_slug';
    const FOTO               = 'vx_foto';
    const BIO                = 'vx_bio';
    const CIUDAD             = 'vx_ciudad';
    const PAIS               = 'vx_pais';
    const CONTACTO_PREFERIDO = 'vx_contacto_preferido';
    const TELEFONO           = 'vx_telefono';
    const LINKEDIN           = 'vx_linkedin';

    // Verificación y estado de cuenta
    const ESTADO                = 'vx_estado';
    const TIPO_VERIFICACION     = 'vx_tipo_verificacion';
    const TOKEN_CONFIRMACION    = 'vx_token_confirmacion';
    const TOKEN_EXPIRA          = 'vx_token_expira';
    const TOKEN_APROBAR         = 'vx_token_aprobar';
    const TOKEN_RECHAZAR        = 'vx_token_rechazar';
    const ONBOARDING_COMPLETO   = 'vx_onboarding_completo';
    const ONBOARDING_PASO       = 'vx_onboarding_paso';

    // Membresía — plan de facturación (independiente del badge de fundador)
    const PLAN                  = 'vx_plan';           // gratuito | mensual | anual | preferencial
    const PLAN_ESTADO           = 'vx_plan_estado';    // activo | vencido
    const PLAN_INICIO           = 'vx_plan_inicio';
    const PLAN_VENCIMIENTO      = 'vx_plan_vencimiento'; // timestamp; 0 = sin vencimiento
    const PRECIO_PREFERENTE     = 'vx_precio_preferente'; // bool: tiene precio especial por ser fundador
    const GATEWAY_CUSTOMER_ID   = 'vx_gateway_customer_id';
    const GATEWAY_SUBSCRIPTION  = 'vx_gateway_subscription_id';

    // Badge de Fundador — PERMANENTE, independiente del plan de facturación
    // Se asigna al completar onboarding durante la fase beta y nunca se quita automáticamente.
    const ES_FUNDADOR = 'vx_es_fundador';

    // Comunidades
    const COMUNIDAD_OUT2B    = 'vx_comunidad_out2b';
    const COMUNIDAD_WOMAN    = 'vx_comunidad_woman';
    const COMUNIDAD_SENIOR   = 'vx_comunidad_senior';
    const SENIOR_SOLICITADO  = 'vx_senior_solicitado';
    const SENIOR_VERIFICADO  = 'vx_senior_verificado';

    // Tags offer / seek
    const OFFER_TAGS  = 'vx_offer_tags';
    const SEEK_TAGS   = 'vx_seek_tags';
    const OFFER_TEXTO = 'vx_offer_texto';
    const SEEK_TEXTO  = 'vx_seek_texto';

    // Género (masculino | femenino | otro | no_contesta)
    const GENERO = 'vx_genero';

    // Tags de perfil independientes (aparecen bajo el nombre en la ficha pública)
    const PROFILE_TAGS = 'vx_profile_tags';

    // Industria principal (sincronizada desde la empresa activa, usada para filtros)
    const INDUSTRIA = 'vx_industria';

    // 4Dinner
    const DINNERS_ASIGNADO   = 'vx_dinners_asignado';
    const DINNERS_INTERESADO = 'vx_dinners_interesado';

    // Favoritos (array de user_ids)
    const FAVORITOS = 'vx_favoritos';

    // Visitas al perfil (contador)
    const VISITAS_PERFIL = 'vx_visitas_perfil';

    /**
     * Registra todos los meta keys con register_meta().
     * Se llama en el hook 'init'.
     */
    public static function register(): void
    {
        $string_keys = [
            self::NOMBRE, self::APELLIDO, self::PERFIL_SLUG, self::BIO,
            self::CIUDAD, self::PAIS, self::CONTACTO_PREFERIDO, self::TELEFONO,
            self::LINKEDIN, self::ESTADO, self::TIPO_VERIFICACION,
            self::TOKEN_CONFIRMACION, self::PLAN, self::PLAN_ESTADO,
            self::OFFER_TEXTO, self::SEEK_TEXTO, self::INDUSTRIA, self::GENERO,
        ];

        foreach ( $string_keys as $key ) {
            register_meta( 'user', $key, [
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => false,
            ] );
        }

        $int_keys = [
            self::FOTO, self::TOKEN_EXPIRA, self::PLAN_INICIO,
            self::PLAN_VENCIMIENTO, self::ONBOARDING_PASO, self::VISITAS_PERFIL,
        ];

        foreach ( $int_keys as $key ) {
            register_meta( 'user', $key, [
                'type'         => 'integer',
                'single'       => true,
                'show_in_rest' => false,
            ] );
        }

        $bool_keys = [
            self::ONBOARDING_COMPLETO, self::PRECIO_PREFERENTE, self::ES_FUNDADOR,
            self::COMUNIDAD_OUT2B, self::COMUNIDAD_WOMAN, self::COMUNIDAD_SENIOR,
            self::SENIOR_SOLICITADO, self::SENIOR_VERIFICADO,
        ];

        foreach ( $bool_keys as $key ) {
            register_meta( 'user', $key, [
                'type'         => 'boolean',
                'single'       => true,
                'show_in_rest' => false,
            ] );
        }

        $array_keys = [
            self::OFFER_TAGS, self::SEEK_TAGS, self::PROFILE_TAGS,
            self::DINNERS_ASIGNADO, self::DINNERS_INTERESADO, self::FAVORITOS,
        ];

        foreach ( $array_keys as $key ) {
            register_meta( 'user', $key, [
                'type'         => 'array',
                'single'       => true,
                'show_in_rest' => false,
            ] );
        }
    }
}
