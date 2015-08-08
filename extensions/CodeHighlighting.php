<?php
$wgExtensionFunctions[] = "wfCodeHighlightingExtension";
function wfCodeHighlightingExtension() 
{
   global $wgParser;
   $wgParser->setHook('code', 'renderCode');
}
function renderCode( $input="", $argv=array() )
{
 $result = SyntaxHighlighting($input, $argv['language']);
 return '<pre>' . trim($result) . '</pre>';  
}
function SyntaxHighlighting($code, $languageKey)
{ 
 $location = 'http://localhost/SyntaxHighlightingWS/SyntaxHighlighting.asmx?wsdl';
 $result = $code;
 
 try
 { 
  $client = new SoapClient($location);
  $arr = array("code" => $code, "languageKey" => $languageKey);
  $result = $client->Parse($arr)->ParseResult; 
 }
 catch(SoapFault $exception)
 {  
  if (strpos($exception->faultstring, "LANGKEYNOTEXIST") === false)
  {
   throw $exception;
  }
 }   
 return $result; 
}
?>