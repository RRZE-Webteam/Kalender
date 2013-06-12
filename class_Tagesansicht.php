<?php
	require_once 'class_Ansicht.php';
	require_once 'platzierung.php';
	
	class Tagesansicht extends Ansicht {
		
		// Gibt den Dateiname des Templates zurueck.
		public function template_name() {
			return "tagesansicht.html";
		}

		// Funktion soll fuer Tagesansicht und Datum die anzuzeigenden Tage als Array zurueckgeben.
		public function lade_tage($datum = '') {
			// Falls kein Tag gewaehlt wurde, waehle heute.
			if(!$datum || $datum === '') $datum = date("Ymd");
			else 			  			 $datum = date("Ymd", strtotime($datum));

			return array($datum);
		}

		// Rendert das Template mit den uebergebenen Daten.
		// Falls noetig werden die Daten noch formatiert.
		public function rendere_daten($daten = '') {
			$datum_aktuell = $daten[0]["datum"];
			$tag = $this->markiere_termine($daten);

			$ansicht_daten = array(
				"datum_aktuell" => $this->datum_aktuell($datum_aktuell),
				"datum_vor" => $this->datum_vor($datum_aktuell),
				"datum_zurueck" => $this->datum_zurueck($datum_aktuell),
				"tag" => $tag
			);
			return $this->rendere_template($ansicht_daten);
		}

		private function markiere_termine($termine = NULL) {
			if(is_null($termine) || !is_array($termine)) return -1;

			$tag = new Tag($termine[0]["termine"]);
			$tag->markiere_termin_spalten();
			$tag->markiere_termin_indizes();
			$termine[0]["termine"] = $tag->neue_termine();
			return $termine;
		}

		public function datum_vor($datum = '') {
			if(!$datum || $datum === '') {
				$datum = date("Ymd");
			}

			return date("Y-m-d", strtotime(date("Y-m-d", strtotime($datum)) . " +1 day"));
		}

		public function datum_zurueck($datum = '') {
			if(!$datum || $datum === '') {
				$datum = date("Ymd");
			}

			return date("Y-m-d", strtotime(date("Y-m-d", strtotime($datum)) . " -1 day"));
		}
	}

?>