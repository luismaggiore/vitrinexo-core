<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Lista completa de códigos telefónicos de país (prefijos) con bandera y nombre.
 * Se usa en los selectores de prefijo del registro, onboarding y editor de perfil.
 *
 * @return array<int,array{iso:string,name:string,dial:string,flag:string}>
 */
function vx_get_dial_codes(): array {
    // [ ISO-2, Nombre (ES), Código de marcado ]
    $raw = [
        [ 'AF', 'Afganistán', '+93' ], [ 'AL', 'Albania', '+355' ], [ 'DE', 'Alemania', '+49' ],
        [ 'AD', 'Andorra', '+376' ], [ 'AO', 'Angola', '+244' ], [ 'AI', 'Anguila', '+1264' ],
        [ 'AG', 'Antigua y Barbuda', '+1268' ], [ 'SA', 'Arabia Saudita', '+966' ], [ 'DZ', 'Argelia', '+213' ],
        [ 'AR', 'Argentina', '+54' ], [ 'AM', 'Armenia', '+374' ], [ 'AW', 'Aruba', '+297' ],
        [ 'AU', 'Australia', '+61' ], [ 'AT', 'Austria', '+43' ], [ 'AZ', 'Azerbaiyán', '+994' ],
        [ 'BS', 'Bahamas', '+1242' ], [ 'BD', 'Bangladés', '+880' ], [ 'BB', 'Barbados', '+1246' ],
        [ 'BH', 'Baréin', '+973' ], [ 'BE', 'Bélgica', '+32' ], [ 'BZ', 'Belice', '+501' ],
        [ 'BJ', 'Benín', '+229' ], [ 'BM', 'Bermudas', '+1441' ], [ 'BY', 'Bielorrusia', '+375' ],
        [ 'BO', 'Bolivia', '+591' ], [ 'BA', 'Bosnia y Herzegovina', '+387' ], [ 'BW', 'Botsuana', '+267' ],
        [ 'BR', 'Brasil', '+55' ], [ 'BN', 'Brunéi', '+673' ], [ 'BG', 'Bulgaria', '+359' ],
        [ 'BF', 'Burkina Faso', '+226' ], [ 'BI', 'Burundi', '+257' ], [ 'BT', 'Bután', '+975' ],
        [ 'CV', 'Cabo Verde', '+238' ], [ 'KH', 'Camboya', '+855' ], [ 'CM', 'Camerún', '+237' ],
        [ 'CA', 'Canadá', '+1' ], [ 'QA', 'Catar', '+974' ], [ 'TD', 'Chad', '+235' ],
        [ 'CL', 'Chile', '+56' ], [ 'CN', 'China', '+86' ], [ 'CY', 'Chipre', '+357' ],
        [ 'CO', 'Colombia', '+57' ], [ 'KM', 'Comoras', '+269' ], [ 'CG', 'Congo', '+242' ],
        [ 'CD', 'Congo (RD)', '+243' ], [ 'KP', 'Corea del Norte', '+850' ], [ 'KR', 'Corea del Sur', '+82' ],
        [ 'CI', 'Costa de Marfil', '+225' ], [ 'CR', 'Costa Rica', '+506' ], [ 'HR', 'Croacia', '+385' ],
        [ 'CU', 'Cuba', '+53' ], [ 'CW', 'Curazao', '+599' ], [ 'DK', 'Dinamarca', '+45' ],
        [ 'DM', 'Dominica', '+1767' ], [ 'EC', 'Ecuador', '+593' ], [ 'EG', 'Egipto', '+20' ],
        [ 'SV', 'El Salvador', '+503' ], [ 'AE', 'Emiratos Árabes Unidos', '+971' ], [ 'ER', 'Eritrea', '+291' ],
        [ 'SK', 'Eslovaquia', '+421' ], [ 'SI', 'Eslovenia', '+386' ], [ 'ES', 'España', '+34' ],
        [ 'US', 'Estados Unidos', '+1' ], [ 'EE', 'Estonia', '+372' ], [ 'ET', 'Etiopía', '+251' ],
        [ 'PH', 'Filipinas', '+63' ], [ 'FI', 'Finlandia', '+358' ], [ 'FJ', 'Fiyi', '+679' ],
        [ 'FR', 'Francia', '+33' ], [ 'GA', 'Gabón', '+241' ], [ 'GM', 'Gambia', '+220' ],
        [ 'GE', 'Georgia', '+995' ], [ 'GH', 'Ghana', '+233' ], [ 'GI', 'Gibraltar', '+350' ],
        [ 'GD', 'Granada', '+1473' ], [ 'GR', 'Grecia', '+30' ], [ 'GL', 'Groenlandia', '+299' ],
        [ 'GP', 'Guadalupe', '+590' ], [ 'GU', 'Guam', '+1671' ], [ 'GT', 'Guatemala', '+502' ],
        [ 'GF', 'Guayana Francesa', '+594' ], [ 'GN', 'Guinea', '+224' ], [ 'GQ', 'Guinea Ecuatorial', '+240' ],
        [ 'GW', 'Guinea-Bisáu', '+245' ], [ 'GY', 'Guyana', '+592' ], [ 'HT', 'Haití', '+509' ],
        [ 'HN', 'Honduras', '+504' ], [ 'HK', 'Hong Kong', '+852' ], [ 'HU', 'Hungría', '+36' ],
        [ 'IN', 'India', '+91' ], [ 'ID', 'Indonesia', '+62' ], [ 'IQ', 'Irak', '+964' ],
        [ 'IR', 'Irán', '+98' ], [ 'IE', 'Irlanda', '+353' ], [ 'IS', 'Islandia', '+354' ],
        [ 'KY', 'Islas Caimán', '+1345' ], [ 'IL', 'Israel', '+972' ], [ 'IT', 'Italia', '+39' ],
        [ 'JM', 'Jamaica', '+1876' ], [ 'JP', 'Japón', '+81' ], [ 'JO', 'Jordania', '+962' ],
        [ 'KZ', 'Kazajistán', '+7' ], [ 'KE', 'Kenia', '+254' ], [ 'KG', 'Kirguistán', '+996' ],
        [ 'KI', 'Kiribati', '+686' ], [ 'KW', 'Kuwait', '+965' ], [ 'LA', 'Laos', '+856' ],
        [ 'LS', 'Lesoto', '+266' ], [ 'LV', 'Letonia', '+371' ], [ 'LB', 'Líbano', '+961' ],
        [ 'LR', 'Liberia', '+231' ], [ 'LY', 'Libia', '+218' ], [ 'LI', 'Liechtenstein', '+423' ],
        [ 'LT', 'Lituania', '+370' ], [ 'LU', 'Luxemburgo', '+352' ], [ 'MO', 'Macao', '+853' ],
        [ 'MK', 'Macedonia del Norte', '+389' ], [ 'MG', 'Madagascar', '+261' ], [ 'MY', 'Malasia', '+60' ],
        [ 'MW', 'Malaui', '+265' ], [ 'MV', 'Maldivas', '+960' ], [ 'ML', 'Malí', '+223' ],
        [ 'MT', 'Malta', '+356' ], [ 'MA', 'Marruecos', '+212' ], [ 'MQ', 'Martinica', '+596' ],
        [ 'MU', 'Mauricio', '+230' ], [ 'MR', 'Mauritania', '+222' ], [ 'MX', 'México', '+52' ],
        [ 'FM', 'Micronesia', '+691' ], [ 'MD', 'Moldavia', '+373' ], [ 'MC', 'Mónaco', '+377' ],
        [ 'MN', 'Mongolia', '+976' ], [ 'ME', 'Montenegro', '+382' ], [ 'MZ', 'Mozambique', '+258' ],
        [ 'MM', 'Myanmar', '+95' ], [ 'NA', 'Namibia', '+264' ], [ 'NR', 'Nauru', '+674' ],
        [ 'NP', 'Nepal', '+977' ], [ 'NI', 'Nicaragua', '+505' ], [ 'NE', 'Níger', '+227' ],
        [ 'NG', 'Nigeria', '+234' ], [ 'NO', 'Noruega', '+47' ], [ 'NC', 'Nueva Caledonia', '+687' ],
        [ 'NZ', 'Nueva Zelanda', '+64' ], [ 'OM', 'Omán', '+968' ], [ 'NL', 'Países Bajos', '+31' ],
        [ 'PK', 'Pakistán', '+92' ], [ 'PW', 'Palaos', '+680' ], [ 'PS', 'Palestina', '+970' ],
        [ 'PA', 'Panamá', '+507' ], [ 'PG', 'Papúa Nueva Guinea', '+675' ], [ 'PY', 'Paraguay', '+595' ],
        [ 'PE', 'Perú', '+51' ], [ 'PF', 'Polinesia Francesa', '+689' ], [ 'PL', 'Polonia', '+48' ],
        [ 'PT', 'Portugal', '+351' ], [ 'PR', 'Puerto Rico', '+1787' ], [ 'GB', 'Reino Unido', '+44' ],
        [ 'CF', 'República Centroafricana', '+236' ], [ 'CZ', 'República Checa', '+420' ],
        [ 'DO', 'República Dominicana', '+1809' ], [ 'RW', 'Ruanda', '+250' ], [ 'RO', 'Rumanía', '+40' ],
        [ 'RU', 'Rusia', '+7' ], [ 'WS', 'Samoa', '+685' ], [ 'KN', 'San Cristóbal y Nieves', '+1869' ],
        [ 'SM', 'San Marino', '+378' ], [ 'VC', 'San Vicente y las Granadinas', '+1784' ],
        [ 'LC', 'Santa Lucía', '+1758' ], [ 'ST', 'Santo Tomé y Príncipe', '+239' ], [ 'SN', 'Senegal', '+221' ],
        [ 'RS', 'Serbia', '+381' ], [ 'SC', 'Seychelles', '+248' ], [ 'SL', 'Sierra Leona', '+232' ],
        [ 'SG', 'Singapur', '+65' ], [ 'SY', 'Siria', '+963' ], [ 'SO', 'Somalia', '+252' ],
        [ 'LK', 'Sri Lanka', '+94' ], [ 'ZA', 'Sudáfrica', '+27' ], [ 'SD', 'Sudán', '+249' ],
        [ 'SS', 'Sudán del Sur', '+211' ], [ 'SE', 'Suecia', '+46' ], [ 'CH', 'Suiza', '+41' ],
        [ 'SR', 'Surinam', '+597' ], [ 'TH', 'Tailandia', '+66' ], [ 'TW', 'Taiwán', '+886' ],
        [ 'TZ', 'Tanzania', '+255' ], [ 'TJ', 'Tayikistán', '+992' ], [ 'TL', 'Timor Oriental', '+670' ],
        [ 'TG', 'Togo', '+228' ], [ 'TO', 'Tonga', '+676' ], [ 'TT', 'Trinidad y Tobago', '+1868' ],
        [ 'TN', 'Túnez', '+216' ], [ 'TM', 'Turkmenistán', '+993' ], [ 'TR', 'Turquía', '+90' ],
        [ 'TV', 'Tuvalu', '+688' ], [ 'UA', 'Ucrania', '+380' ], [ 'UG', 'Uganda', '+256' ],
        [ 'UY', 'Uruguay', '+598' ], [ 'UZ', 'Uzbekistán', '+998' ], [ 'VU', 'Vanuatu', '+678' ],
        [ 'VE', 'Venezuela', '+58' ], [ 'VN', 'Vietnam', '+84' ], [ 'YE', 'Yemen', '+967' ],
        [ 'DJ', 'Yibuti', '+253' ], [ 'ZM', 'Zambia', '+260' ], [ 'ZW', 'Zimbabue', '+263' ],
    ];

    $out = [];
    foreach ( $raw as $c ) {
        $out[] = [
            'iso'  => $c[0],
            'name' => $c[1],
            'dial' => $c[2],
            'flag' => vx_iso_to_flag( $c[0] ),
        ];
    }
    return $out;
}

