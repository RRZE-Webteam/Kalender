<?php

require_once 'Mustache/Autoloader.php';

require_once 'SG-iCalendar/SG_iCal.php';
require_once 'SG-iCalendar/helpers/SG_iCal_Query.php';

abstract class Ansicht {

	protected $monate = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
	protected $wochentag_namen = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
	protected $optionen = NULL;

	// Gibt den Dateiname des Templates zurueck.
	abstract protected function template_name();

	// Funktion soll fuer jede Ansicht und Datum die anzuzeigenden Tage als Array zurueckgeben.
	abstract protected function lade_tage($datum = '');

	// Rendert das Template mit den uebergebenen Daten.
	// Falls noetig werden die Daten fuer die jeweilige Anischt noch formatiert.
	abstract protected function rendere_daten($daten = '');

	abstract protected function datum_vor($datum = '');

	abstract protected function datum_zurueck($datum = '');

	private $ical_parser;
	private $ical_query;

	public function __construct($optionen = NULL) {

		if ($optionen) {
			$this->optionen = $optionen;
		}

		$anzuzeigender_Kalender = "Kalender" . $this->optionen["Zeige_Kalender"];
		if (!$this->optionen[$anzuzeigender_Kalender]) {
			echo "Kalender URL nicht angegeben.";
			return -1;
		}

		$kalender_url = $this->optionen[$anzuzeigender_Kalender];

		$this->ical_parser = new SG_iCal($kalender_url);
		$this->ical_query = new SG_iCal_Query();
	}

	public function suche_events_fuer_tage($tage) {
		$anfang = $tage[0];
		$ende = $tage[count($tage) - 1];

		return $this->ical_query->Between($this->ical_parser->getEvents(), strtotime($anfang . "-00:00"), strtotime($ende . "-24:00"));
	}

