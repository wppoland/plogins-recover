=== Plogins Recover - Abandoned Cart for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, abandoned cart, cart recovery, email, ecommerce
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Recupera carritos abandonados de WooCommerce: captura el correo electrónico pronto, guarda el carrito y envía un enlace seguro de un solo clic para terminar la compra.

== Description ==

Recover captura los carritos de WooCommerce que los clientes dejan atrás y les envía un enlace seguro de un solo clic que devuelve cada artículo directamente a su carrito para que puedan terminar la compra. Funciona por completo en tu propio sitio: sin servicios de terceros, ningún dato sale de tu tienda.

Como todo ocurre en tu propio servidor, puedes leer exactamente qué hace. El código fuente completo está en https://github.com/wppoland/plogins-recover, que también es el lugar para informar de un error o solicitar una función.

<strong>Cómo funciona</strong>

1. En cuanto un cliente tiene artículos en el carrito, Recover guarda una instantánea privada de ese carrito.
2. El correo electrónico del cliente se captura pronto, automáticamente para clientes registrados y (con consentimiento) desde el campo de correo electrónico de la compra para invitados.
3. Si la compra no se completa dentro de una ventana que tú elijas, el carrito se marca como <strong>abandonado</strong>.
4. En la siguiente ejecución programada, Recover envía un mensaje de recuperación con un enlace de restauración seguro y tokenizado.
5. Un clic en ese enlace vuelve a llenar el carrito y devuelve al cliente a la compra. Los carritos recuperados se registran por separado para que puedas ver tu tasa de recuperación.

<strong>Algunas cosas que conviene saber</strong>

Los correos electrónicos se envían a través del propio correo de WordPress (`wp_mail`), y los datos del carrito viven en una sola tabla personalizada (`{prefix}_recover_carts`) en tu base de datos. No se envía nada a ningún servicio externo.

La captura del correo electrónico de invitados solo ocurre después de que el cliente marque una casilla de consentimiento, y puedes editar el texto o desactivar el requisito. Los enlaces de restauración llevan un token aleatorio de 64 caracteres imposible de adivinar y nada más: sin id de cliente, sin correo electrónico en la URL. Desde la pantalla de carritos puedes borrar con un clic todos los carritos guardados para una sola dirección de correo electrónico.

En la implementación, toda la salida se escapa y toda la entrada se sanea, cada formulario de administración y petición AJAX se verifica con nonce, y las páginas de administración requieren la capacidad `manage_woocommerce`. La captura temprana del correo electrónico usa un pequeño fragmento de JavaScript puro (sin jQuery) cargado en el pie de página; el proceso de recuperación se ejecuta en el cron de WordPress y es idempotente, así que una nueva ejecución nunca envía un segundo correo electrónico para el mismo carrito. Al borrar el plugin se elimina su tabla, se quitan sus dos opciones y se limpia la tarea programada.

<strong>Funciones</strong>

* Instantáneas automáticas del carrito cada vez que cambia
* Captura temprana del correo electrónico para clientes registrados e invitados (con consentimiento)
* Ventana de abandono y retraso del correo electrónico configurables
* Enlace de restauración seguro y tokenizado de un solo clic que vuelve a llenar el carrito
* Correo electrónico de recuperación enviado según un horario de cron de WordPress mediante `wp_mail`
* Lista de carritos abandonados / recuperados / pendientes con un resumen de la tasa de recuperación
* Asunto, encabezado, cuerpo y texto del botón del correo electrónico personalizables
* Casilla de consentimiento compatible con el RGPD y borrado de datos por correo electrónico con un clic
* Compatible con HPOS de WooCommerce (Custom Order Tables) y bloques de carrito/pago

== Installation ==

1. Instala y activa WooCommerce (8.0 o posterior).
2. Instala Recover desde el directorio de plugins de WordPress o sube la carpeta `recover` a `/wp-content/plugins/`.
3. Activa el plugin en la pantalla <strong>Plugins</strong>.
4. Entra en <strong>WooCommerce → Recover</strong> para configurar los tiempos y personalizar el correo electrónico; los valores predeterminados sensatos funcionan de inmediato.
5. Los carritos abandonados y tu tasa de recuperación aparecen en <strong>WooCommerce → Recover Carts</strong>.

== Frequently Asked Questions ==

= Documentation and links =

