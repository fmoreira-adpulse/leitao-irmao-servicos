<?php 
class WCSTS_File
{
	var $to_remove_from_file_name = array(".php", "../", "./",  ".jsp", ".vbs", ".exe", ".bat", ".php5", ".pht", ".phtml", 
										  ".shtml", ".asa", ".cer", ".asax", ".swf", ".xap", ";", ".asp", ".aspx", 
										  "*", "<", ">", "::");
	public function __construct()
	{
		add_action( 'wp_ajax_wcsts_file_chunk_upload', array( &$this, 'ajax_manage_file_chunk_upload' ));
		add_action( 'wp_ajax_nopriv_wcsts_file_chunk_upload', array( &$this, 'ajax_manage_file_chunk_upload' ));
		
		add_action( 'wp_ajax_wcsts_delete_tmp_uploaded_file', array( &$this, 'delete_tmp_uploaded_file' ));
		add_action( 'wp_ajax_nopriv_wcsts_delete_tmp_uploaded_file', array( &$this, 'delete_tmp_uploaded_file' ));
		
		add_action( 'wp_ajax_wcsts_delete_uploaded_file', array( &$this, 'delete_uploaded_file' ));
		
		add_action('init', array( &$this, 'delete_unused_tmp_files' ));
	}
	//Ajax managment
	function ajax_manage_file_chunk_upload()
	{
		global $wcsts_session_model ;
		
		$buffer = 5242880; //1048576; //1mb
		$target_path = $this->get_temp_dir_path();
		$tmp_name = $_FILES['wcsts_file_chunk']['tmp_name'];
		$size = $_FILES['wcsts_file_chunk']['size'];
		$current_chunk_num = $_POST['wcsts_current_chunk_num'];
		//validation
		$file_name = str_replace($this->to_remove_from_file_name, "",$_POST['wcsts_file_name']);
		$session_id =  str_replace($this->to_remove_from_file_name, "", $_POST['wcsts_current_upload_session_id']);
		$upload_field_name = str_replace($this->to_remove_from_file_name, "", $_POST['wcsts_upload_field_name']);
		if($file_name != $_POST['wcsts_file_name'] || $session_id != $_POST['wcsts_current_upload_session_id'] || $upload_field_name != $_POST['wcsts_upload_field_name'])
			wp_die();
		//
		$tmp_file_name = $session_id."_".$file_name;
		$wcsts_is_last_chunk = $_POST['wcsts_is_last_chunk'] == 'true' ? true : false;
	

		$com = fopen($target_path.$tmp_file_name, "ab");
		$in = fopen($tmp_name, "rb");
			if ( $in ) 
				while ( $buff = fread( $in, $buffer ) ) 
				   fwrite($com, $buff);
				 
			fclose($in);
		fclose($com);
		
		//final validation once the file has been completely uploaded
		if($wcsts_is_last_chunk && file_exists($target_path.$tmp_file_name))
		{
			$validate = wp_check_filetype_and_ext($target_path.$tmp_file_name, $_POST['wcsts_file_name'] );
			$real_filename = $validate['proper_filename'] !== false ? $validate['proper_filename'] : $_POST['wcsts_file_name'];
			foreach($this->to_remove_from_file_name as $needle)
			{
				$pos = strpos($real_filename, $needle);
				$pos2 = strpos($file_name, $needle); //??
				if ($pos !== false || $pos2 !== false)  
				{
					unlink($target_path.$tmp_file_name);
					break;
				}
			}
				
		}
		
		wp_die();
	}
	function delete_tmp_uploaded_file()
	{
		$file_to_delete = isset($_POST['file_to_delete']) ? $_POST['file_to_delete'] : null;
		$target_path = $this->get_temp_dir_path();
		
		if(isset($file_to_delete))
		{
			try{
				@unlink($target_path.$file_to_delete);
			}catch(Exception $e){};
		}
		wp_die();
	}
	function delete_uploaded_file()
	{
		global $wcsts_order_model;
		
		$file_to_delete = isset($_POST['file_to_delete']) ? $_POST['file_to_delete'] : null;
		$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : null;
		$meta_key = isset($_POST['meta_key']) ? $_POST['meta_key'] : null;
		$target_path = $this->get_temp_dir_path($order_id);
		
		if(isset($file_to_delete))
		{
			try{
				@unlink($target_path.$file_to_delete);
				$wcsts_order_model->delete_meta($order_id, $meta_key);
				//wcsts_var_dump($target_path.$file_to_delete);
			}catch(Exception $e){};
		}
		wp_die();
	}
	function delete_unused_tmp_files()
	{
		$files = glob($this->get_temp_dir_path()."*");
		$now   = time();

		if(is_array($files) && !empty($files))
			foreach ($files as $file) 
				if (is_file($file)) 
				  if (basename ($file) != "index.html" && $now - filemtime($file) >= 60 * 60 /* * 24 * 2 */) //1 hpur
				  {
					  try{
							@unlink($file);
						}catch(Exception $e){};
				  }
			
		  
	}
	private function get_temp_dir_path($order_id = null, $baseurl = false)
	{
		$upload_dir = wp_upload_dir();
		
		$temp_dir = !$baseurl ? $upload_dir['basedir']. '/wcsts/' : $upload_dir['baseurl']. '/wcsts/';
		$temp_dir .= isset($order_id) && $order_id !=0 ? $order_id.'/': 'tmp/';
		
		if(!$baseurl)
		{
			if (!file_exists($temp_dir)) 
					mkdir($temp_dir, 0775, true);
			
			if( !file_exists ($temp_dir.'index.html'))
				$this->create_empty_file ($temp_dir.'index.html');
		}
		return $temp_dir;
	}
	private function create_empty_file($path)
	{
		$file = fopen($path, 'w'); 
		fclose($file); 
	}
	//End tmp and ajax managment 
	
