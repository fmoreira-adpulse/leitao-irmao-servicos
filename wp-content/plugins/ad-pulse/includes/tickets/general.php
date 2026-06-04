<?php

function load_associated_order($value) {
    return isset($_GET['order_id']) && !empty($_GET['order_id'])? $_GET['order_id'] : $value;
}

add_filter('acf/load_value/name=wcsts_associated_order', 'load_associated_order', 10, 3);