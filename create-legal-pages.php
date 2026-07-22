<?php
$privacidad_content = <<<HTML
<h2>1. ¿Quiénes somos?</h2>
<p>Vitrinexo es una plataforma de directorio B2B para empresas de servicios profesionales. Puedes contactarnos en <a href="mailto:hola@vitrinexo.com">hola@vitrinexo.com</a>.</p>

<h2>2. ¿Qué datos recopilamos?</h2>
<p>Al registrarte en Vitrinexo recopilamos los siguientes datos personales:</p>
<ul>
<li>Nombre y apellido</li>
<li>Dirección de correo electrónico</li>
<li>Empresa y cargo</li>
<li>Perfil de LinkedIn</li>
<li>País de residencia</li>
<li>Número de teléfono celular</li>
</ul>
<p>Adicionalmente, recopilamos datos de uso de la plataforma para mejorar la experiencia (páginas visitadas, conexiones realizadas, actividad general).</p>

<h2>3. ¿Para qué usamos tus datos?</h2>
<p>Usamos tu información para:</p>
<ul>
<li>Crear y gestionar tu perfil en la plataforma</li>
<li>Facilitar conexiones entre miembros</li>
<li>Enviarte comunicaciones relacionadas con tu cuenta y la plataforma</li>
<li>Mejorar nuestros servicios</li>
<li>Cumplir con obligaciones legales</li>
</ul>

<h2>4. ¿Con quién compartimos tus datos?</h2>
<p>Tu perfil (nombre, empresa, cargo, industria) es visible para otros miembros verificados de Vitrinexo. No vendemos ni cedemos tus datos personales a terceros con fines comerciales.</p>
<p>Podemos compartir datos con proveedores de servicios tecnológicos que nos ayudan a operar la plataforma (servidores, emails transaccionales), siempre bajo acuerdos de confidencialidad.</p>

<h2>5. ¿Cuánto tiempo guardamos tus datos?</h2>
<p>Mantenemos tus datos mientras tu cuenta esté activa. Si solicitas la eliminación de tu cuenta, borraremos tus datos personales en un plazo de 30 días, salvo que la ley nos exija conservarlos por más tiempo.</p>

<h2>6. Tus derechos</h2>
<p>Como titular de datos tienes derecho a:</p>
<ul>
<li>Acceder a los datos que tenemos sobre ti</li>
<li>Corregir datos inexactos</li>
<li>Solicitar la eliminación de tus datos</li>
<li>Portabilidad de tus datos</li>
<li>Oponerte al uso de tus datos para fines de marketing</li>
</ul>
<p>Para ejercer cualquiera de estos derechos escríbenos a <a href="mailto:hola@vitrinexo.com">hola@vitrinexo.com</a>.</p>

<h2>7. Cookies</h2>
<p>Vitrinexo utiliza cookies esenciales para el funcionamiento de la plataforma (sesión, preferencias). No utilizamos cookies de seguimiento publicitario.</p>

<h2>8. Modificaciones</h2>
<p>Podemos actualizar esta política periódicamente. Te notificaremos por email ante cambios significativos.</p>
HTML;

$terminos_content = <<<HTML
<h2>1. Aceptación</h2>
<p>Al registrarte y utilizar Vitrinexo aceptas estos Términos y Condiciones. Si no estás de acuerdo, no debes usar la plataforma.</p>

<h2>2. La plataforma</h2>
<p>Vitrinexo es un directorio B2B de empresas de servicios profesionales. La plataforma facilita la visibilidad y las conexiones entre empresas, pero no garantiza resultados comerciales ni es responsable de las relaciones que se establezcan entre miembros.</p>

<h2>3. Elegibilidad</h2>
<p>Para registrarte debes:</p>
<ul>
<li>Ser mayor de 18 años</li>
<li>Representar a una empresa o ejercer actividad profesional</li>
<li>Proporcionar información verdadera y actualizada</li>
</ul>
<p>Vitrinexo se reserva el derecho de rechazar o cancelar cuentas que no cumplan estos requisitos o que violen estos términos.</p>

<h2>4. Uso aceptable</h2>
<p>Al usar Vitrinexo te comprometes a:</p>
<ul>
<li>No publicar contenido falso, engañoso o fraudulento</li>
<li>No utilizar la plataforma para spam o comunicaciones no solicitadas</li>
<li>No infringir derechos de propiedad intelectual de terceros</li>
<li>No intentar acceder sin autorización a cuentas o sistemas</li>
<li>Respetar a los demás miembros</li>
</ul>

<h2>5. Membresía y pagos</h2>
<p>Los primeros 100 Miembros Pioneros acceden a 3 meses gratuitos desde el lanzamiento de la plataforma. Transcurrido ese período, el acceso continuado podrá requerir el pago de una suscripción. Los precios y condiciones serán comunicados con anticipación.</p>

<h2>6. Propiedad intelectual</h2>
<p>El contenido, diseño y marca de Vitrinexo son propiedad exclusiva de Vitrinexo SpA. Los miembros conservan los derechos sobre el contenido que publican en la plataforma.</p>

<h2>7. Limitación de responsabilidad</h2>
<p>Vitrinexo se proporciona "tal como está". No somos responsables de daños directos o indirectos derivados del uso de la plataforma, incluyendo pérdida de negocios o datos.</p>

<h2>8. Modificaciones</h2>
<p>Podemos modificar estos términos con previo aviso de 15 días. El uso continuado de la plataforma implica la aceptación de los nuevos términos.</p>

<h2>9. Legislación aplicable</h2>
<p>Estos términos se rigen por las leyes de Chile. Cualquier disputa se someterá a los tribunales ordinarios de Santiago de Chile.</p>

<h2>10. Contacto</h2>
<p>Para cualquier consulta sobre estos términos escríbenos a <a href="mailto:hola@vitrinexo.com">hola@vitrinexo.com</a>.</p>
HTML;

foreach ([
    ['privacidad', 'Política de Privacidad', $privacidad_content],
    ['terminos',   'Términos y Condiciones', $terminos_content],
] as [$slug, $title, $content]) {
    $page = get_page_by_path($slug);
    if ($page) {
        wp_update_post(['ID' => $page->ID, 'post_content' => $content, 'post_title' => $title, 'post_status' => 'publish']);
        echo "Updated: $slug\n";
    } else {
        $id = wp_insert_post(['post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'page']);
        echo "Created: $slug (ID $id)\n";
    }
}
