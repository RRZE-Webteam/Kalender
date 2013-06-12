<?php 

	//error_reporting(E_ALL);

	$args = (!empty($_GET)) ? $_GET:array('task'=>$argv[1]);
	$optionen = ladeConf($args);
	
	$ansichten = array(
		"tag" => 'Tagesansicht',
		"woche" => 'Wochenansicht',
		"monat" => 'Monatsansicht',
		"liste" => 'Listenansicht'
	);

	$standard_ansicht = "woche";

	$geforderte_ansicht;
	$datum;

	if($_SERVER["QUERY_STRING_UNESCAPED"]) {
		$path_info = $_SERVER["QUERY_STRING_UNESCAPED"];
		$request = explode("_", $path_info);
		
		$geforderte_ansicht = $request[0];
		$datum = $request[1];
	}


	if(!$geforderte_ansicht || $geforderte_ansicht === '') $geforderte_ansicht = $standard_ansicht;
	if(!array_key_exists($geforderte_ansicht, $ansichten)) $geforderte_ansicht = $standard_ansicht;

	$class_name = "class_".$ansichten[$geforderte_ansicht].".php";
	require_once $class_name;
	$ansicht = new $ansichten[$geforderte_ansicht]($optionen);
	
	$tage = $ansicht->lade_tage($datum);

	if($geforderte_ansicht === "liste") {
		echo $ansicht->rendere_daten($ansicht->suche_events_alle());
	}else{
		$events = $ansicht->suche_events_fuer_tage($tage);
		$tage_mit_events = array();
		foreach ($tage as $tag) {
			$tage_mit_events[] = $ansicht->suche_events_fuer_tag($tag, $events);
		}

		echo $ansicht->rendere_daten($tage_mit_events);
	}


	function ladeConf($args=NULL){
		$options= array();

		// defaults
		$defaults = array(
			'Tagesanfang' => '7:00',
			'Tagesende' => '19:00',
			'Zeige_Wochenende' => 1,
			'Zeige_Sonntag' => 0,
			'Zeige_Kalender' => 1
		);

		// load options
		
		$fpath = '../../univis.conf';
		$fpath_alternative = $_SERVER["DOCUMENT_ROOT"].'/vkdaten/kalender.conf';

		if(file_exists($fpath_alternative)){ $fpath = $fpath_alternative; }
		$options = array();
		$fh = fopen($fpath, 'r') or die('Cannot open file!');
		while(!feof($fh)) {
			$line = fgets($fh);
			$line = trim($line);
			if((strlen($line) == 0) || (substr($line, 0, 1) == '#')) {
				continue; // ignore comments and empty rows
			}
			$arr_opts = preg_split('/\t+/', $line); // tab separated
			$options[$arr_opts[0]] = $arr_opts[1];
		}
		fclose($fh);

		// merge defaults with options
		$options = array_merge($defaults, $options);
		if($args)
			return array_merge($options, $args);
		else
			return $options;

	}
?>