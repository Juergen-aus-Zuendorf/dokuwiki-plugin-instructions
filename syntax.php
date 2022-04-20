<?php
/**
 * @license    GPL (http://www.gnu.org/licenses/gpl.html)
 * @author     Hans-Juergen Schuemmer
 *
 */

if(!defined('DOKU_INC')) die();

//if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
//if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_instructions extends DokuWiki_Syntax_Plugin {

	function getType() {
		return 'substition';
	}

	function getSort() {
		return 16;
	}

	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('~~INSTR~~.*?~~END~~',$mode,'plugin_instructions');   // Syntax funktioniert zusammen mit Plugin CKGEdit
		$this->Lexer->addSpecialPattern('{{INSTR<.*?}}',$mode,'plugin_instructions');         // veraltet wegen CKGEdit
	}

	function handle($match, $state, $pos, Doku_Handler $handler){

		// aktuelle Seite "@ID@" und "@PAGE@":
		global $ID, $INFO;
		$urldoku = DOKU_URL;
		$urlpage = DOKU_URL."doku.php?id=".$ID;
		
		$pg_curr = $INFO['id'];
		if (strrpos($pg_curr,":") > 0) {
			$pg_curr = substr(strrchr($INFO['id'], ":"), 1);
		};
		$id_curr = $ID;
		if (strrpos($id_curr,":") > 0) {
			$id_curr = substr(strrchr($ID, ":"), 1);
		};
		$ns_long = substr(strrchr($urlpage, "="), 1);
		$ns_long = substr($ns_long,0,strrpos($ns_long,":"));
		$ns_curr = $ns_long;
		if (strrpos($ns_long,":") > 0) {
			$ns_curr = substr(strrchr($ns_long, ":"), 1);
		};
		$ns_main = $INFO['namespace'];		// Namespce bei Verwendung in der Sidebar

// echo "ID :", $ID, "<br />";
// echo "INFO['id'] :", $INFO['id'], "<br />";
// echo "INFO['namespace'] :", $INFO['namespace'], "<br />";
// echo "ns_long :", $ns_long, "<br />";
// echo "ns_curr :", $ns_curr, "<br />";
// echo "ns_main :", $ns_main, "<br />";

		// Eingabe-Wert verarbeiten
		$match = str_replace("{{INSTR<", '', $match);
		$match = str_replace("}}", '', $match);
		$match = str_replace("~~INSTR~~", '', $match);
		$match = str_replace("~~END~~", '', $match);

		// Typ des Templates auslesen
		if (substr_count($match,"~~") > 0) {
			list($typ) = explode('~~',$match);
		} else {
			list($typ) = explode('>',$match);
		}
		$typ = strtolower($typ);

		// Zeilenumbruch verarbeiten:
		$match = str_replace(array("|+"), '<br>', $match);

		// Steuerzeichen im Wiki-Code verarbeiten:
		//zwei hintereinanderfolgende Leerzeichen sollen als Einrückung ausgegeben werden:
		$match = str_replace(array("  "), '&nbsp; &nbsp;', $match);
		// Kursivschrift:
		while (strpos($match, '//') !== false) {
			$match = preg_replace('/\/\//', '<i>', $match, 1); 
			$match = preg_replace('/\/\//', '</i>', $match, 1); 
		};
		// Fettschrift:
		while (strpos($match, '**') !== false) {
			$match = preg_replace('/\*\*/', '<b>', $match, 1); 
			$match = preg_replace('/\*\*/', '</b>', $match, 1); 
		};
		// Codetext:
		while (strpos($match, "''") !== false) {
			$match = preg_replace("/''/", '<code>', $match, 1); 
			$match = preg_replace("/''/", '</code>', $match, 1); 
		};
		// Verarbeitung Wiki-Links:
		while (strpos($match, '[[') !== false) {
			$s1 = strpos($match, '[[') + 2;
			$s2 = strpos($match, ']]');
			$title = substr($match, $s1, $s2-$s1);
			$s3 = strpos($title, '|') + 1;
			$l3 = strlen($title);
			$title = substr($title, $s3, $l3);
			$content = substr($match, $s1, $s3-1);
			$match = str_replace("|".$title, "", $match); 
			$match = preg_replace('/\[\[/', '<a href="'.DOKU_BASE.'/doku.php?id=', $match, 1); 
			$match = preg_replace('/\]\]/', '">'.$title.'</a>', $match, 1); 
		};

		// Platzhalter für Namensraum und aktuelle Seite:
		while (strpos($match, '@PAGE@') !== false) {
			$match = preg_replace('/@PAGE@/', $pg_curr, $match, 1); 
		}
		while (strpos($match, '@ID@') !== false) {
			$match = preg_replace('/@ID@/', $id_curr, $match, 1); 
		}
		while (strpos($match, '@NS@') !== false) {
			$match = preg_replace('/@NS@/', $ns_long, $match, 1); 
		}
		while (strpos($match, '@NSMAIN@') !== false) {
			$match = preg_replace('/@NSMAIN@/', $ns_main, $match, 1); 
		}
		while (strpos($match, '@CURNS@') !== false) {
			$match = preg_replace('/@CURNS@/', $ns_curr, $match, 1); 
		}
		while (strpos($match, '@URL_DOKU@') !== false) {
			$match = preg_replace('/@URL_DOKU@/', $urldoku, $match, 1); 
		}
		while (strpos($match, '@URL_PAGE@') !== false) {
			$match = preg_replace('/@URL_PAGE@/', $urlpage, $match, 1); 
		}

		// Parameter aufspalten:
		$param = explode('|-',$match);

		$datei='tpl/'.$typ.'/html.txt';
		$zeilen = file($datei,true);

		for($i=1; $i<count($zeilen); $i++) {
			list($p, $z) = explode('-',$zeilen[$i]);
			$z = (int)$z;
			if ($p == "param") {
				// HTML-Zeile enthält Platzhalter für Parameter
				$tpz = trim($param[$z]);
				$var = $var.$tpz;
			}
			else {
				// HTML-Zeile enthält Code
				$z_trim = trim($zeilen[$i]);
				if ((($z_trim == "<br>") or ($z_trim == "<br />")) and ($tpz == "")) {
					// falls zuvor kein Parameter übergeben wurde, den ersten nachfolgenden Zeilenumbruch unterdrücken
					$tpz = "leer";
				}
				else {
					$var = $var.$z_trim;
				}
			}
		}

		// Übergabe-Wert für Renderer:
		return $var;
	}

	function render($mode, Doku_Renderer $renderer, $data) {
		if($mode == 'xhtml'){
			$renderer->doc .= $data;
			return true;
		}
		return false;
	}
}
?>
