<?php
#ez change ip script for globe modems
#works with B315/B310/B525
#need administrator privilages, modem must be customized or r00ted
#powered by inabaindustries
#greetz: rsg: the electric boogaloo
$modem_ip = '192.168.8.1'; // modem hostname/address
$username = 'admin'; // modem administrator user login
$password = 'admin'; // modem administrator user password

libxml_use_internal_errors(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $modem_ip . '/api/webserver/SesTokInfo');	
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$sesh = curl_exec($ch);
$xml = simplexml_load_string($sesh, "SimpleXMLElement", LIBXML_NOCDATA);
$json = json_encode($xml);
$getsesh = json_decode($json);
$tokinfo = $getsesh->TokInfo;
curl_close($ch);

$getcsrf = curl_init();
curl_setopt($getcsrf, CURLOPT_URL, 'http://' . $modem_ip . '/html/home.html');	
curl_setopt($getcsrf, CURLOPT_RETURNTRANSFER, true);
curl_setopt($getcsrf, CURLOPT_HEADER, true);
$csrfkey = curl_exec($getcsrf);
$header_size = curl_getinfo($getcsrf, CURLINFO_HEADER_SIZE);
$headercsrf = substr($csrfkey, 0, $header_size);
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
$parsed = get_string_between($headercsrf, 'Cookie:', ';path');


curl_close($getcsrf);

$doc = DOMDocument::loadHTML($csrfkey);
$xpath = new DOMXPath($doc);
$query = "//meta[@name='csrf_token'][last()]";
$entries = $xpath->query($query);
foreach ($entries as $entry) {
  $csrftokenlast = $entry->getAttribute("content");
}


$hashed = base64_encode(hash('sha256', $username.base64_encode(hash('sha256', $password, false)) . $csrftokenlast));
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'http://' . $modem_ip . '/api/user/login');	
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HEADER, true);
curl_setopt($ch2, CURLOPT_VERBOSE, true);
curl_setopt($ch2, CURLOPT_POST, true);
$headers = array();
$headers[] = 'Accept: */*';
$headers[] = '__RequestVerificationToken: '. $csrftokenlast .'';
$headers[] = 'Content-type: application/x-www-form-urlencoded';
$headers[] = 'X-Requested-With: XMLHttpRequest';
$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0';
$headers[] = 'Accept-Language: en-PH,en;q=0.7,ja;q=0.3';
$headers[] = 'Host: ' . $modem_ip . '';
$headers[] = 'Connection: Keep-Alive';
$headers[] = 'Pragma: no-cache';
$headers[] = 'Cookie: '. $parsed .'';
curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch2, CURLOPT_POSTFIELDS, '<request><Username>admin</Username><Password>'. $hashed .'</Password><password_type>4</password_type></request>');
$loginsesh = curl_exec($ch2);
$header_size = curl_getinfo($ch2, CURLINFO_HEADER_SIZE);
$header = substr($loginsesh, 0, $header_size);
$authcsrf = get_string_between($loginsesh, 'RequestVerificationToken:', '#');
$authcookie = get_string_between($loginsesh, 'Cookie:', ';path');
curl_close($ch2);

$determinestate = curl_init();
curl_setopt($determinestate, CURLOPT_URL, 'http://' . $modem_ip . '/api/net/net-mode');	
curl_setopt($determinestate, CURLOPT_RETURNTRANSFER, true);
$headers2 = array();
$headers2[] = 'Accept: */*';
$headers2[] = '__RequestVerificationToken: '. $authcsrf .'';
$headers2[] = 'Content-type: application/x-www-form-urlencoded';
$headers2[] = 'X-Requested-With: XMLHttpRequest';
$headers2[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0';
$headers2[] = 'Accept-Language: en-PH,en;q=0.7,ja;q=0.3';
$headers2[] = 'Host: ' . $modem_ip . '';
$headers2[] = 'Connection: Keep-Alive';
$headers2[] = 'Pragma: no-cache';
$headers2[] = 'Cookie: '. $authcookie .'';
curl_setopt($determinestate, CURLOPT_HTTPHEADER, $headers2);
$state = curl_exec($determinestate);
$xmlstate = simplexml_load_string($state, "SimpleXMLElement", LIBXML_NOCDATA);
$jsonstate = json_encode($xmlstate);
$getstate = json_decode($jsonstate);
if($getstate->NetworkMode == "00"){
	$changestate = '<request><NetworkMode>03</NetworkMode><NetworkBand>3FFFFFFF</NetworkBand><LTEBand>7FFFFFFFFFFFFFFF</LTEBand></request>';
}
else if($getstate->NetworkMode == "03"){
	$changestate = '<request><NetworkMode>00</NetworkMode><NetworkBand>3FFFFFFF</NetworkBand><LTEBand>7FFFFFFFFFFFFFFF</LTEBand></request>';
}
else{
	echo 'error';
}
curl_close($determinestate);

$changeip = curl_init();
curl_setopt($changeip, CURLOPT_URL, 'http://' . $modem_ip . '/api/net/net-mode');	
curl_setopt($changeip, CURLOPT_RETURNTRANSFER, true);
curl_setopt($changeip, CURLOPT_POST, true);
curl_setopt($changeip, CURLOPT_POSTFIELDS, $changestate);
$headers = array();
$headers[] = 'Accept: */*';
$headers[] = '__RequestVerificationToken: '. $authcsrf .'';
$headers[] = 'Content-type: application/x-www-form-urlencoded';
$headers[] = 'X-Requested-With: XMLHttpRequest';
$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0';
$headers[] = 'Accept-Language: en-PH,en;q=0.7,ja;q=0.3';
$headers[] = 'Host: ' . $modem_ip . '';
$headers[] = 'Connection: Keep-Alive';
$headers[] = 'Pragma: no-cache';
$headers[] = 'Cookie: '. $authcookie .'';
curl_setopt($changeip, CURLOPT_HTTPHEADER, $headers);
$changeresult = curl_exec($changeip);
echo '<pre>Change IP Status : ' . $changeresult . '</pre></br>';
echo '<pre>ezpz change ip script for huawei modems - inabaindustries.org';
curl_close($changeip);
?>	

