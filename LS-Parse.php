Almost out of storage … If you run out, you can't create, edit, and upload files.
<?php
# include basic wordpress functions
include("/home/user/myprivatedomain.com/wp-load.php");
	
include("../wp-config.php");
	
# database connection string for Wordpress backend

include("/home/user/myprivatedomain.com/db-conn.php");

########################functions used in the scope of this script


# a send email function

function sendHTMLemail($HTML,$from,$to,$subject)
{
# First we have to build our email headers
# Set out "from" address

    $headers = "From: $from\r\n"; 

# Now we specify our MIME version

    $headers .= "MIME-Version: 1.0\r\n"; 

# Create a boundary so we know where to look for
# the start of the data

    $boundary = uniqid("HTMLEMAIL"); 
    
#For a non-html version of our email
    
    $headers .= "Content-Type: multipart/alternative;".
                "boundary = $boundary\r\n\r\n"; 

    $headers .= "This is a MIME encoded message.\r\n\r\n"; 

    $headers .= "--$boundary\r\n".
                "Content-Type: text/plain; charset=ISO-8859-1\r\n".
                "Content-Transfer-Encoding: base64\r\n\r\n"; 
                
    $headers .= chunk_split(base64_encode(strip_tags($HTML))); 

#The HTML version

    $headers .= "--$boundary\r\n".
                "Content-Type: text/html; charset=ISO-8859-1\r\n".
                "Content-Transfer-Encoding: base64\r\n\r\n"; 
                
    $headers .= chunk_split(base64_encode($HTML)); 

#And then send the email ....

    mail($to,$subject,"",$headers);
    
}
 
# end check email function 
 
# function to check for even number 
 
function checkNum($num){
	return ($num%2) ? TRUE : FALSE;
}

# end even number function


#function to get the redirected url

/**
 * get_redirect_url()
 * Gets the address that the provided URL redirects to,
 * or FALSE if there's no redirect. 
 *
 * @param string $url
 * @return string
 */
 
 
function get_redirect_url($url){
	$redirect_url = null; 
 
	$url_parts = @parse_url($url);
	if (!$url_parts) return false;
	if (!isset($url_parts['host'])) return false; //can't process relative URLs
	if (!isset($url_parts['path'])) $url_parts['path'] = '/';
 
	$sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
	if (!$sock) return false;
 
	$request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n"; 
	$request .= 'Host: ' . $url_parts['host'] . "\r\n"; 
	$request .= "Connection: Close\r\n\r\n"; 
	fwrite($sock, $request);
	$response = '';
	while(!feof($sock)) $response .= fread($sock, 8192);
	fclose($sock);
 
	if (preg_match('/^Location: (.+?)$/m', $response, $matches)){
		if ( substr($matches[1], 0, 1) == "/" )
			return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[1]);
		else
			return trim($matches[1]);
 
	} else {
		return false;
	}
 
}


# function to get all redirects, we'll use only the last one later 
/**
 * get_all_redirects()
 * Follows and collects all redirects, in order, for the given URL. 
 *
 * @param string $url
 * @return array
 */
function get_all_redirects($url){
	$redirects = array();
	while ($newurl = get_redirect_url($url)){
		if (in_array($newurl, $redirects)){
			break;
		}
		$redirects[] = $newurl;
		$url = $newurl;
	}
	return $redirects;
}


# function to get fianl redirected URL
 
/**
 * get_final_url()
 * Gets the address that the URL ultimately leads to. 
 * Returns $url itself if it isn't a redirect.
 *
 * @param string $url
 * @return string
 */
function get_final_url($url){
	$redirects = get_all_redirects($url);
	if (count($redirects)>0){
		return array_pop($redirects);
	} else {
		return $url;
	}
}


##################################### end functions

 
#delete expired offers

#loop through post ids and delete them

$sql = "SELECT * FROM wp_posts WHERE post_author = '3'";

//echo $sql;

$sql_result = mysql_query($sql,$wp_conn) or die ("Couldn't execute select $sql query." );

	while($row = mysql_fetch_array($sql_result)) {

	//link up to category

	$post_id = $row["ID"];
	//echo "post deleted";

	#now we have the IDs of posts for deletion, delete them with the WP function
	wp_delete_post( $post_id, 1 );
	//print_r($row);


}


//die;

#end delete expired offers

#set up datafeed and send run through xml parser
 
