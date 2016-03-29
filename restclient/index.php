<?php
// Client-side REST
require_once('../util/main.php');
require_once('curl_helper.php');
// This is the URL of the REST service: needs to be
//  changed if this code is used in pizza2
$base_url = $_SERVER['SERVER_NAME']. $app_path . 'rest';
// echo '<br>include path = ' . get_include_path();
$error = null;
$day = 3;
$url = $base_url . '/day/';
echo '<br>posting day = 3 to '.$url;
$location = client_post_day($day,$error_message);
$message = $error_message != null? $error_message: 'Success';
?>
<br> Post of day result: <?php echo $message?>
<?php
echo '<br>GET of day to '.$url;
error_log('......starting client: GET day');
$day1 = client_get_day($url, $error);
echo '<br>Back from GET: day= '. $day1 . ' (wrong until coded right)';

$product_id = 1;
$url = $base_url . '/products/' . $product_id;
$product = client_get_product($url, $error);
echo '<br> Returned result of GET of product 1: ';
print_r($product);
?>

<br> Now post it back, but change productCode to avoid uniqueness constraint </p>
<?php
$product['productCode'] = 'strat71';  // works only once per each value
$error_message = null;
$location = client_post_product($product, $error_message);
$message = $location ? $location : $error_message;
?>
<br> New location/error: <?php echo $message?>
<br> If error is 400, probably because of constraint violation on unique column productCode
<br> Need to change productCode from strat71 in the above code
 
<?php
function client_get_product($url, &$error_message) {
    try {
        // works without specifying JSON since no type check on receipt
        $productJson = curl_request($url, 'GET', null, 'application/json');
        $error_message = null;
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
        return null;
    }
    // decode to assoc array, what is normally used for $product in this app
    // without the flag, we would get "stdObject"
    $product = json_decode($productJson, /* assoc */ true);
    return $product;
}

// POST to /product/, get back new relative URI in $location, return new full URI
function client_post_product($product, &$error_message) {
    global $base_url;
    $data_string = json_encode($product);
    $location = null;
    try {
        curl_request_get_location($base_url . '/product/', 'POST',
                $data_string,'application/json', $location);
        $error_message = null;
    } catch (Exception $e) {
        error_log('error in client_post_product' . $e->getMessage());
        $error_message = 'Error: ' . $e->getMessage();
        return null;
    }
    // echo '<br>Location = ' . $location;
    return ($location);
}
function client_get_day($url, &$error_message) {
    try {
        // works without specifying JSON since no type check on receipt
        $day = curl_request($url, 'GET', null, 'application/json');
        $error_message = null;
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
        return null;
    }
  
    return $day;
}

// POST to /day, no new Location to report back
function client_post_day($day, &$error_message) {
    global $base_url;
  
    $location = null;
    try {
        curl_request_get_location($base_url . '/day/', 'POST',
                $day,'application/json', $location);
        $error_message = null;
    } catch (Exception $e) {
        error_log('error in client_post_day' . $e->getMessage());
        $error_message = 'Error: ' . $e->getMessage();
    }
}
