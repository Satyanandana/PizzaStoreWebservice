<?php
// Curl calls for REST web services needing at most one Authorization header
// and no other special headers
// Headers this handles:
//  One Authorization header, passed in parameter $auth_header
//  Content-type for POST, Accept for GET, via data_type argument
//  Note that Content-length is added by curl itself
//  Curl uses Accept: */* if data_type is null on GET


// No response headers requested, returned response is just the body text
// for GET/POST, to get/send JSON use $data_type 'application/json'
// $auth_header example: 'Authorization: Bearer <token>', with token filled in
function curl_request($url, $method, $postdata = null, $data_type = null,
        $auth_header = null) 
{
    $curl_info = null;
    return curl_request_internal($url, $method, $postdata, $data_type,
            $auth_header, false, $curl_info);
}

// usually, for POST, get back response's Location header value as well as response body
// assumes JSON to send in $postdata
// $auth_header example: 'Authorization: Bearer <token>', with token filled in
function curl_request_get_location($url, $method, $postdata, $data_type,
        &$location, $auth_header = null)
{
    $curl_info = null;
    $raw_response = 
            curl_request_internal($url, $method, $postdata, $data_type, $auth_header, true, $curl_info);
    $response = split_response($raw_response, $curl_info, $location);
    return $response;
}

// For curl requests for web services, get back response and curl_info
// if $with_response_headers === true, returns headers in response text
//   use split_response to get Location header value, body separately
//   However, lose verbose info. can get outgoing headers with 
// for GET, $data_type is used for the Accept header
// $auth_header example: 'Authorization: Bearer <token>', with token filled in
// for POST, use postdata for params 'x=10&y=2', etc. or an assoc array,
//   or JSON or XML data, in which case specify $data_type as well (for 
//   Content-type header on request
// If SSL is in use (https: URL) this code expects a CA certicates file 
//    at htdocs/ca-bundle.cer. See http://curl.haxx.se/docs/sslcerts.html
function curl_request_internal($url, $method, $postdata, $data_type, 
        $auth_header, $with_response_headers, &$curl_info) 
{
    // echo '<br>curl_request_internal: url ='. $url.' method= '.$method.'$postdata = '.$postdata;
    $ch = curl_init($url);
    $verbose_file = null;
    if (!$with_response_headers) {
        // for verbose report if error occurs-- should drop this for production code
        // (doesn't work if $with_response_headers is true, but then another 
        //   way exists, for request)
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose_file = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose_file);
    }

    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($with_response_headers) {    // return headers as text in returned response
        curl_setopt($ch, CURLOPT_HEADER, true);  // return header info
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // return headers in response data
    }
  
    if ($auth_header) {
        $request_headers = array($auth_header);
    } else {
        $request_headers = array();
    }
    if ($method === 'GET') { // method names must be in caps
        if ($data_type) {  // but header names are case-insensitive
            $request_headers[] = 'Accept: ' . $data_type;
        }
    } elseif ($method === 'POST') {  
        curl_setopt($ch, CURLOPT_POST, 1); 
        if ($postdata) {
            // Note that curl sets the Content-Length header
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            if ($data_type){ // if null, let curl set this header
                $request_headers[] = 'Content-Type: ' . $data_type;        
            }
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    if (count($request_headers) > 0) {
 //       foreach($request_headers as $aheader) {
 //           echo '<br>adding header: '. $aheader;
 //      }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
    }
    if (stripos($url, 'https') !== false) {  // case-insensitive
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        $certs_file = $_SERVER['DOCUMENT_ROOT'] . '/ca-bundle.cer';
        curl_setopt($ch, CURLOPT_CAINFO, $certs_file);
    }
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_message = 'Curl error: ' . curl_error($ch);
        verbose_debug_report($ch, $verbose_file);
        curl_close($ch);
        throw new Exception($error_message);
    }
    $curl_info = curl_getinfo($ch);
 //   verbose_debug_report($ch, $verbose_file);
    curl_close($ch);
    return $response;  // contains headers if $withHeaders is true
}
// Output verbose report on request and response done by curl
// To be called after curl_exec returns
function verbose_debug_report($ch, $verbose_file = null) {
    $curl_info = curl_getinfo($ch);
    if (isset($curl_info['request_header'])) {
        // Existence of 'request_header' means output response headers were
        //  requested and no verbose report is available
        // with output headers, can get request headers this way;
        echo "<br>Request information: " . htmlspecialchars($curl_info['request_header']);
      
    } else if ($verbose_file != NULL) {
        rewind($verbose_file);
        $verboseLog = stream_get_contents($verbose_file);
        echo "<br>Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
    }
}

// for more general handling of header values, see PHP doc for http_parse_headers
function split_response($raw_response, $info, &$location) {
    $header = substr($raw_response, 0, $info['header_size']);
    // header has line "Location: <value>" terminated by \n
    $i = strpos($header, 'Location:');
    $s = substr($header, $i + strlen('Location: ')); // so $s starts with value
    $j = strpos($s, "\n"); // find \n: use double quotes here to get \n to work
    $location = substr($s, 0, $j);  // extract value        
    $response = substr($raw_response, $info['header_size']); // body
    return $response;
}
?>
