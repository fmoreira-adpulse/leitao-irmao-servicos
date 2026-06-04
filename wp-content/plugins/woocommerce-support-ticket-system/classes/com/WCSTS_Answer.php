<?php 
class WCSTS_Answer
{
	function __construct()
	{
		add_action( 'init', array(&$this, 'register_custom_post_type'), 0 );
		
		add_action('wp_ajax_wcsts_get_predefined_answers_list', array(&$this, 'ajax_get_predefined_answers_list'));
		add_action('wp_ajax_wcsts_get_answer', array(&$this, 'ajax_wcsts_get_answer'));
	}
	function register_custom_post_type() 
	{
		$labels = array(
			'name'                => _x( 'Answer', 'Ticket', 'woocommerce-support-ticket-system' ),
			'singular_name'       => _x( 'Answer', 'Ticket', 'woocommerce-support-ticket-system' ),
			'parent_item_colon'   => __( 'Parent Item:', 'woocommerce-support-ticket-system' ),
			'all_items'           => __( 'Predefined answers', 'woocommerce-support-ticket-system' ),
			'add_new_item'        => __( 'Add Answer', 'woocommerce-support-ticket-system' ),
			'add_new'             => __( 'Add Answer', 'woocommerce-support-ticket-system' ),
			'new_item'            => __( 'New Answer', 'woocommerce-support-ticket-system' ),
			'edit_item'           => __( 'Edit Answer', 'woocommerce-support-ticket-system' ),
			'update_item'         => __( 'Update Answer', 'woocommerce-support-ticket-system' ),
			'view_item'           => __( 'View Answer', 'woocommerce-support-ticket-system' ),
			'search_items'        => __( 'Search Answer', 'woocommerce-support-ticket-system' ),
			'not_found'           => __( 'Not found', 'woocommerce-support-ticket-system' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'woocommerce-support-ticket-system' ),
		);
		$args = array(
			'label'               => __( 'Predefined Answer', 'woocommerce-support-ticket-system' ),
			'description'         => __( 'Predefined Answers', 'woocommerce-support-ticket-system' ),
			'labels'              => $labels,
			'supports'            => array('editor', 'title', 'author' ),
			'taxonomies'          => array( /*'category' , 'post_tag' */ ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,                                     
			'show_in_menu'        => 'edit.php?post_type=wcsts_ticket',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,		
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		);
		register_post_type( 'wcsts_predef_answer', $args );
		flush_rewrite_rules();
		
	}
	
	function ajax_get_predefined_answers_list()
	{
		$resultCount = 50;
		$search_string = isset($_GET['search_string']) ? $_GET['search_string'] : null;
		$page = isset($_GET['page']) ? $_GET['page'] : null;
		$offset = isset($page) ? ($page - 1) * $resultCount : null;
		$answers_list = $this->get_answers_list($search_string ,$offset, $resultCount);
		echo json_encode( $answers_list); 
		wp_die();
	}
	function ajax_wcsts_get_answer()
	{
		$answer_id = wcsts_get_value_if_set($_POST, 'id', "");
		$result = $answer_id != "" ? get_post_field('post_content', $answer_id) : "";
		echo $result;
		wp_die();
	}
	function get_answers_list($search_string ,$offset, $resultCount)
	{
		global $wpdb, $wcsts_wpml_helper;
		 $query_select_string = "SELECT answers.ID as id, answers.post_parent as product_parent, answers.post_title as title";
		 $query_select_count_string = "SELECT COUNT(*) as tot";
		 $query_from_string = " FROM {$wpdb->posts} AS answers
								 WHERE  (answers.post_type = 'wcsts_predef_answer' AND answers.post_status = 'publish')
								";
		if($search_string)
				$query_from_string .=  " AND ( answers.post_title LIKE '%{$search_string}%'  OR answers.ID LIKE '%{$search_string}%' ) 
										AND (answers.post_type = 'wcsts_predef_answer') "; //Why?
		
		$final_query_string =  $query_select_string.$query_from_string." GROUP BY answers.ID LIMIT {$offset}, {$resultCount}";
		
		$result = $wpdb->get_results($final_query_string ) ;
		
		//No need
		/* if($wcsts_wpml_helper->wpml_is_active())
		{
			$product_ids = $variation_ids = array();
			foreach($result as $product)
			{
				if($product->product_parent == 0 )
					$product_ids[] = $product;
				else
					$variation_ids[] = $product;
			}
			
			
			//Filter answers
			if(!empty($product_ids))
				$product_ids = $wcsts_wpml_helper->remove_translated_id($product_ids, 'product', true);
			
			//Filter variations
			if(!empty($variation_ids))
				$variation_ids = $wcsts_wpml_helper->remove_translated_id($variation_ids, 'product', true);
			
			$result = array_merge($product_ids, $variation_ids);
		} */
		
		
		if(isset($offset) && isset($resultCount))
		{
			$num_order = $wpdb->get_col($query_select_count_string.$query_from_string);
			$num_order = isset($num_order[0]) ? intval($num_order[0]) : 0;
			$endCount = $offset + $resultCount;
			$morePages = empty($result) ? false : $num_order > $endCount;
			$results = array(
				  "results" => $result,
				  "pagination" => array(
					  "more" => $morePages
				  )
			  );
		}
		else
			$results = array(
				  "results" => $result,
				  "pagination" => array(
					  "more" => false
				  )
			  );
		return $results;
	}
}
?>