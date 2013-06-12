<?php

	function array_copy( array $array ) {
	        $result = array();
	        foreach( $array as $key => $val ) {
	            if( is_array( $val ) ) {
	                $result[$key] = arrayCopy( $val );
	            } elseif ( is_object( $val ) ) {
	                $result[$key] = clone $val;
	            } else {
	                $result[$key] = $val;
	            }
	        }
	        return $result;
	}

	function pre($obj) {
		echo '<pre>';
		var_dump($obj);
		echo '</pre>';
	}

	class Termin {

		public $titel = "";

		public $termin_data = NULL;

		private $start = 0;

		private $ende = 0;

		private $spalten_gesamt = 0;

		private $spalte = -1;

		public function __construct($termin_data) {
			

			$this->termin_data = $termin_data;
			$this->titel = $termin_data["summary"];
			$this->start = $termin_data["start"];
			$this->ende = $termin_data["start"] + $termin_data["duration"];
	
		}

		public function erhoehe_spalten_gesamt($anzahl) {
			if(is_null($anzahl) || !is_int($anzahl)) return false;

			if($anzahl > $this->spalten_gesamt) $this->spalten_gesamt = $anzahl;
		}

		public function hole_spalten_gesamt() {
			return $this->spalten_gesamt;
		}

		public function hole_spalte() {
			return $this->spalte;
		}

		public function setze_spalte($spalte) {
			if(is_null($spalte) || !is_int($spalte)) return false;

			$this->spalte = $spalte;
		}

		public function ist_gesetzt() {
			return $this->spalte === -1;
		}

		public function hole_start() {
			return $this->start;
		}

		public function hole_ende() {
			return $this->ende;
		}

		public function hole_laenge() {
			return $this->ende - $this->start;
		}

		public function findet_statt($zeit = 0) {
			if(is_null($zeit) || !is_int($zeit)) return false;

			if( $this->hole_start() < $zeit && $zeit < $this->hole_ende())  {
				return true;
			}else{
				return false;
			}
		}

		public function findet_statt_zwischen($anfang = 0, $ende = 0) {
			if(is_null($anfang) || !is_int($anfang)) return false;
			if(is_null($ende) || !is_int($ende)) return false;

			pre($anfang);
			pre($ende);
			pre($this);


			if( ($this->hole_start() >= $anfang && $this->hole_start() <= $ende) 
				|| ($this->hole_ende() >= $anfang && $this->hole_ende() <= $ende) ) {
				echo 'ja';
				return true;
			}else{
				echo 'nein';
				return false;
			}
		}

		public function kollidiert_mit($anfang = 0, $ende = 0) {
			if(is_null($anfang) || !is_int($anfang)) return false;
			if(is_null($ende) || !is_int($ende)) return false;


			for ($m=min($anfang, $this->hole_start()); $m < max($ende, $this->hole_ende()); $m++) { 
				
				if( ($anfang <= $m && $ende >= $m) ) {
					if($this->findet_statt($m)) {
						return true;
					}
				}
			}
			return false;
		}

		public function kollidiert_mit_termin($termin) {
			pre($termin);
			return $this->findet_statt_zwischen($termin->hole_start(), $termin->hole_ende());
		}


		public function speichern() {
			$this->termin_data["spalten_gesamt"] = $this->spalten_gesamt;
			$this->termin_data["spalte"] = $this->spalte;
		}
	}

	class Tag {
		
		private $termine = NULL;

		private $tag_laenge = 800;

		public function __construct($termine = NULL) {
			if(is_null($termine) || !is_array($termine)) return -1;

			$this->termine = array();
			foreach ($termine as $termin_data) {
				$termin = new Termin($termin_data);
				$this->termine[] = $termin;
			}
		}

		
		public function markiere_termin_spalten() {
			
			for ($m=0; $m < $this->tag_laenge(); $m++) { 
				
				// Welche Events finden gerade statt?
				$aktuelle_termine = array();
				for ($e=0; $e < count($this->termine); $e++) { 
					
					if($this->termine[$e]->findet_statt($m)) {
						$aktuelle_termine[] = $this->termine[$e];
					}
				}
				
				// Markiere aktuelle Events
				for ($ae=0; $ae < count($aktuelle_termine); $ae++) { 
					$aktuelle_termine[$ae]->erhoehe_spalten_gesamt(count($aktuelle_termine));
				}
			}
		}

		public function markiere_termin_indizes() {

			for ($e=0; $e < count($this->termine); $e++) { 
				$aktueller_termin = $this->termine[$e];

				$spalte_gefunden = false;
				for ($spalte=0; $spalte < $aktueller_termin->hole_spalten_gesamt() && !$spalte_gefunden; $spalte++) { 
					
					$nachbarn = array_copy($this->termine);
					unset($nachbarn[$e]);

					$termine = $this->filtere_termine($nachbarn, $aktueller_termin->hole_start(), $aktueller_termin->hole_ende(), $spalte);
					
					if(count($termine) == 0) {
						$spalte_gefunden = true;
						$aktueller_termin->setze_spalte($spalte);
					}
				}
				
			}

			for ($e=0; $e < count($this->termine); $e++) {
				if($this->termine[$e]->hole_spalte() === -1) {
					$this->termine[$e]->setze_spalte($this->termine[$e]->hole_spalten_gesamt()-1);
				}
			}

		}

		public function filtere_termine($termine = NULL, $anfang = 0, $ende = 0, $spalte = 0) {
			
			$ergebnis = array();

			foreach ($termine as $aktueller_termin) {

				if($aktueller_termin->hole_spalte() == $spalte && $aktueller_termin->kollidiert_mit($anfang, $ende)) {
					$ergebnis[] = $aktueller_termin;
				}
			}

			return $ergebnis;
		}

		public function tag_laenge() {
			$tag_laenge = 0;
			foreach ($this->termine as $termin) {
				$tag_laenge = max($tag_laenge, $termin->hole_ende());
			}
			return $tag_laenge;
		}

		public function neue_termine() {

			$termine = array();
			foreach ($this->termine as $termin) {

				$termin->speichern();

				$spacing = 1;
				$width = 90 / $termin->hole_spalten_gesamt() - 2 * $spacing;
				$left = 5 + (($width + 2*$spacing) * $termin->hole_spalte());
				$termin->termin_data["width"] = $width;
				$termin->termin_data["left"] = $left;
				
				$termine[] = $termin->termin_data;
			}

			return $termine;
		}

		public function test_ausgabe() {
			foreach ($this->termine as $termin) {
				$spacing = 1;
				$width = 90 / $termin->hole_spalten_gesamt() - 2 * $spacing;
				$left = 5 + (($width + 2*$spacing) * $termin->hole_spalte());

				echo '<div style="position: absolute; left: '.$left.'%; top: '.$termin->hole_start().'px; height: '.$termin->hole_laenge().'px; border: solid 1px; width:'.$width.'%; text-overflow: ellipsis;
overflow: hidden;">';
				echo $termin->titel;
				echo '</div>';

			}
		}
	}


	// $termine = array();

	// $termine[] = new Termin("Sitzung des LS12", 0, 120);
	// $termine[] = new Termin("HSCD Seminar", 60, 180);
	// $termine[] = new Termin("Sitzung des LS12", 120, 180);

	// $termine[] = new Termin("Uebung zu Security", 195, 300);
	// $termine[] = new Termin("Sitzung des LS12", 240, 480);
	// $termine[] = new Termin("Praktikum: Lego", 280, 600);
	// $termine[] = new Termin("Security in Embeded Hardware", 300, 420);

	// $tag = new Tag($termine);
	// $tag->markiere_termin_spalten();
	// $tag->markiere_termin_indizes();
	// $tag->test_ausgabe();
?>