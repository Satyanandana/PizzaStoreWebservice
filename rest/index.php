<?php 

$request_uri = $_SERVER['REQUEST_URI'];

// Get the document root
$doc_root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
$dirs = explode(DIRECTORY_SEPARATOR, __DIR__);
array_pop($dirs); // remove last element
$project_root = implode('/', $dirs) . '/';

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '0'); // would mess up response
ini_set('log_errors', 1);
// the following file needs to exist, be accessible to apache
// and writable (chmod 777 php-errors.log)
// Use an absolute file path to create just one log for the web app
ini_set('error_log', $project_root . 'php-server-errors.log');
set_include_path($project_root);
// app_path is the part of $project_root past $doc_root
$app_path = substr($project_root, strlen($doc_root));
// project uri is the part of $request_uri past $app_path, not counting its last /
$project_uri = substr($request_uri, strlen($app_path)-1);
error_log('project uri = '. $project_uri);
$parts = explode('/', $project_uri);
//like  /rest/product/1 ;
//    0    1     2    3    

// Get needed code
require_once('model/database.php');
require_once('model/pizza_restdb.php');

$server = $_SERVER['HTTP_HOST'];
$method = $_SERVER['REQUEST_METHOD'];
$proto = isset($_SERVER['HTTPS'])? 'https:':'http:';
$url  = $proto . '//' . $server . $request_uri;
$resource = trim($parts[2]);
$id = $parts[3];
error_log('starting REST server request, resource = '.$parts[3]. ' method='.$method);


switch ($resource) {
    // Access the specified product
    
        case 'day': 
        error_log('request at case day');
        switch ($method) {
            case 'GET':
                // TODO: get current day from DB
                //$day = 6;
                
                handle_get_day();
                break;
            case 'POST':
                //TODO: set new day in DB
                handle_post_day();
                break;
            default:
                $error_message = 'bad HTTP method : ' . $method;
                include_once('errors/server_error.php');
                server_error(405, $error_message);
                break;
        }
        break;
        case 'orders':
        error_log('request at case orders');
        switch ($method) {
            case 'GET':
              
                // TODO: get full information, including status of a supply order (i.e., delivered or not)
                handle_get_orders($id);
                //return $order_details;
                break;
            case 'POST':
                handle_post_orders($url);
                // TODO: creates a new supply order (flour, cheese), returns new URI
                break;
            default:
                $error_message = 'bad HTTP method : ' . $method;
                include_once('errors/server_error.php');
                server_error(405, $error_message);
                break;
        }
        break;
        default:
            $error_message = 'Unknown REST resource: ' . $resource;
            include_once('errors/server_error.php');
            server_error(400, $error_message);
        break;
}



function handle_get_day() {
    $day = get_system_day();
    error_log('rest server in handle_get_day, day = '. $day);
    echo $day['current_day'];
}

function handle_post_day() {
    error_log('rest server in handle_post_day');
    $day = file_get_contents('php://input');  // just a digit string
    error_log('Server saw POSTed day = ' . $day);
    //if $day = 0 then reinitialize the orders
    if ($day == '0')
    {
        //delete the orders and orderitems
        //set the autoincrement value of table orders back to 0 
        //so that the first order id will be 1
        reinitialize_orders();
    }
    else {
          update_system_day($day);
    }
}

function handle_get_orders($order_id)
{
    
    $order = get_order($order_id);
    // TODO: get full information, including status of a supply order (i.e., delivered or not)
    // tables: customers, orderItems, orders, array(itemID, productID, quantity)
    
    $current_day = get_system_day();
 
    //show delivery status as "true" or "false"
    if ($order['deliveryday'] <= $current_day['current_day'])
    {
        $order['status'] = 1;
    }
    else if ($order['deliveryday'] > $current_day['current_day']) 
    {
        $order['status'] = 0;
    }
    
    $data = json_encode($order);
    error_log('hi from handle_get_orders');
    error_log('data after getting order: '.$data);
    echo $data;
}

function handle_post_orders($url)
{
    // TODO: creates a new supply order (flour, cheese), returns new URI
    $bodyJson = file_get_contents('php://input');
    error_log( 'Server saw post data' . $bodyJson);
    $body = json_decode($bodyJson, true);
    try {
        
        $customerID=$body[0]['customerID'];
        $currentDay = get_system_day();
        error_log('$currentDay = ' . $currentDay['current_day']);
      //  $deliveryDay = $currentDay['current_day']+ rand(1,2);
        $deliveryDay = rand($currentDay['current_day'] + 1, $currentDay['current_day'] + 2);
        $status=0;
        
        error_log('currentday = ' . $currentDay['current_day']);
        error_log('deliveryday = ' . $deliveryDay);
        error_log('supplierID = ' . $body[0]['customerID']);
        error_log('flourID = ' . $body[1][0]['productID']);
        error_log('cheeseID = ' . $body[1][1]['productID']);
        error_log('quantity of flour = ' . $body[1][0]['quantity']);
        error_log('quantity of cheese = ' . $body[1][1]['quantity']);
        
        
       
        $orderID = add_supply_order($customerID,$currentDay['current_day'],$deliveryDay,$body[1][0]['quantity'],$body[1][1]['quantity'],$status);
      
        error_log('orderId = ' . $orderID);
        //post_orderID($orderID);
        
        // return new URI in Location header
        //ob_start();
        $locHeader = 'Location: ' . $url . '/'.$orderID;
        header('Content-type: application/json');
        header($locHeader, true, 201);
        error_log('hi from handle_post_orders, header = ' . $locHeader);
    } catch (PDOException $e) {
        $error_message = 'Insert failed: ' . $e->getMessage();
        include_once('errors/server_error.php');
        server_error(400, $error_message);
    }
}

?>