* <strong>Documentación</strong> - https://plogins.com/es/plogins-recover/docs/
* <strong>Página del plugin</strong> - https://plogins.com/es/plogins-recover/
* <strong>Código fuente</strong> - https://github.com/wppoland/plogins-recover
* <strong>Informes de errores y peticiones de funciones</strong> - https://github.com/wppoland/plogins-recover/issues


= Is Recover free? =
Sí. Recover es gratuito y tiene licencia GPL.

= Does Recover require WooCommerce? =
Sí. Recover es una extensión de WooCommerce y requiere WooCommerce 8.0 o posterior. Muestra un aviso de administración y permanece inactivo si falta WooCommerce o está desactualizado.

= How is the recovery email sent? =
Según un horario de cron de WordPress (por defecto, cada hora). Cada ejecución marca como abandonados los carritos inactivos más allá de tu ventana y luego envía un enlace de recuperación a cada carrito abandonado que corresponda, usando el correo de tu propio sitio (`wp_mail`). El proceso es idempotente, así que nunca envía duplicados: cada carrito recibe un solo correo electrónico de recuperación.

= Is the restore link safe? =
Sí. Cada carrito tiene un token aleatorio criptográfico de 64 caracteres. El enlace de restauración contiene solo ese token: sin id de cliente, sin correo electrónico, nada personal. Sin el token exacto no se puede restaurar un carrito, así que no hay riesgo de enumeración ni IDOR.

= Does this comply with GDPR / consent requirements? =
La captura del correo electrónico de invitados solo ocurre después de que el cliente marque una casilla de consentimiento (puedes editar el texto, y el consentimiento puede ser obligatorio o no). Los datos del carrito se almacenan solo en tu propia base de datos y nunca se envían a terceros. Desde <strong>WooCommerce → Recover Carts</strong> puedes borrar con un clic todos los datos de carrito guardados para cualquier dirección de correo electrónico. Sigues siendo responsable de la política de privacidad de tu tienda.

= Where is cart data stored? =
En una tabla personalizada `{prefix}_recover_carts` en tu base de datos de WordPress. No se envía nada a ningún otro sitio.

= How do I remove all plugin data? =
Al borrar el plugin desde la pantalla <strong>Plugins</strong> se ejecuta la rutina de desinstalación, que elimina la tabla `{prefix}_recover_carts`, quita las opciones `recover_settings` y `recover_db_version` y limpia la tarea programada de recuperación.


= Does this plugin work on WordPress Multisite? =

Sí. Este plugin es compatible con WordPress Multisite. Actívalo en toda la red o en sitios concretos; cada sitio conserva sus propios ajustes y datos.

== External Services ==

Recover no se conecta a ningún servicio externo. Los correos electrónicos de recuperación se envían a través del correo de WordPress de tu propio sitio (`wp_mail`) y todos los datos del carrito permanecen en tu base de datos de WordPress.

== Screenshots ==

1. Lista de carritos abandonados con recuentos pendientes/abandonados/recuperados y tasa de recuperación.
2. El correo electrónico de recuperación con su botón de un solo clic «Completar mi pedido».

== Translations ==

Plogins Recover incluye traducciones al polaco, al alemán y al español para la interfaz del plugin. El dominio de texto es `plogins-recover`, por lo que los paquetes de idioma de WordPress.org también pueden sobrescribir o ampliar estas traducciones incluidas.

== Changelog ==

= 1.0.2 =
* Se añadieron traducciones incluidas al polaco, al alemán y al español para la interfaz del plugin.

= 1.0.1 =
* Primera versión estable.

= 0.1.3 =
* Renombrado a Plogins Recover para WooCommerce para conseguir un nombre de plugin más distintivo.

= 0.1.2 =
* Acción `recover/email_sent` después de que wp_mail acepte un correo electrónico de recuperación.
* Acción `recover/cart_recovered` cuando un carrito se marca como recuperado.
* `CartRepository::findById()` para búsqueda de carrito por clave principal.

= 0.1.1 =
* Secuencias de recuperación con varios correos electrónicos: `recover/max_emails`, `recover/email_step_delay`,
  `recover/email/template_args` y un tercer argumento `$step` en `recover/email/args`.
* El proceso cron incrementa `emails_sent` y programa seguimientos desde `last_email_at`.

= 0.1.0 =
* Versión inicial.