$file = 'http://couponfeed.linksynergy.com/coupon?token=bf1bdf8a0afc2957a16cd58509040ced367497d2464346842fb7aa66925674d6';
$req = file_get_contents($file);

#use built in xml parser to begin parsing
$xml = simplexml_load_string($req);
$gotcha = $xml->link[0];
$gotcha2 = $xml->link[1];


#get a total to check for null, empty

$total = count($xml);

//echo "TOTAL:$total";




######################################### a sample of the data structure we are parsing for reference

/*

["offerstartdate"]=>
      string(10) "2010-02-01"
      ["offerenddate"]=>
      string(10) "2013-04-01"
      ["couponrestriction"]=>
      string(19) " New Customers Only"
      ["clickurl"]=>
      string(95) "http://click.linksynergy.com/fs-bin/click?id=uus6M8Kzh4Q&offerid=215687.10000335&type=3&subid=0"
      ["impressionpixel"]=>
      string(88) "http://ad.linksynergy.com/fs-bin/show?id=uus6M8Kzh4Q&bids=215687.10000335&type=3&subid=0"
      ["advertiserid"]=>
      string(4) "2762"
      ["advertisername"]=>
      string(13) "drugstore.com"
      ["network"]=>
      string(17) "LinkShare Network"
	  
	  
	 196 	1 	2010-01-04 10:58:51 	2010-01-04 10:58:51 	  	a1 books Coupon 	Save 10% on purchase of $50 or above.Valid for Ele... 	publish 	open 	open 	  	a1-books-coupon 	  	  	2010-01-04 11:06:55 	2010-01-04 11:06:55 	  	0 	?p=196 	0 	post 	  	0
	 
	 
	 */
	 
################################### end data sample
#we need to only continue if xml data is not null.  This will skip null or incomplete records

#total is greater than 1, now iterate through each instance for saving to DB

if ($total > 1) {

	for ($x=0;$x<$total;$x++) {


	#reset term_id each iteration through loop
	$term_id = "";


	$gotcha3[$x] = $xml->link[$x];

	$categories = $xml->link[$x]->categories->category[0];

	$categories2 = $xml->link[$x]->categories->category[1];

	$categories3 = $xml->link[$x]->categories->category[2];

	$promotiontype = $xml->link[$x]->promotiontypes->promotiontype[0];

	$offerdescription = addslashes($xml->link[$x]->offerdescription);

	$offerstartdate = $xml->link[$x]->offerstartdate;

	$offerenddate = $xml->link[$x]->offerenddate;

	$couponrestriction = addslashes($xml->link[$x]->couponrestriction);

	$clickurl = $xml->link[$x]->clickurl;

	$impressionpixel = $xml->link[$x]->impressionpixel;

	$advertiserid = $xml->link[$x]->advertiserid;

	$impressionpixel = $xml->link[$x]->impressionpixel;

	$advertisername = $xml->link[$x]->advertisername;

	$advertisernetwork = $xml->link[$x]->network;


#check for offer end date = ongoing

		if ($offerenddate == "ongoing") {

#if offer is ongoing set endate to 2099
			$offerenddate = "2099-12-31";

		}


#build query and insert data into mysql

#The data feed has incomplete records in some instances, we don't want them here, we use the advertiser name field as the criteria  

#Do not insert data if advertiser name field is null.  
  
			if ($advertisername <> "") {
	
#calculate a simple running total for debugging and output reporting

				$z = $z + 1;

//echo "$z posts inserted <br></br>";


#In future cases if something changes with wp_insert_post function, use this SQL to insert records the old fashioned way.  We don't need it now.  Hopefully never will.
# No need to be concerned about SQL injection here since data input is from a trusted feed source

#construct array to pass to wp_insert_post

				$my_post = array();
				$my_post['post_title'] = $advertisername.' - '.$promotiontype;
				$my_post['post_content'] = $offerdescription;
				$my_post['post_excerpt'] = $offerdescription;
				$my_post['post_status'] = 'publish';
				$my_post['post_author'] = 3;
				//$my_post['post_category'] = array($term_id[$xx]);
				//$my_post['post_category'] = array(1);
				$my_post['post_type'] = 'post';
				$my_post['post_date'] = date('Y-m-d H:i:s');
				$my_post['tags_input'] = 'coupons for, coupon for, coupons online, coupon codes';


#Insert the post into the database, wp_insert_post returns ID of post

				$post_id = wp_insert_post($my_post, true);


#Add the correct category data while we have each post id
				$category_ids = @array_map('intval', $category_ids);

				wp_set_object_terms( $post_id, $categories, 'category');



#We need to ID of last inserted post

				$current_id = mysql_insert_id();


				$master_current_id = mysql_insert_id();

# need to save redirect

				$rez = get_all_redirects($clickurl);

				$last = $rez[0];


#get domain of URL
				$parse = parse_url($last);




				$last2 = $parse['host']; // prints 'google.com'

				$display = substr($last2, 4); 

				$last3 = "http://".$last2;



#save redirect to database
				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'redirect','$last3')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

# end save redirect


#maintain post meta values.  Built in WP functions are throwing errors, so we do it the old fashioned way


				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'url','$last')";

#execute sql
				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'code','Click For Coupon')";

