<?php
$string['pluginname']=$string['intuitel']='Tutor Inteligente';
$string['error_not_in_course']='El bloque Tutor Inteligente debería funcionar dentro de un curso.';
$string['welcome']='Tutoría personalizada ofrecida por  <a href="http://eduvalab.uva.es/en/projects/intuitel-intelligent-tutoring-interface-technology-enhanced-learning">INTUITEL</a>.';
$string['intuitel:myaddinstance']=$string['block/intuitel:myaddinstance']= 'Add INTUITEL block to My Site';
$string['intuitel:addinstance']=$string['block/intuitel:addinstance']= 'Añadir el bloque a un curso habilitándolo para INTUITEL';
$string['intuitel:externallyedit']=$string['block/intuitel:externallyedit']='Permitir autenticar este usuario al Editor SLOM de Intuitel externo';
$string['intuitel:myaddinstance'] = 'Add INTUITEL block to My Home';
$string['intuitel:view']=$string['block/intuitel:view']= 'Ver mensajes TUG y LORE e interactuar con ellos.';
$string['allowed_intuitel_ips'] = 'Lista de IPs a las que se permite enviar eventos INTUITEL a este LMS.';
$string['config_allowed_intuitel_ips'] = 'A todas las direcciones aquí incluidas se les permite enviar solicitudes de información de usuarios y contenidos de este LMS. Se debe incluir una entrada por cada línea. Una entrada con \'*\' habilita a todas las direcciones el acceso (no usar en servidores de producción).';
$string['intuitel_servicepoint_urls'] = 'Punto de servicio URL para usar los servicios INTUITEL.';
$string['config_intuitel_servicepoint_urls'] = 'URL Base para el punto de servicio REST INTUITEL.';
$string['config_intuitel_intuitel_LMS_id'] = 'Identificación de esta plataforma Moodle en la red INTUITEL. Todo el contenido y los usuarios se identifican usando este valor. Este valor no debe ser modificado después de comenzar la interacción con INTUITEL.';
$string['intuitel_intuitel_LMS_id'] = 'Prefijo de identificación para esta instancia de Moodle.';
$string['intuitel_debug_server'] = 'Ignora el servidor INTUITEL y emplea simulación';
$string['config_intuitel_debug_server'] = 'Con la finalidad de depurar, usa una simulación de servidores INTUITEL en vez de uno real.';
$string['intuitel_report_from_logevent'] = 'Experimental: Reporta a INTUITEL todos los eventos desde el último reporte.';
$string['config_intuitel_report_from_logevent'] = 'Experimental: Tomar la lista de eventos desde el último log en vez de reportar sólo un evento. Podría ser útil si algunos eventos no han sido notificados.';
$string['intuitel_no_javascript_strategy'] = 'Experimental: Si Javascript no está disponible. Implementa el bloque como un iFrame o como una inclusión inline.';
$string['config_intuitel_no_javascript_strategy'] = 'La inserción de un iFrame podría causar defectos estéticos. La inclusión Inline supondrá una penalización en la velocidad de carga de cada página incluso cuando JavaScript esté disponible porque el contenido se construye siempre para los potenciales más limitados navegadores.';
$string['intuitel_allow_geolocation'] = 'Permitir geolocalización de los usuarios.';
$string['config_intuitel_allow_geolocation'] = 'INTUITEL empleará la posición del usuario para realizar mejores recomendaciones de contenido.';

$string['dismiss'] = 'Cerrar';
$string['personalized_recommendations'] = 'Tus recomendaciones personalizadas:';
$string['page_not_monitored'] = 'Esta página no se monitoriza por INTUITEL';

$string['submit'] = 'Enviar';
$string['advice_duration'] = 'Parece que {$a->duration} segundos es poco tiempo para el contenido {$a->loId}.
	¿No crees que debes repasarlo un poco más?';
$string['advice_grade'] = 'Parece que no has obtenido un gran resultado en la actividad {$a->loId} ({$a->grade} de {$a->grademax}). Conviene que repases los contenidos anteriores y repitas la prueba.';
$string['advice_revisits'] = 'Ya has visitado {$a->count} veces el contenido {$a->loId}. Si tienes problemas en entenderlo contacta con tu profesor. Él te ayudará.';
$string['congratulate_grade'] ='Obtuviste un buen resultado ({$a->grade} de {$a->grademax}) en la actividad {$a->loId}. ¡Bien hecho! ¡sigue así! ';
$string['remember_already_graded'] ='Obtuviste un buen resultado ({$a->grade} de {$a->grademax}) en esta actividad. No es necesario que la repitas.';
$string['advice_outofsequence']='Te recomiendo visitar {$a->previousLoId} antes de {$a->currentLoId} – reconsidera tu elección';

// ERROR strings
$string['protocol_error_intuitel_node_malfunction'] = 'El servidor de INTUITEL situado en {$a->service_point} no responde adecuadamente. Por favor, informe a los adminsitradores. Exception: {$a->message}';