	public function suche_events_fuer_tag($tag = '', $events = NULL) {

		if ($tag === '') {
			// Kein Tag angegeben (zB. bei Listenansicht) -> Suche nach allen Events.
			$events = $this->ical_parser->getEvents();
		} else {
			// Hack: strtotime schlaegt immer 2 Stunden drauf. Diese werden hier wieder abgezogen.
			$events = $this->ical_query->Between($events, strtotime($tag . '-00:00 - 2 hours'), strtotime($tag . '-00:00 + 1 day - 2 hours'));
		}

		$events_data = array();
		foreach ($events as $event) {
			$event_data = array();

			$properties = array("summary", "description", "location");
			foreach ($properties as $prop) {
				$event_data[$prop] = $event->getProperty($prop);
			}
			// Dauer in Minuten
			$event_data["duration"] = $event->getDuration() / 60;

			// Start in Conf angegeben (Pro Minute ein Pixel hoehe)
			$startzeit = explode(":", $this->optionen["Tagesanfang"]);
			$event_data["start"] = (date("G", $event->getProperty("start")) - $startzeit[0]) * 60 + date("i", $event->getProperty("start") - $startzeit[1]);

			// Zeitanzeige an Termin
			$event_data["time"] = date("G:i", $event->getProperty("start")) . " - " . date("G:i", $event->getProperty("end"));

			if ($event_data["description"] && $event_data["description"] !== "") {
				$vorschau_laenge = 100;
				$description = str_split($event_data["description"], $vorschau_laenge);

				if (count($description) === 1) {
					$event_data["description_vorschau"] = strip_tags(htmlspecialchars_decode($description[0]));
				} else {
					$event_data["description_vorschau"] = strip_tags(htmlspecialchars_decode($description[0])) . "...";
					$event_data["description_komplett"] = htmlspecialchars_decode(str_replace("\n", "<br />", implode("", $description)));
				}
			}

			// Bei Wiederholenden Events die Regeln zusammenfassen.
			$regel = '';
			$wochentag = $this->wochentag_namen[date("w", $event->getProperty('start'))];
			$interval = NULL;
			$freq = -1;
			if ($event->getFrequency()) {
				$interval = $event->getFrequency()->getInterval();
				$freq = $event->getFrequency()->getFreq();
			}

			switch ($freq) {
				case 'weekly':
					if ($interval == 1) {
						$regel = "Jeden " . $wochentag;
					} else {
						$regel = "Jeden " . $interval . ". " . $wochentag;
					}
					break;

				default:
					$regel = date("d.m.Y", $event->getProperty('start'));
					break;
			}

			$event_data["datum"] = $regel;

			// Ganztagige Events rausfiltern
			if ($event->isWholeDay()) {
				$event_data["ganztagig"] = true;
				$datum = array(date("d.m.Y", $event->getStart()));
				if ($event->getDuration() / (24 * 60 * 60) > 1) {

					$datum[] = date("d.m.Y", $event->getRangeEnd() - 1);
				}
				$event_data["datum"] = implode(" - ", $datum);
			} else {
				$event_data["nicht_ganztagig"] = true;
			}

			// Farbmarkierung
			$farben = array("Blau", "Rot", "Gruen", "Orange", "Tuerkis");
			foreach ($farben as $farbe) {
				// Wenn Keywords fuer Farbe gesetzt ist, versuche diese zuzuordnen.
				if ($this->optionen[$farbe]) {
					$suchwoerter = explode(";", $this->optionen[$farbe]);
					foreach ($suchwoerter as $suchwort) {
						if (strpos($event_data["summary"], $suchwort) !== false) {
							$event_data["farbe"] = $farbe;
						}
					}

					if (!$event_data["farbe"]) {
						$event_data["farbe"] = $farben[0];
					}
				}
			}

			$events_data[] = $event_data;
		}


		$ts = strtotime($tag);
		$datum = date("j", $ts);

		$monat = $this->monate[date("n", $ts) - 1];

		$wochentag = str_split($this->wochentag_namen[date("w", $ts)], 2);
		$wochentag_anfang = $wochentag[0];
		unset($wochentag[0]);
		$wochentag_ende = implode("", $wochentag);

		// Berechne Tageslaenge in Minuten
		$tag_laenge = (strtotime($this->optionen["Tagesende"]) - strtotime($this->optionen["Tagesanfang"])) / 60;
		$tag_anfang = "7:00";
		if ($this->optionen["Tagesanfang"]) {
			$tag_anfang = $this->optionen["Tagesanfang"];
		}

		return array(
			"termine" => $events_data,
			"datum" => $tag,
			"monat" => $monat,
			"datum_kurz" => $datum,
			"wochentag_anfang" => $wochentag_anfang,
			"wochentag_ende" => $wochentag_ende,
			"heute" => $this->ist_heute($tag),
			"wochenende" => $this->ist_wochenende($tag),
			"sonntag" => $this->ist_sonntag($tag),
			"tag_laenge" => $tag_laenge,
			"tag_anfang" => $tag_anfang
		);
	}

	public function suche_events_alle() {
		return $this->suche_events_fuer_tag();
	}

	protected function ist_heute($datum = '') {
		if ($datum == "")
			return false;

		return (date("Y-m-d") === $datum);
	}

	protected function ist_wochenende($datum = '') {
		if ($datum == "")
			return false;

		return (date("N", strtotime($datum)) >= 6);
	}

	protected function ist_sonntag($datum = '') {
		if ($datum == "")
			return false;

		return (date("N", strtotime($datum)) == 7);
	}

	protected function ist_im_monat($datum = '', $monat = '') {
		if ($datum == "")
			return false;

		return (date("m", strtotime($datum)) === $monat);
	}

	protected function datum_aktuell($datum = '') {
		// Datum kann ein Tag in der aktuellen Woche.
		if (!$datum || $datum === '') {
			$datum = date("Ymd");
		}

		return date("Y-m-d", strtotime($datum));
	}

	protected function rendere_template($daten) {
		Mustache_Autoloader::register();

		$m = new Mustache_Engine;
		$template = $this->lade_template();

		if ($template == -1)
			return -1;

		// Aktuellen Dateinamen fuer korrekte Verlinkungen mit in Template Daten einfuegen
		$daten["dateiname"] = $_SERVER['DOCUMENT_NAME'];

		return $m->render($template, $daten);
	}

	protected function lade_template() {
		$filename = $this->template_name();
		$filename = "templates/" . $filename;
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		return $contents;
	}

}
