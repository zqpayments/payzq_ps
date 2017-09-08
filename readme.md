
#PayZQ for PrestaShop by ZQ Payments#
======================

PayZQ payment gateway for PrestaShop extension

Before Start
============

Asegúrate de poseer las credenciales para el uso de nuestra API. Para ello, deberás darte de alta en nuestra web y obtener el token para empezar a usarlo. Para mayor información revisa la documentación desde nuestra web.


Requerimientos
==============
- Certificado SSL
- PHP >= 5.5
- PrestaShop >= 1.6

En caso de que el servidor no cumpla con los requerimientos comunicate con tu proveedor de servicios


Install
=======
- Descarga y copia la carpeta ``payzq_pf`` dentro de la carpeta ``module`` de PrestaShop
- Ingresa en la opción de modulos instalados, localiza el de PayZQ y presiona sobre **Configurar**
- Ingresa en la opción **Congigurar API** e ingresa el Token que usarás para realizar las transacciones. Nota que puedes colocar dos Tokens: uno para el modo _test_ y otro para el modo _live_. Por último, ingresa la clave que se usará para el cifrado en caso de requerirlo.


Reembolsos
==========
 Desde la opción **Reembolsos** podrás realizar devoluciones de transacciones que ya se hayan liquidado. Podrás realizar el reembolso parcial o completo de la transacción. En ningún caso podrás realizar el reembolso de un monto superior. Podrás realizar varios reembolsos para una misma transacción siempre y cuando la suma de los reembolsos no supere al monto liquidado inicialmente.

 Traducciones
 ============
 Podrás generar las traducciones a todos los literales desde la opción de traducciones de PrestaShop
