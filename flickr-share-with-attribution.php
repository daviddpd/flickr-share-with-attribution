<?php

$getFavs = 0;
$getSinglePhoto = 0;
$cid = -1;
$user_id = '';

if ($argc == 3 && in_array($argv[1], array('--id', '-id', ))) {
	$getSinglePhoto = 1;
	$cid = $argv[2];
} elseif ($argc == 3 && in_array($argv[1], array('--user', '-user', ))) {

	$getFavs = 1;
	$user_id = $argv[2];

} else {

	print "usage --id or --user required\n";
	exit;

}

require_once 'Phlickr/Api.php';
if ( is_file ("flickr-apikey.php") ) {
	include "flickr-apikey.php";
} else {
	define('FLICKR_API_KEY', '');
	define('FLICKR_API_SECRET', '');
	define('FLICKR_API_TOKEN', '');
}


// create an api
$api = new Phlickr_Api(FLICKR_API_KEY, FLICKR_API_SECRET, FLICKR_API_TOKEN);

$response = $api->executeMethod('flickr.photos.licenses.getInfo', array());
$licenses_xml = simplexml_load_string($response);
# print_r ( $licenses_xml );

$licenses_array = array();

foreach ($licenses_xml->licenses->{'license'} as $license ) {

	$id= (int)$license{'id'};

	$licenses_array[$id]['name'] = (string)$license{'name'};
	$licenses_array[$id]['url']  = (string)$license{'url'};
	
	if ( preg_match ( '/creativecommons/', $licenses_array[$id]['url']  )  ) {
		$a = explode  ( '/',  $licenses_array[$id]['url'] );
		$licenses_array[$id]['cc'] = '<a href="' . $licenses_array[$id]['url'] . '">CC ' .  strtoupper ( $a[4] . " " . $a[5] ) . "</a>";
	} else {
		$licenses_array[$id]['cc'] = '© ' . $licenses_array[$id]['name'] ;	
	}

}

$response = '';
$xml = '';
$photos = null;
$CreativeCommonsLicense = null;

if ( $getFavs ) {
	print " ==== $user_id \n";
	$response = $api->executeMethod('flickr.favorites.getList', array('user_id'=> "$user_id" ,'per_page' => 500));
	$xml = simplexml_load_string($response);
	$photos = $xml->photos->{'photo'};
}

if ( $getSinglePhoto ) {
	$photos = array ();
	$photos[0] = array  ( id => $cid );
}

foreach ($photos  as $photo) {
	$response = $api->executeMethod('flickr.photos.getInfo', array('photo_id'=> $photo{'id'}));
	$photo_xml = simplexml_load_string($response);

	$response = $api->executeMethod('flickr.photos.getSizes', array('photo_id'=> $photo{'id'}));
	$sizes_xml = simplexml_load_string($response);

#	print_r ($sizes_xml);
	
	$url = (string)$photo_xml->photo->urls->url;
	$owner_url = "https://www.flickr.com/people/" . $photo_xml->photo->owner{'nsid'};
	$owner_username = (string)$photo_xml->photo->owner{'username'};
	$title = (string)$photo_xml->photo->{'title'};
	$d = (string)$photo_xml->photo->{'description'};
	$license = (int)$photo_xml->photo{'license'};
#	print_r ( $photo_xml );
	

	if  ( ($license == 0) || ($license == 8) || ($license == 7) ) { 
		$CreativeCommonsLicense = false; 
	} else { 
		$CreativeCommonsLicense = true; 
	}

	$sizes_array = array ();
	
	foreach ($sizes_xml->sizes->{'size'} as $size ){
		$l = (string)$size{'label'};
		if ( isset ( $sizes_array{$l}{'url'} ) ) { continue; }
		
		$sizes_array{$l}{'url'} = (string)$size{'source'};
		$sizes_array{$l}{'width2'} = (int) ((int)$size{'width'} * 1.1 );
		$sizes_array{$l}{'height'} = (int)$size{'height'};
		$sizes_array{$l}{'height2'} = (int)((int)$size{'height'} * 1.1);
	
	}
	


if ( $CreativeCommonsLicense ) {
?>
<div class="flickrrow" style="height: <?= $sizes_array['Medium']['height2'] ?>px;">
	<div class="flickrimg" style="width: <?= $sizes_array['Medium']['width2'] ?>px;">
		<a target="_blank" href="<?= $url ?>"><img src="<?= $sizes_array['Medium']['url'] ?>" alt="<?= $title ." by " . $owner_username ?>" /></a>		
	</div>
	<div>
		<div class="flickrtitle"><a target="_blank" href="<?= $url ?>"><?= $title ?></a> by <a target="_blank" href="<?= $owner_url ?>"><?= $owner_username ?></a></div>
		<div class="flickrcredit">is licensed under <?= $licenses_array[$license]['cc']  ?> via Flickr</div>
		<div class="flickrdesc"><?= $d ?></div>
	</div>	
	<div style="clear: both">&nbsp;</div>
</div>
<div>&nbsp;</div>

<?php
} else {
?>
<div class="flickrrow" style="height: <?= $sizes_array['Square']['height'] ?>px;">
	<div>
		<div class="flickrtitle"><a target="_blank" href="<?= $url ?>"><?= $title ?></a> by <a target="_blank" href="<?= $owner_url ?>"><?= $owner_username ?></a></div>
		<div class="flickrcredit"><?= $licenses_array[$license]['cc']  ?>, please view the image on Flickr</div>
	</div>	
	<div style="clear: both">&nbsp;</div>
</div>
<div>&nbsp;</div>


<?php
} # if Creative Commons 

} # foreach photos

?>