/** Convierte un código ISO-2 (ej: "CL") en su emoji de bandera. */
function vx_iso_to_flag( string $iso ): string {
    $iso = strtoupper( trim( $iso ) );
    if ( strlen( $iso ) !== 2 ) return '';
    $a = 0x1F1E6 + ( ord( $iso[0] ) - ord( 'A' ) );
    $b = 0x1F1E6 + ( ord( $iso[1] ) - ord( 'A' ) );
    return mb_convert_encoding( '&#' . $a . ';&#' . $b . ';', 'UTF-8', 'HTML-ENTITIES' );
}

/**
 * Renderiza un <select> de prefijos con bandera, nombre y código de marcado.
 * El value de cada opción es el código (ej: "+56").
 *
 * @param string $id        id/name del select
 * @param string $selected  código preseleccionado (ej: "+56")
 */
function vx_dial_code_select( string $id, string $selected = '+56', array $attrs = [] ): string {
    $codes = vx_get_dial_codes();
    $attr_str = '';
    foreach ( $attrs as $k => $v ) {
        $attr_str .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
    }
    $html = '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" class="form-control-vx"' . $attr_str . '>';
    foreach ( $codes as $c ) {
        $label = $c['flag'] . ' ' . $c['name'] . ' (' . $c['dial'] . ')';
        $sel   = ( $c['dial'] === $selected ) ? ' selected' : '';
        // value único por iso+dial para evitar colisiones de códigos repetidos (ej: +1)
        $html .= '<option value="' . esc_attr( $c['dial'] ) . '"' . $sel . '>' . esc_html( $label ) . '</option>';
    }
    $html .= '</select>';
    return $html;
}