	public function save_uploaded_files($file_array, $ticket_id, $message_id)
	{
		if(!isset($file_array) || !is_array($file_array))
			return;
		
		global $wcsts_ticket_message_model;
		$upload_dir = wp_upload_dir();
		$final_path = $upload_dir['basedir'].'/wcsts/'.$ticket_id.'/'.$message_id.'/';
		
		//Dir creation
		if (!file_exists($upload_dir['basedir']."/wcsts")) 
				mkdir($upload_dir['basedir']."/wcsts", 0775, true);	
		
		if (!file_exists($upload_dir['basedir']."/wcsts/".$ticket_id)) 
				mkdir($upload_dir['basedir']."/wcsts/".$ticket_id, 0775, true);
		
		if (!file_exists($upload_dir['basedir']."/wcsts/".$ticket_id.'/'.$message_id)) 
				mkdir($upload_dir['basedir']."/wcsts/".$ticket_id.'/'.$message_id, 0775, true);	
			
		if( !file_exists ($upload_dir['basedir'].'/wcsts/index.html'))
						touch ($upload_dir['basedir'].'/wcsts/index.html');
					
		if( !file_exists ($upload_dir['basedir'].'/wcsts/'.$ticket_id.'/index.html'))
						touch ($upload_dir['basedir'].'/wcsts/'.$ticket_id.'/index.html');
					
		if( !file_exists ($final_path.'index.html'))
						touch ($final_path.'index.html');
		
		//Files move from tmp dir to final dir
		foreach($file_array as $file)
		{
			
			//old
			//$new_file_name = rand(0,100000)."_".$file['name'];
			//move_uploaded_file($file['tmp_name'], $final_path.$new_file_name);
			
			//new 
			if($file['file_name'] == "")
				continue;
			
			$new_file_name = rand(0,100000)."_".$file['file_name'];
			$tmp_file_folder = $this->get_temp_dir_path();
			$unique_file_name = $file['file_name_tmp_prefix']."_".$file['file_name'];
			$result = @rename($tmp_file_folder.$unique_file_name, $final_path.$new_file_name);
			
			//Message metadata update to keep track of the uploaded files
			if($result)
				$wcsts_ticket_message_model->add_attachment_path($message_id, "wcsts/".$ticket_id.'/'.$message_id.'/'.$new_file_name);
		}
	}
	public function delete_file($file_relative_path)
	{
		$upload_dir = wp_upload_dir();
		try{
			@unlink($upload_dir['basedir'].'/'.$file_relative_path);
		}catch(Exception $e){};
	}
	public function delete_directory($ticket_id)
	{
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'].'/wcsts/'.$ticket_id;
		try{
			$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new RecursiveIteratorIterator($it,
						 RecursiveIteratorIterator::CHILD_FIRST);
						 
			
			foreach($files as $file) {
				if ($file->isDir()){
					@rmdir($file->getRealPath());
				} else {
					@unlink($file->getRealPath());
				}
			}
			@rmdir($dir);
		}catch(Exception $e){};
	}
}
?>