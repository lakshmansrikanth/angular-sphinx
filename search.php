
<?php
// The request is a JSON request.
// We must read the input.
// $_POST or $_GET will not work!

$data = file_get_contents("php://input");

$objData = json_decode($data);

require("sphinxapi.php"); //sphinx api

$CONF = array();
$CONF['sphinx_host'] = '127.0.0.1'; //connecting to sphinx server
$CONF['sphinx_port'] = 9312; //default sphinx port
$CONF['sphinx_index'] = "dist"; //sphinx indexes for searchd to look for
$CONF['page_size'] = 10; // no of results per page 
$CONF['max_matches'] = 1000; //max matches as in Google

#Change this to FALSE on a live site!
$CONF['debug'] = TRUE;
	
$q = $objData->data;  //get the input

$cl = new SphinxClient(); 
$cl->SetServer($CONF['sphinx_host'], $CONF['sphinx_port']);
$cl->SetMatchMode(SPH_MATCH_EXTENDED);
$cl->SetLimits(0,$CONF['page_size']);

//query using extended query operators refer http://sphinxsearch.com/docs/current.html#extended-syntax
$query = '@* ' . '"^' . $q .'" /1'; 
//$query = '@source ' . '"^' . $q . '* | '.$q.'*" /1';

$res = $cl->Query($query, $CONF['sphinx_index']); //results array

//Check for failure
if (empty($res)) {
	print "Query failed: -- please try again later.\n";
	if ($CONF['debug'] && $cl->GetLastError()) {
		print "<br/>Error: ".$cl->GetLastError()."\n\n";
	}
	return;
} else {
	//Get total no of results and time taken, total pages
	if ($CONF['debug'] && $cl->GetLastWarning()) {
		return print "<br/>WARNING: ".$cl->GetLastWarning()."\n\n"; 
	}
	$query_info = "Query '".htmlentities($q)."' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
	$resultCount = $res['total_found'];
	$numberOfPages = ceil($res['total']/$CONF['page_size']);
}

if (is_array($res["matches"])) {
	//Build a list of IDs for use in the mysql Query and looping though the results
	$ids = array_keys($res["matches"]);
} else {
	return print "No Results for ".htmlentities($q);
}

if (!empty($ids)) {
	return print_r($ids);
}
else {
	return print "No Results for ".htmlentities($q);
}

?>
