<?php
$start = time();
$out = "";

require_once(dirname(__FILE__)."/inc/db.php");
$apiConfig = json_decode(file_get_contents(dirname(__FILE__)."/config/api.json"));

//Query to get all titles
$res = $mysqli->query("select 
	node.title as title, 
	resume.field_resume_ficheserie_value as summary,
	origname.field_nom_originale_value as original_name,
	concat('https://geekseries.fr/sites/default/files/images_serie_interieur_grande/', image_in_url.filename) as image_in_url,
	concat('https://geekseries.fr/sites/default/files/images_serie_grande/', image_out_url.filename) as image_out_url
from node
	
left outer join `field_data_field_resume_ficheserie` as resume on node.`nid` = `resume`.`entity_id`
left outer join `field_data_field_nom_originale` as origname on node.`nid` = `origname`.`entity_id`
left outer join `field_data_field_image_fiche_serie_in` as image_in on node.`nid` = `image_in`.`entity_id`
left outer join `file_managed` as image_in_url on `image_in`.`field_image_fiche_serie_in_fid` = `image_in_url`.`fid`
left outer join `field_data_field_image_fiche` as image_out on node.`nid` = `image_out`.`entity_id`
left outer join `file_managed` as image_out_url on `image_out`.`field_image_fiche_fid` = `image_out_url`.`fid`
where node.type='fiches_series' and node.status = 1;");

for ($series = array(); $tmp = $res->fetch_assoc();) $series[] = $tmp;

//Login to API
$curl = curl_init();

$credentials = new stdClass;
$credentials->email = $apiConfig->user->email;
$credentials->password = $apiConfig->user->password;

curl_setopt_array($curl, array(
  CURLOPT_PORT => $apiConfig->port,
  CURLOPT_URL => $apiConfig->endpoint."/api/v1/authenticate",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode($credentials),
  CURLOPT_HTTPHEADER => array(
    "content-type: application/json"
  ),
  CURLOPT_SSL_VERIFYHOST => false,
  CURLOPT_SSL_VERIFYPEER => false
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  $out .= "---";
  $out .= "cURL Error #:" . $err;
  $out .= "---";
} else {
  $jwtRes = json_decode($response);
  $jwt = $jwtRes->auth_token;
}
$outErr = "";
foreach($series as $serie) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_PORT => $apiConfig->port,
    CURLOPT_URL => $apiConfig->endpoint."/api/v1/series",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($serie),
    CURLOPT_HTTPHEADER => array(
        "authorization: Bearer ".$jwt,
        "content-type: application/json"
    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      $outErr .= $serie->title."\n";
	$out .= "---";
    	$out .= "cURL Error #:" . $err;
	$out .= "---";
    } else {
	$out .= "---";
    	$out .= $response;
	$out .= "---";
    }

}

$end = time();

$time = $end - $start;

$out.="<h3>$time sec</h3>";

file_put_contents(dirname(__FILE__)."/out.html", $out);
file_put_contents(dirname(__FILE__)."/outErr.log", $outErr);