#execute sql

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'link','$clickurl')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'conditions','$couponrestriction')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'pixel','$impressionpixel')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");


				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'expires','$offerenddate')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'promo','$promotiontype')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'featured','no')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'type','coupon')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'packageID', '1')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");


				
#generate random numbers for views so they don't appear as 0 to website users
				srand ((double) microtime( )*100000);
				$random_number = rand(0,1000);

#insert random number to post meta for view count
				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, 'hits','$random_number')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

#data for all in one seo plugin

# some plugin fields: _aioseop_keywords, _aioseop_title, _aioseop_description

				$title = $my_post['post_title'];

				$description = $my_post['post_content'];

				$keywords = "$title, online coupons, coupons online, coupon for, coupon code, promotion code";

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, '_aioseop_title','$title')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, '_aioseop_description','$description')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

				$q = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, '_aioseop_keywords','$keywords')";

				$q_result = mysql_query($q,$wp_conn) or die ("Couldn't execute query. $q.");

#another simple counter for debugging purposes
$f = $f + 1;

									}



								}


$wp_post_id_sql = "SELECT * FROM wp_posts WHERE post_author = '3' ORDER BY RAND() LIMIT 15";

$wp_post_id_sql_result = mysql_query($wp_post_id_sql,$wp_conn) or die ("Couldn't execute filter select $wp_post_id_sql query." );

	while($row2 = mysql_fetch_array($wp_post_id_sql_result)) {

		$wp_post_id = $row2["ID"];

		$featured = $featured . "," . $wp_post_id;

	}


##########################################

#Correct counts for comments and categories.

#Table prefix variable stored in WP config

	  $result = mysql_query("SELECT term_taxonomy_id FROM ".$table_prefix."term_taxonomy");
		while ($row = mysql_fetch_array($result)) {
		$term_taxonomy_id = $row['term_taxonomy_id'];
		//echo "term_taxonomy_id: ".$term_taxonomy_id." count = ";
		$countresult = mysql_query("SELECT count(*) FROM ".$table_prefix."term_relationships WHERE term_taxonomy_id = '$term_taxonomy_id'");
		$countarray = mysql_fetch_array($countresult);
		$count = $countarray[0];
		//echo $count."<br />";
		mysql_query("UPDATE ".$table_prefix."term_taxonomy SET count = '$count' WHERE term_taxonomy_id = '$term_taxonomy_id'");
		}

$result = mysql_query("SELECT ID FROM ".$table_prefix."posts");
		while ($row = mysql_fetch_array($result)) {
		$post_id = $row['ID'];
		//echo "post_id: ".$post_id." count = ";
		$countresult = mysql_query("SELECT count(*) FROM ".$table_prefix."comments WHERE comment_post_ID = '$post_id' AND comment_approved = 1");
		$countarray = mysql_fetch_array($countresult);
		$count = $countarray[0];
		//echo $count."<br />";
  		mysql_query("UPDATE ".$table_prefix."posts SET comment_count = '$count' WHERE ID = '$post_id'");
		}
		
		
#grab a few random posts, make featured (to do later)

##########################

#email results


					$email2 = "admin@myprivatedomain.com";

					$subject = "Private Domain Cron Success";

					$from = "Private Domain Cron Job";

					$theData = "Private Domain Cron Has Completed Successfully";

					sendHTMLemail($theData,$from,$email2,$subject);

# count is less than one

					}else{
	
					//send email to admin indicating failure	
	
	
					$subject = "Private Domain cron EPIC FAIL";
					$theData = "Private Domain cron EPIC FAIL";
					sendHTMLemail($theData,$from,$email2,$subject);

}
?>
