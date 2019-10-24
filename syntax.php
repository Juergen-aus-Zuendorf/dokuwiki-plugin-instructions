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
        return 169;             /* ??? */
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{INSTR<.*?}}',$mode,'plugin_instructions');
    }

    function handle($match, $state, $pos, Doku_Handler $handler){
        global $conf;
        $this->Info = $this->getInfo();
        $this->Info['PluginPath'] = DOKU_PLUGIN.$this->Info['base'].'/';
		
		
        // Eingabe-Wert verarbeiten
		$match = substr($match, 8, -2);
		list($typ) = explode('>',$match);
		$typ = strtolower($typ);
		
		
		/* Steuerzeichen im Wiki-Code verarbeiten: */
		// Zeilenumbruch:
		$match = str_replace(array("|+"), '<br>', $match);
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
		// Unterstrichen:
		while (strpos($match, '__') !== false) {
			$match = preg_replace('/__/', '<u>', $match, 1); 
			$match = preg_replace('/__/', '</u>', $match, 1); 
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
		
		
		// Parameter aufspalten:
		$param = explode('|-',$match);
		
		$datei='tpl/'.$typ.'/html.txt';

		$zeilen = file($datei,true);
		
		for($i=1; $i<count($zeilen); $i++) {
			list($p, $z) = explode('-',$zeilen[$i]);
			$z = (int)$z;
			if ($p == "param") {   
				// HTML-Zeile enthält Platzhalter für Parameter           
				$var = $var.trim($param[$z]);
			}
			else { 				
				// HTML-Zeile enthält Code
				$var = $var.$zeilen[$i];
			}
		}

        // Übergabe-Wert für Renderer
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