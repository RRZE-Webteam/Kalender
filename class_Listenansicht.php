<?php

require_once 'class_Ansicht.php';

class Listenansicht extends Ansicht {

	// Gibt den Dateiname des Templates zurueck.
	public function template_name() {
		return "listenansicht.html";
	}

	// Funktion soll fuer Tagesansicht und Datum die anzuzeigenden Tage als Array zurueckgeben.
	public function lade_tage($datum = '') {
		// Soll alle Termine laden.
		return -1;
	}

	// Rendert das Template mit den uebergebenen Daten.
	// Falls noetig werden die Daten noch formatiert.
	public function rendere_daten($daten = '') {
		return $this->rendere_template($daten);
	}

	public function datum_vor($datum = '') {
		return NULL;
	}

	public function datum_zurueck($datum = '') {
		return NuLL;
	}

}
