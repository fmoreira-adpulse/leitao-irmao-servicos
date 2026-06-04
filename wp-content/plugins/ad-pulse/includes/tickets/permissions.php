<?php

function check_user_is_ticket_creator() {
    $logged_user = wp_get_current_user();
    $ticket = get_post($_POST['ticket_id']);
	$ticket_meta = get_post_meta($_POST['ticket_id']);
    $ticket_users = get_post_meta($_POST['ticket_id'], 'wcsts_manager_user_id');

	error_log("Ticket's meta data: " . json_encode($ticket_meta));
    error_log("Users managing this ticket: " . json_encode($ticket_users));

    $is_admin = in_array('administrator', $logged_user->roles);
    $is_ticket_author = intval($ticket->post_author) == $logged_user->ID;
    $is_not_ticket = $ticket->post_type != 'wcsts_ticket';
    $is_in_managing_users = in_array($logged_user->ID, $ticket_users);

    echo $is_admin || $is_ticket_author || $is_not_ticket || $is_in_managing_users;
    wp_die();
}

add_action('wp_ajax_user_is_ticket_creator', 'check_user_is_ticket_creator');