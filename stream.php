<?php

$file = $_SERVER['DOCUMENT_ROOT'] . $_GET["file"] ;

//if you need to add more file types do so in the filter array. 
$filter = array("flv", "mp4","mov","m4v");

//prepare the file for checking
$parts = pathinfo($file);

//make sure file exists
if (@fclose(@fopen($file, "r"))) 
{
 
	//is this a valid file to give the user? Lets find out!
	if (array_search($parts["extension"], $filter) !== false)
	{
	
			$pos = (isset($_GET["start"])) ? intval($_GET["start"]): 0;
			$tam = filesize($file);		
			//Formamos la cabecera del fichero
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Content-Type: video/x-flv");
			header("Content-Length: " . (filesize($file) - $pos));
						
			//Empaquetamos 
						
			if($start > 0)
			{
			print("FLV");
			print(pack('C',1));
			print(pack('C',1));
			print(pack('N',9));
			print(pack('N',9));
			}
						
			//Abrimos el fichero, y leemos desde pos hasta el final 
						
			$fh = fopen($file, "rb");
			fseek($fh, $start);
			fpassthru($fh);
			fclose($fh);
	
	}

}	
else
{
	//Bad File Given
	echo 'Bad File Request';
}	


?>