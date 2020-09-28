<?php  
/**  
* Filtro probado para moodle 3.8.2+  
*  
* This filter will replace any R code in [R]...[/R]
*  
* @package    	filter_roodle  
* @copyright  	2017 Barrera, Badel  
*   			2020 Claros,Morales,Echeverria - Fixbugs and improve sessions persistent in linux environments  
* @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
*/ 
defined('MOODLE_INTERNAL') || die(); 
class filter_roodle extends moodle_text_filter 
{
	public function PrintCallTrace($rcode)
	{
		$e = new Exception();
		$trace = explode("\n", $e->getTraceAsString());
		$trace = array_reverse($trace);
		array_shift($trace); // remove {main}
		array_pop($trace); // remove call to this method
		$length = count($trace);
		$result = array();	   
		for ($i = 0; $i < $length; $i++)
		{
			$result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' '));
		}
		echo "<pre><b>$rcode</b>\n TRACE: \t" . implode("\n\t", $result)."</pre>";
	}
	
	public function filter($text, array $options = array()) 
	{ 	
		global $CFG;  
        if (!is_string($text) or empty($text) or strpos($text,"[R") === false) 
		{ 	
			return $text;         
		}
		
		$temp = get_config('filter_rcode', 'temporal');
		//return $temp;
		//$search = '/\[R\](.+?)\[\/R\]/is'; 		
		//$newtext = preg_replace_callback($search, 'filter_r_callback', $text);
		
		$search = '/\[R\s*\w*\s*\](.+?)\[\/R\]/is';  
		$newtext = preg_replace_callback($search, 'filter_r_callback', $text);
		
		//$this->PrintCallTrace($newtext);		
		if (is_null($newtext)) 
		{ 
			return $text;         
		} 
		return $newtext;		 
	} 
}

function filter_r_callback($expression) {
	global $CFG;
	$showCode = false;
	if(strpos($expression[0],"CODE")===false){
		$showCode = false;
	}else{
		//$expression[1] =$expression[2];
		$showCode = true;
	}
	
	//return '(1) '.$expression[0].' (2) '.$expression[1];
	$expression = strip_tags($expression[1]);	
	$expression = str_replace("<p>","",$expression);    
	$expression = str_replace("</p>","; ",$expression);
	$expression = trim(preg_replace('/(\n?\r?)+(<br>)+/', '; ', $expression));   
	$cadena = str_replace("print(","cat(",$expression);
	
	$n = rand(1000,10000);     
	$prefix = "plot_".$n;
	$tmpDir =   '/tmp';
	$RData = $tmpDir.'/'.session_id().'.RData';
	$scriptFile = $tmpDir.'/in_'.$n.'.R';
	
	$files = glob($tmpDir."/*.RData");
	$now   = time();
	foreach ($files as $file) {
		if (is_file($file)) {
			if ($now - filemtime($file) >= 60 * 60 * 24 * 2) { // 2 days
				unlink($file);
			}
		}
	}
	
	$input = 'png(file = "'.$tmpDir.'/'.$prefix.'_%d.png"); ';                 
	$input .= strip_tags($cadena);     
	$input = trim(preg_replace('/(\n\r)+/', '; ', $input));    
	$input = trim(preg_replace('/&nbsp;/', ' ', $input));     
	$input = html_entity_decode($input, ENT_QUOTES, 'UTF-8');    
	$input = str_replace("; ;","; ",$input);		
	
	$script = 'if(file.exists("'.$RData.'")){ load("'.$RData.'"); }; ';
	$script.= $input;
	$script.= 'save(list = ls(all=TRUE),file="'.$RData.'");'; 
			
	if (!($fi = fopen($scriptFile, 'w'))){
		return "No se pudo crear archivo temporal: ".$scriptFile; 
	}
	fwrite($fi, $script);// Creación del archivo temporal 
	fclose($fi);  
	
	$cmd = 'R --slave -f '.$scriptFile.' 2>&1';		
	$clo = shell_exec($cmd); 
	
	unlink($scriptFile); 
	
	$imagenes = array();  
	$files = glob($tmpDir.'/'.$prefix.'_[0-9].png'); 
	
	foreach ($files as $i => $path)
	{         
		$type = pathinfo($path, PATHINFO_EXTENSION);      
		$data = file_get_contents($path);      
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);  		
		//Ignore empty images
		if($base64 !="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAeAAAAHgCAMAAABKCk6nAAAAA1BMVEX///+nxBvIAAAA9klEQVR4nO3BAQ0AAADCoPdPbQ8HFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD8G4YNAAGL73n/AAAAAElFTkSuQmCC"){
			$imagenes[$i] = sprintf("<div><img src='%s' /></div>",$base64); 
		}
		unlink($path);
	}	
	$output = $clo;
	foreach($imagenes as $image){
		$output .= $image;  
	}
	if($showCode){
		$output="<div style=".'background-color:#eee;padding:10px;'.">&gt; <code>$cadena</code><br><pre>".$output."</pre></div>";
	}
	return $output;
}
