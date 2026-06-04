<?php 
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class WCSTS_ManageTicketVisibilityPage extends WP_List_Table()
{
	public function __construct()
	{
		 parent::__construct( array(
            'singular'  => 'ticket_visibility',     
            'plural'    => 'tickets_visibility',    
            'ajax'      => false        
        ) );
	}
	function column_default($item, $column_name)
	{
		 switch($column_name)
		 {
            case 'ID':
            case 'login':
            case 'name':
            case 'surname':
            case 'email':
            case 'roles':
            case 'visibility':
                return $item[$column_name];
            default:
			    $result = apply_filters('manage_ticket_visibility_custom_column', null, $column_name, $item["ID"] );
                return isset($result) ? $result : esc_html__('N/A', 'woocommerce-support-ticket-system');//print_r($item,true); //Show the whole array for troubleshooting purposes
        }
	}
	function column_cb($item)
	{
        return sprintf(
            '<input type="checkbox"  name="%1$s[]" value="%2$s" />', 
             $this->_args['singular'],  
             $item['ID']               
        );
    }
	function get_columns()
	{
        $columns = array(
            'cb'        => '<input type="checkbox" />', 
			'ID'     => 'ID',
			'login'     => esc_html__('Login', 'woocommerce-customers-manager'),
            'name'     => esc_html__('Name', 'woocommerce-customers-manager'),
            'surname'     => esc_html__('Surname', 'woocommerce-customers-manager'),
            'email'     => esc_html__('Email', 'woocommerce-customers-manager'),
            'roles'     => esc_html__('Role(s)', 'woocommerce-customers-manager'),
			'visibility' => esc_html__('Visibility', 'woocommerce-customers-manager')
        );
		
		$columns = apply_filters('manage_ticket_visibility_columns', $columns);
        return $columns;
    }
	function get_sortable_columns() 
	 {
        $sortable_columns = array(
			'ID'     => array('ID',false), 
            'name'     => array('name',false),     
            'surname'     => array('surname',false),    
            'login'     => array('login',false),    
            'email'  => array('email',false),   
            'visibility'  => array('visibility',false),   
        );
		
        return $sortable_columns;
    }
	 function get_bulk_actions() 
	 {
         $actions = array(
			'assign-visibility' =>  esc_html__('Assign visibility', 'woocommerce-customers-manager'),
           
        );
        return $actions; 
    }
	function process_bulk_action() 
	{
         if( 'assign-visibility'===$this->current_action() ) 
		{
           
        } 
    }
	function prepare_items() 
	{
		$columns = $this->get_columns();
        $hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();
		
		//data preparation
		array_push($data, array( 'ID' => "",
								 'name'  => "",
								 'surname'  => "",
								 'email'  => "",
								 'login'  => "",
								 'visibility'  => "")
					);
		$current_page = $this->get_pagenum();
		$this->items = $data;
		
		
	}
	function render_page()
	{
		?>
		 <h2><?php esc_html_e('Ticket visibility', 'woocommerce-customers-manager'); ?> 
		</h2>
		<?php 
	}
}
?>