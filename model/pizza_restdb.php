<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function update_system_day($day)
{
    global $db;
    $query = 'UPDATE system_day SET current_day = :day';
    $statement = $db->prepare($query);
    $statement->bindValue(':day', $day);
    $statement->execute();
    $statement->closeCursor();
}

function get_system_day()
{
    global $db;
    $query = 'select * from system_day';
    $statement = $db->prepare($query);
    $statement->execute();
    $current_day = $statement->fetch();
    $statement->closeCursor();
    return $current_day; 
    
}
function add_supply_order($customerID,$orderday,$deliveryday,$flour_qty,$cheese_qty,$status){
    global $db;
  
    $query = 'INSERT INTO supply_orders (customerID,orderday,deliveryday,flour_qty,cheese_qty,status)
         VALUES (:customerID,:orderday,:deliveryday,:flour_qty,:cheese_qty,:status)';
    $statement = $db->prepare($query);
    
    $statement->bindValue(':customerID',$customerID);
    $statement->bindValue(':orderday',$orderday);
    $statement->bindValue(':deliveryday',$deliveryday);
    $statement->bindValue(':flour_qty',$flour_qty);
    $statement->bindValue(':cheese_qty',$cheese_qty);
    $statement->bindValue(':status', $status);
    
    $statement->execute();
    $order_id = $db->lastInsertId(); 
    $statement->closeCursor();
    return $order_id;
    
}
function get_order($order_id) {
    global $db;
    $query = 'SELECT * FROM supply_orders WHERE orderID = :order_id';
    $statement = $db->prepare($query);
    $statement->bindValue(':order_id', $order_id);
    $statement->execute();
    $order = $statement->fetch();
    $statement->closeCursor();
    return $order;
}
function reinitialize_orders()
{
    global $db;
    $query='truncate table supply_orders;';
    $query.='UPDATE system_day SET current_day = 1;';
    $statement = $db->prepare($query);
    $statement->execute(); 
    $statement->closeCursor();
}


