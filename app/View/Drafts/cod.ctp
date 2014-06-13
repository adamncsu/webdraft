<?php 
header('Content-type: text/xml');
header("Content-disposition: attachment; filename=deck.cod");

$domtree = new DOMDocument('1.0', 'UTF-8');

$xmlRoot = $domtree->createElement("cockatrice_deck");

$version = $domtree->createAttribute('version');
$version->value = '1';
$xmlRoot->appendChild($version);

$xmlRoot = $domtree->appendChild($xmlRoot);

$deckname = $domtree->createElement('deckname', 'Untitled Deck');
$comments = $domtree->createElement('comments', '');
$xmlRoot->appendChild($deckname);
$xmlRoot->appendChild($comments);

$zone = $domtree->createElement('zone');
$zonename = $domtree->createAttribute('name');
$zonename->value = 'main';
$zone->appendChild($zonename);

$cardarray = explode("\r\n", $data);
foreach ($cardarray as $c){
	$cex = explode(" ", $c, 2);
	if(count($cex) < 2)
		continue;

	$card = $domtree->createElement('card');
	
	$number = $domtree->createAttribute('number');
	$number->value = $cex[0];
	
	$price = $domtree->createAttribute('price');
	$price->value = '0';
	
	$name = $domtree->createAttribute('name');
	$name->value = $cex[1];
	
	$card->appendChild($number);
	$card->appendChild($price);
	$card->appendChild($name);
	$zone->appendChild($card);
}

$xmlRoot->appendChild($zone);

$domtree->formatOutput = TRUE;
echo $domtree->saveXML(); 
	
?>