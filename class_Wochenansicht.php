<?php

require_once 'class_Ansicht.php';
require_once 'platzierung.php';

class Wochenansicht extends Ansicht {

	// Gibt den Dateiname des Templates zurueck.
	public function template_name() {
		return "wochenansicht.html";
	}

	// Funktion soll fuer Tagesansicht und Datum die anzuzeigenden Tage als Array zurueckgeben.
	public function lade_tage($datum = '') {
		// Datum kann ein Tag in der aktuellen Woche.
		if (!$datum || $datum === '') {
			$datum = date("Y-m-d");
		}

		$ts = strtotime($datum);

		$jahr = date('o', $ts);
		$woche = date('W', $ts);

		$tage_in_woche = array();

		for ($i = 1; $i <= 7; $i++) {

			$ts = strtotime($jahr . 'W' . $woche . $i);
			$tag = date("Y-m-d", $ts);
			array_push($tage_in_woche, $tag);
		}
		return $tage_in_woche;
	}

	// Rendert das Template mit den uebergebenen Daten.
	// Falls noetig werden die Daten noch formatiert.
	public function rendere_daten($daten = '') {
		$tag = $daten[2];
		$ts = strtotime($tag["datum"]);
		$monat = $this->monate[date("n", $ts) - 1];
		$tage = $this->markiere_termine($daten);

		$stunden = array();
		$stunde_start = date("G", strtotime($tag["tag_anfang"]));
		for ($i = 0; $i < $tag["tag_laenge"] / 60; $i++) {
			$stunden[] = array("stunde" => $stunde_start + $i, "stunde_abstand" => $i * 60);
		}

		$ansicht_daten = array(
			"monat" => $monat,
			"datum_aktuell" => $this->datum_aktuell($tag["datum"]),
			"datum_vor" => $this->datum_vor($tag["datum"]),
			"datum_zurueck" => $this->datum_zurueck($tag["datum"]),
			"tage" => $tage,
			"stunden" => $stunden
		);
		return $this->rendere_template($ansicht_daten);
	}

	private function markiere_termine($tage = NULL) {
		if (is_null($tage) || !is_array($tage))
			return -1;

		for ($t = 0; $t < count($tage); $t++) {
			$tag = new Tag($tage[$t]["termine"]);
			$tag->markiere_termin_spalten();
			$tag->markiere_termin_indizes();
			$tage[$t]["termine"] = $tag->neue_termine();
		}

		return $tage;
	}

	private function filtere_termine_fuer_minute($termine = '', $minute = 0) {
		if (!is_array($termine))
			return -1;

		$gefundene_termine = array(2);

		for ($a = 0; $a < count($termine); $a++) {
			$termin = $termine[$a];
			if ($termin["start"] < $minute && ($termin["start"] + $termin["duration"]) > $minute) {
				$gefundene_termine[] = $a;

				if ($minute > 500 && $a == 2) {
					echo "OOps";
				}
			}
		}
		return $gefundene_termine;
	}

	private function suche_kleinsten_rang($termin, $vergleichs_termine) {
		$max = -1;
		$termin["ende"] = $termin["start"] + $termin["duration"];
		foreach ($vergleichs_termine as $vergleichs_termin) {
			$vergleichs_termin["ende"] = $vergleichs_termin["start"] + $vergleichs_termin["duration"];
			if ($termin["summary"] != $vergleichs_termin["summary"] || $termin["location"] != $vergleichs_termin["location"]) {

				if (( $termin["start"] >= $vergleichs_termin["start"] && $termin["start"] <= $vergleichs_termin["ende"] ) ||
						( $termin["ende"] >= $vergleichs_termin["start"] && $termin["ende"] <= $vergleichs_termin["ende"] )) {
					if ($vergleichs_termin["ueberschneidungs_rang"] > $max)
						$max = $vergleichs_termin["ueberschneidungs_rang"];
				}
			}
		}
		return $max;
	}

	public function datum_vor($datum = '') {
		// Datum kann ein Tag in der aktuellen Woche.
		if (!$datum || $datum === '') {
			$datum = date("Y-m-d");
		}

		return date("d-m-Y", strtotime(date("Y-m-d", strtotime($datum)) . " +1 week"));
	}

	public function datum_zurueck($datum = '') {
		// Datum kann ein Tag in der aktuellen Woche.
		if (!$datum || $datum === '') {
			$datum = date("Y-m-d");
		}

		return date("d-m-Y", strtotime(date("Y-m-d", strtotime($datum)) . " -1 week"));
	}

}
