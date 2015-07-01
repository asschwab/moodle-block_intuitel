<?php
$string['pluginname']=$string['intuitel']='Automatischer Tutor';
$string['error_not_in_course']='Der Intuitel Block muss in einem Kurs platziert werden.';
$string['welcome']='<a href="http://www.intuitel.eu/">INTUITEL</a>.';
$string['intuitel:myaddinstance']=$string['block/intuitel:myaddinstance']= 'INTUITEL Block zu diesem Moodle hinzufügen.';
$string['intuitel:addinstance']=$string['block/intuitel:addinstance']= 'Fügen Sie den Block einem Kurs hinzu, um den INTUITEL-Tutor zu aktivieren.';
$string['intuitel:externallyedit']=$string['block/intuitel:externallyedit']='Diese Option erlaubt es dem externen Intuitel SLOM Editor, sich mit dieser Kennung anzumelden.';
$string['intuitel:myaddinstance'] = 'Fügt den INTUITEL Block der persönlichen Startseite hinzu.';
$string['intuitel:view']=$string['block/intuitel:view']= 'Zeige und verwende TUG- und LORE-Nachrichten.';
$string['allowed_intuitel_ips'] = 'Von diesen IP-Adressen dürfen INTUITEL Ereignisse zu diesem Moodle gesendet werden.';
$string['config_allowed_intuitel_ips'] = 'Alle Adressen, die hier eingegeben werden, dürfen Anfragen nach BenutzerInnen und Inhalten an dieses Moodle senden. Es ist ein Eintrag pro Zeile möglich. Der Eintrag \'*\' erlaubt jeder IP-Adresse Zugrifff auf diesen Server. Benutzen Sie die Einstellung \'*\' nicht in Produktionsumgebungen.';
$string['intuitel_servicepoint_urls'] = 'Service Point URL des INTUITEL Servers:';
$string['config_intuitel_servicepoint_urls'] = 'Basis URL für den INTUITEL REST Service.';
$string['config_intuitel_intuitel_LMS_id'] = 'Identifikation dieser Moodleinstallation im INTUITEL Netzwerk. Alle Inhalte und BenutzerInnen werden mit diesem Wert verbunden. Dieser Wert sollte nach dem Start der Verbindung mit dem INTUITEL-System nicht geändert werden.';
$string['intuitel_intuitel_LMS_id'] = 'Identifikation dieser Moodleinstanz:';
$string['intuitel_debug_server'] = 'INTUITEL Server ignorieren und Simulation verwenden.';
$string['config_intuitel_debug_server'] = 'Diese Option kann nur für die Fehlersuche verwendet werden. Es wird eine INTUITEL Simulation statt des eines INTUITEL Servers verwendet, wenn diese Option aktiviert wird.';
$string['intuitel_report_from_logevent'] = 'Diese Option ist experimentell. Wenn die Option aktivier ist, werden alle Ereignisse seit dem letzten Bericht an das INTUITEL-System übermittelt.';
$string['config_intuitel_report_from_logevent'] = 'Diese Option ist experimentell. Wenn die Option aktiviert ist, wird die Liste der Ereignisse aus der Ereignislogdatei statt einzelner Ereignisse verwendet. Diese Option ist sinnvoll, wenn Ereigniss nicht korrekt erkannt werden.';
$string['intuitel_no_javascript_strategy'] = 'Diese Option ist experimentell. Wenn Javascript nicht aktiviert ist, wird der INTUITEL-Block als iFrame oder als Inline Inclussion angezeigt.';
$string['config_intuitel_no_javascript_strategy'] = 'IFrames können ästhetische Probleme verursachen. Eine Inline inclussion wird die Ladegeschwindigkeit jeder Seite reduzieren, weil der Inhalt immer auch für Browser von Menschen mit Beeinträchtigungen aufbereitet wird.';
$string['intuitel_debug_level'] = 'Nur für Entwicklungszwecke: Alle Systemereignisse werden protokolliert (Debug Level Log).';
$string['config_intuitel_debug_level'] = 'Nur Ereignisse, die wichtiger sind als die eingestellte Stufe, werden festgehalten.';

$string['intuitel_debug_file'] = 'Nur für Entwicklungszwecke: Log Datei.';
$string['config_intuitel_debug_file'] = 'Das Verzeichnid und die Datei müssen für den Webserver beschreibbar sein.';

$string['intuitel_allow_geolocation'] = 'Erlaube die räumliche Lokalisierung der BenutzerInnen.';
$string['config_intuitel_allow_geolocation'] = 'Wenn diese Option aktiviert ist, wird INTUITEL den Ort, an dem sich die BenutzerInnen befinden, für die Empfehlungen verwenden.';

$string['dismiss'] = 'Schließen';
$string['personalized_recommendations'] = 'Ihre Empfehlungen:';
$string['page_not_monitored'] = 'Diese Seite wurde nicht in das INTUITEL integriert.';
$string['submit'] = 'OK';
$string['advice_duration'] = '{$a->duration} Sekunden könnten zu wenig sein, um {$a->loId} zu lernen.
	Möchten Sie den Inhalte noch einmal ansehen?';
$string['advice_grade'] = 'Sie haben in {$a->loId} {$a->grade} von {$a->grademax} Punkten erreicht. Möchten Sie den Inhalt noch einmal ansehen und den Test wiederholen?';
$string['advice_revisits'] = 'Sie haben die Seite {$a->loId} bereits {$a->count} mal angesehen. Vielleicht ist es sinnvoll, den Inhalt mit anderen Lernende oder Lehrenden zu diskutieren?';
$string['congratulate_grade'] ='Sie haben {$a->grade} von {$a->grademax} Punkten in {$a->loId} erreicht. Ein ausgezeichnetes Ergebnis!';
$string['remember_already_graded'] ='Sie haben {$a->grade} von {$a->grademax} Punkten erreicht. Ein gute Ergebnis - weiter so!';
$string['advice_outofsequence']='Sie sollten sich {$a->previousLoId} ansehen, bevor Sie sich mit {$a->currentLoId} beschäftigen.';

// ERROR strings
$string['protocol_error_intuitel_node_malfunction'] = 'Der INTUITEL Server {$a->service_point} antwortet nicht. Bitte informieren Sie einen Systembetreuer. Der Fehler ist: {$a->message}';
