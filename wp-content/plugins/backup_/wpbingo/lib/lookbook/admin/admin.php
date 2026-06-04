<?php
$url_tail = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	global $wpdb;
	$max_id = $wpdb->get_results(
            "SELECT
                max(id) AS max_id
            FROM
                `" . $wpdb->prefix . LOOKBOOK_TABLE . "`",ARRAY_A
    );
    if (isset($_REQUEST['bwp_action'])) {
        if (in_array($_REQUEST['bwp_action'], $admin_post_handlers)) {
            call_user_func($_GET['bwp_action']);
        }
    }

    if (!isset($_POST['noredirect']) && isset($_REQUEST['page']) && $_REQUEST['page'] == 'lookbook') {
		if(isset($_REQUEST['saveandstay']) && $_REQUEST['saveandstay']){
			if(isset($_REQUEST['lookbook_id']) && $_REQUEST['lookbook_id'])
				$lookbook_id = $_REQUEST['lookbook_id'];
			elseif($max_id)
				$lookbook_id = (int)$max_id[0]['max_id'] + 1;
			else
				$lookbook_id = 0;	
			wp_redirect($site_url . "admin.php?page=lookbook&bwp_action=add_lookbook&id=".$lookbook_id.$url_tail);	
		}else{
			$site_url = esc_url( home_url( '/' ) );
			wp_redirect($site_url . "wp-admin/admin.php?page=lookbook".$url_tail);
		}
        exit;
    }

}

function bwp_dashboard(){
    global $admin_get_handlers;
	
    if (isset($_GET['bwp_action'])) {

        if (in_array($_GET['bwp_action'], $admin_get_handlers)){
            call_user_func($_GET['bwp_action']);
        }

    }else {
        list_lookbook();
    }
}

function list_lookbook() {
    global $wpdb;
	
    $limit_result = $wpdb->get_results(
            "SELECT
                COUNT(id) AS slides_count
            FROM
                `" . $wpdb->prefix . LOOKBOOK_TABLE . "`",ARRAY_A
    );

    $add_new_lookbook = '<a href="admin.php?page=lookbook&bwp_action=add_lookbook" class="page-title-action">'.__('Add Lookbook','wpbingo').'</a>';

    echo '<div>
        <div>
            <div class="wrap">
                <h1>
                    ' . $add_new_lookbook . '
                </h1>
            </div>
        </div>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th class="dies-column">#</th>
                    <th>'.__('Image','wpbingo').'</th>
                    <th>'.__('Name','wpbingo').'</th>
                </tr>
            </thead>';
	
	$result = $wpdb->get_results("SELECT id, name, image FROM `" . $wpdb->prefix . LOOKBOOK_TABLE . "`",ARRAY_A );

    $s = 1;
    foreach ($result as $slider){
        echo
            '<tr>
                <td>'.$s.'</td>
                <td><img src="' . LOOKBOOK_UPLOAD_URL_THUMB . "/" . $slider['image'].'" /></td>
                <td>
                    <strong><a href="admin.php?page=lookbook&bwp_action=add_lookbook&id='.$slider['id'].'">' . $slider['name'] . '</a><br>
                    <div class="row-actions">
                        <span class="edit"><a href="admin.php?page=lookbook&bwp_action=add_lookbook&id='.$slider['id'].'">'.__('Edit','wpbingo').'</a>|</span>
                        <span class="delete">
                            <form id="del'.$slider['id'].'" method="post" action="admin.php?page=lookbook&bwp_action=del_lookbooks">
                            <input type="hidden" name="id" value="'.$slider['id'].'">
                            <input type="hidden" name="bwp_action" value="del_lookbooks">
                            <a href="#" class="delete-tag" onclick="if(confirm(\'Delete ?\')) jQuery(\'#del'.$slider['id'].'\').submit(); else return false;">'.__('Delete','wpbingo').'</a>';
                            wp_nonce_field( 'del_lookbooks', '_bwp_nonce');
                        echo '</form>|
                        </span>
                    </div>
                </td>
            </tr>';
        $s++;
    }

    echo '</table>
    </div>';
}

function store_lookbook() {
    global $url_tail, $wpdb,$config;
    if ( ! empty( $_POST ) && check_admin_referer( 'store_lookbook', '_bwp_nonce' ) ) {

        $file = new bwp_lookbook_class();
        $user_data = prepare_data($_POST);

        $create_slide = false;

		if (empty($_POST['lookbook_id'])) {
			$wpdb->insert(
				$wpdb->prefix . LOOKBOOK_TABLE,
				$user_data['data'],
				$user_data['format']);
				
			$lookbook_id = $wpdb->insert_id;
			$create_slide = true;
		}else {
			$wpdb->update(
				$wpdb->prefix . LOOKBOOK_TABLE,
				$user_data['data'],
				array('id'=>$_POST['lookbook_id']),
				$user_data['format'],
				array( '%d' )
			);
			$lookbook_id = $_POST['lookbook_id'];
		}

		if ($_POST['tmp_image']) {
			
			$file->create_folder_recursive(LOOKBOOK_UPLOAD_PATH_ORIG, 0755);
		   
			$image_name = $file->copy_file(
							LOOKBOOK_UPLOAD_PATH_IMAGE . "/" . $_POST['tmp_image'], 
							LOOKBOOK_UPLOAD_PATH_ORIG. "/",
							$_POST['tmp_image_name']
						);
						
			$thumb_width = $config['thumb_width'];
			$thumb_height = $config['thumb_height'];

			$thumb_check_result = $file->check_orientation(
									$thumb_width,
									$thumb_height,
									LOOKBOOK_UPLOAD_PATH_IMAGE . "/" .$_POST['tmp_image']
			);

			$force_resize_flag = true;

			$need_places_thmb = false;
			if (!$thumb_check_result['check']){
				$force_resize_flag = false;
				$thumb_height = $thumb_check_result['resize_height'];
				$thumb_width = $thumb_check_result['resize_width'];
				$need_places_thmb = true;
			}

			$file->resize_upload_img(
					LOOKBOOK_UPLOAD_PATH_IMAGE . "/" .$_POST['tmp_image'],
					$image_name,
					$thumb_height,
					$thumb_width,
					LOOKBOOK_UPLOAD_PATH_THUMB . "/",
					$force_resize_flag
			);
			if ($need_places_thmb) {
				$file->center_place_img_to_background(
						$config['thumb_width'],
						$config['thumb_height'],
						$thumb_check_result['frame_orient'],
						LOOKBOOK_UPLOAD_PATH_THUMB . "/" . $image_name
				);
			}
			
			$width_image = (isset($_POST['bwp_width']) && $_POST['bwp_width']) ? $_POST['bwp_width'] :  $config['width'];
			$height_image = (isset($_POST['bwp_height']) && $_POST['bwp_height']) ? $_POST['bwp_height'] :  $config['height'];
			$check_result = $file->check_orientation(
				$width_image,
				$height_image,
				LOOKBOOK_UPLOAD_PATH_IMAGE . "/" .$_POST['tmp_image']
			);

			if ($check_result['check']) {
					$file->resize_upload_img(
					LOOKBOOK_UPLOAD_PATH_IMAGE . "/" .$_POST['tmp_image'],
					$image_name,
					$height_image,
					$width_image,
					LOOKBOOK_UPLOAD_PATH . "/",
					true);
			}else {
					$file->resize_upload_img(
					LOOKBOOK_UPLOAD_PATH_IMAGE . "/" .$_POST['tmp_image'],
					$image_name,
					$check_result['resize_height'],
					$check_result['resize_width'],
					LOOKBOOK_UPLOAD_PATH . "/",
					false
				);
				$file->center_place_img_to_background(
					$width_image,
					$height_image,
					$check_result['frame_orient'],
					LOOKBOOK_UPLOAD_PATH . "/" . $image_name
				);
			}

			/**
			 * Update image field
			 */
			$wpdb->update(
				$wpdb->prefix . LOOKBOOK_TABLE,
				array('image' => $image_name),
				array('id' => $lookbook_id),
				array('%s'),
				array( '%d' )
			);

			if (!empty($_POST['old_image']) && $image_name) {
				$file->delete_file(LOOKBOOK_UPLOAD_PATH . "/" . $_POST['old_image']);
				$file->delete_file(LOOKBOOK_UPLOAD_PATH_THUMB . "/" . $_POST['old_image']);
				$file->delete_file(LOOKBOOK_UPLOAD_PATH_ORIG . "/" . $_POST['old_image']);
			}

			$file->delete_file(LOOKBOOK_UPLOAD_PATH_IMAGE . "/" .$_POST['tmp_image']);
			$file->delete_file(LOOKBOOK_UPLOAD_PATH_IMAGE . "/preview_" .$_POST['tmp_image']);
		}
    }
}

function add_lookbook() {
    global $wpdb,$config;
	
    wp_enqueue_script('annotate');
    $page_header = __('Create New Lookbook','wpbingo');
	$item = array();
    if (isset($_GET['id'])) {
        $page_header = __('Edit Lookbook','wpbingo');
        $item = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    *
                FROM
                    `" . $wpdb->prefix . LOOKBOOK_TABLE . "`
                WHERE
                    id = %d",
                $_GET['id']
            ),
            ARRAY_A
        );

        $required = '';

    }else {
        $required = 'required';
    }

    $image = (empty($item[0]['image'])) ? '' : '<img id="LookbookImage" width="'.((empty($item[0]['width'])) ? $config['width'] : $item[0]['width']).'" height="'.((empty($item[0]['height'])) ? $config['height'] : $item[0]['height']).'" src="' . LOOKBOOK_UPLOAD_URL_IMAGE . '/'.UPLOAD_FOLDER_NAME.'/' . $item[0]['image'] . '" />';
	$add_new_lookbook = '<a href="admin.php?page=lookbook&bwp_action=add_lookbook" class="page-title-action">'.__('Add New Lookbook','wpbingo').'</a>';
    echo
        '<div><h2>' . $page_header . '</h2>        
			<div>
				<div class="wrap">
					<h1>
						' . $add_new_lookbook . '
					</h1>
				</div>
			</div>
            <form method="post" onsubmit="return save_notes();" action="admin.php?page=lookbook&bwp_action=store_lookbook" enctype="multipart/form-data">
                <input type="hidden" name="lookbook_id" value="'.@$_GET['id'].'" />
                <input type="hidden" name="old_image" value="'.@$item[0]['image'].'" />
                <input type="hidden" name="bwp_pins" value="">
                <input type="hidden" name="tmp_image" value="" id="bwp_tmp_image">
                <input type="hidden" name="tmp_image_name" value="" id="bwp_tmp_image_name">';

            wp_nonce_field( 'store_lookbook', '_bwp_nonce');

    echo
        '<table class="wp-list-table widefat fixed striped pages">

            <tr>
                <td class="label">
                    <label for="slide_name">'.__('Name','wpbingo').'<span class="required">*</span></label>
                </td>
                <td class="value">
                    <input size="50" type="text" required class=" input-text" value="'.@$item[0]['name'].'" name="bwp_name" id="slide_name">
                </td>
                <td class=""></td>
            </tr>
            <tr>
                <td class="label">
                    <label for="slide_name">'.__('Title','wpbingo').'</label>
                </td>
                <td class="value">
                    <input size="50" type="text" class=" input-text" value="'.@$item[0]['title'].'" name="bwp_title" id="slide_title">
                </td>
                <td class=""></td>
            </tr>
            <tr>
                <td class="label">
                    <label for="slide_name">'.__('Description','wpbingo').'</label>
                </td>
                <td class="value">
                    <input size="100" type="text" class="input-text description" value="'.@$item[0]['description'].'" name="bwp_description" id="slide_description">
                </td>
                <td class=""></td>	
            <tr>
            <tr>
                <td class="label">
                    <label for="bwp_image">'.__('Upload file','wpbingo').'<span class="required">*</span></label>
                </td>
                <td class="value">
                    <input type="file" ' . $required . ' name="bwp_image" id="bwp_image_id"><br>
                    <div id="upload_progress"></div><div id="upload_results" class="required"></div>
                </td>
                <td class=""></td>
            </tr>
            <tr>
                <td colspan="3">
                <div id="LookbookImageBlock">
                        ' . $image . '
                    </div>
                </td>
            </tr>
            </table>
				<input type="hidden" class=" input-text" value="'.@$item[0]['width'].'" name="bwp_width" id="slider_width">
                <input type="hidden" class=" input-text" value="'.@$item[0]['height'].'" name="bwp_height" id="slider_height">			
                <div class="bottom_button">
                    <input type="submit" value="'.__('Save','wpbingo').'" class="button button-primary button-large">
					 <input type="submit" value="'.__('Save And Stay','wpbingo').'" name="saveandstay" class="button button-primary button-large">
					<a href="admin.php?page=lookbook&bwp_action=list_lookbook" class="button button-primary button-large">'.__('Cancel','wpbingo').'</a>
                </div>
            </form>
        </div>';
		
		js_print_lookbook($item);
}

function js_print_lookbook($item){ ?>
    <script type="application/javascript">
        var annotate;
        var response;
        var relative_url = '<?php echo get_site_url()?>';
        function InitPinBtn() {
            if (jQuery("img#LookbookImage")) {
                var annotObj = jQuery("img#LookbookImage").annotateImage({
                    editable: true,
                    useAjax: false,
                    interdict_areas_overlap: true,
                    captions: {"button_add":"Pin","cancel_btn":"Cancel","delete_btn":"Delete","alert_saving_error":"Error saving this pin.","alert_overlap_error":"Areas should not overlap.","alert_sku_error":"Please Choosen Product","alert_product_dont_exists":"The product : ","prod_sku":"Product:","alert_delete_pin":"Error deleting this pin."},
                    notes: <?php echo (empty($item[0]['pins'])) ? '[]' : $item[0]['pins'];?>,
                    input_field_id: "pins"
                });

                var top = Math.round(jQuery("img#LookbookImage").height()/2);
                jQuery(".image-annotate-background").append('<div class="pins-msg" style="top:' + top + 'px;">Image To See Pins</div>');

                jQuery(".image-annotate-background").hover(
                    function () {
                        displayPinsMessenger();
                    },
                    function () {
                        displayPinsMessenger();
                    }
                );

                return annotObj;
            }
            else
            {
                return false;
            }
        };

        function save_notes() {
            var notes_obj = JSON.stringify(annotate.notes);
            jQuery("input[name='bwp_pins']").val(notes_obj);
            return true;
        }


        jQuery(document).ready(function() {

            annotate = InitPinBtn();

            jQuery("#bwp_image_id").on("change", function() {
                var file_data = jQuery("#bwp_image_id").prop("files")[0];
                var form_data = new FormData();

                jQuery("#upload_progress").show();

                form_data.append("bwp_image", file_data);
                form_data.append("noredirect", true);
                form_data.append("_bwp_nonce", jQuery("#_bwp_nonce").val());

                jQuery.ajax({
                    url: "admin.php?page=lookbook&bwp_action=ajax_upload",
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function(data, status){
                        if (status == 'success') {
                            if (data.error) {
                                var error_text = '';
                                jQuery.each(data.msg, function(key, value) {
                                    error_text += value + ' ';
                                });
                                jQuery("#upload_results").text(error_text);
                            }else {
                                jQuery("#bwp_tmp_image").val(data.tmp_file);
                                jQuery("#bwp_tmp_image_name").val(data.file_name);
                                jQuery("#upload_results").text(data.msg[0]);
                                var tmp_img = '<img id="LookbookImage" width="'+data.width+'px" height="'+data.height+'px" src="<?php echo LOOKBOOK_UPLOAD_URL_IMAGE;?>/' + data.preview_file + '" />';
                                jQuery("#slider_width").val(data.width);
								jQuery("#slider_height").val(data.height);
								jQuery("#LookbookImageBlock").html(tmp_img);
                                annotate = InitPinBtn();
                            }

                        }else{
                            jQuery("#upload_results").text('File not uploaded. Network error');
                        }
                        jQuery("#upload_progress").hide();
                    }
                });
            });
        });
    </script>	
<?php }

function prepare_data($data, $prefix = 'bwp_') {
    $res_data = array();
    $formats = array();
    foreach ($data as $key=>$val) {
        if (preg_match("#^" . $prefix . "(.+)$#su",$key,$matched)){

            if (is_array($val)) {
                $res_data[$matched[1]] = serialize($val);
            }else {
                $res_data[$matched[1]] = $val;
            }
            $formats[] = (is_numeric($val)) ? "%d" : "%s";
        }
    }
    return array('data'=>$res_data, 'format'=>$formats);
}

function del_lookbook() {

    global $url_tail, $wpdb;
    $file = new bwp_lookbook_class();

    if (isset($_POST['id']) && is_numeric($_POST['id'])) {

        $wpdb->query(
            $wpdb->prepare(
                "
                DELETE FROM `" .$wpdb->prefix. LOOKBOOK_TABLE . "`
		        "
            )
        );

        $file->delete_directory(LOOKBOOK_UPLOAD_PATH . "/" . $_POST['id']);
        $file->delete_directory(LOOKBOOK_UPLOAD_PATH_THUMB . "/" . $_POST['id']);
    }
}

function del_lookbooks() {

    if ( ! empty( $_POST ) && check_admin_referer( 'del_lookbooks', '_bwp_nonce' ) ) {

        global $url_tail, $wpdb;
        $file = new bwp_lookbook_class();

        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $item = array();
            $item = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        *
                    FROM
                        `" . $wpdb->prefix . LOOKBOOK_TABLE . "`
                    WHERE
                        id = %d",
                    $_POST['id']
                ),
                ARRAY_A
            );

            if ($wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM `" . $wpdb->prefix. LOOKBOOK_TABLE . "`
                    WHERE id = %d
                    ",
                    $_POST['id']
                )
            )){
                $file->delete_file(LOOKBOOK_UPLOAD_PATH . "/" . $item[0]['image']);
                $file->delete_file(LOOKBOOK_UPLOAD_PATH_THUMB . "/" . $item[0]['image']);
                $file->delete_file(LOOKBOOK_UPLOAD_PATH_ORIG . "/" . $item[0]['image']);
            }
        }
    }
}

function check_isset_product() {
    global $wpdb;
	$posts = get_posts(array(
		'name' => $_POST['post_id'],
		'posts_per_page' => 1,
		'post_type' => 'product',
		'post_status' => 'publish'
	));
    echo (isset($posts[0]) && $posts[0]) ? 1 : __('don\'t exist','wpbingo');
    exit();
}

function search_product(){
	$character = (isset($_POST['character']) && $_POST['character'] ) ? $_POST['character'] : '';
	$args = array(
		'post_type' 			=> 'product',
		'post_status'    		=> 'publish',
		'ignore_sticky_posts'   => 1,	  
		's' 					=> $character,
		'posts_per_page'		=> 50
	);	
	$json = array();
	$list = get_posts( $args );
	$html = "";
	if($list){
		$html .= '<ul class="list-product">';
			foreach($list as $item){
				$html .= '<li class="item-list-product" data-slug="'.esc_html($item->post_name).'">';
				$html .= esc_html($item->post_title).'('.esc_attr($item->ID).')';
				$html .= '</li>';
			}								
		$html .= '</ul>';
	}
	die($html);
}

function bwp_check_slider_image_restriction($file, $restrictions) {

    if (empty($file['tmp_name'])) return array('error'=>false, 'error_msg'=>'');

    $error = false;
    $error_msg = array();

    if ($file['size'] > $restrictions['size']){
        $error = true;
        $error_msg[] = __('File size too large','wpbingo');
    }

    preg_match("#.+\/(.+)#siu", $file['type'], $matches);

    $allowed_ext = explode(",", $restrictions['ext']);

    array_push($allowed_ext, 'jpeg'); // :)

    if (!in_array($matches[1], $allowed_ext)){
        $error = true;
        $error_msg[] = __('This file type not allowed','wpbingo');
    }

    return array('error'=>$error, 'error_msg'=>$error_msg);
}

function ajax_upload() {

    if ( ! empty( $_POST ) && check_admin_referer( 'store_lookbook', '_bwp_nonce' ) ) {

        global $wpdb,$config;

        $file = new bwp_lookbook_class();
        $restrictions = array(
            'size' => 20000000,
            'ext' => 'png,gif,jpg'
        );
		
        $check_result = bwp_check_slider_image_restriction($_FILES['bwp_image'], $restrictions);
        if ($check_result['error']) {
            echo json_encode(array('error'=>true, 'msg'=>$check_result['error_msg']));
        }else {
            preg_match("#\.(\w+)$#siu", $_FILES['bwp_image']['name'], $matches);
            $tmp_file_name = time().$matches[0];
            $tmp_file_name_preview = 'preview_' . $tmp_file_name;
			$imagesize = @getimagesize($_FILES['bwp_image']['tmp_name']);
			$width_image = $imagesize[0];
			$height_image = $imagesize[1];
            $check_result = $file->check_orientation(
                $width_image,
                $height_image,
                $_FILES['bwp_image']['tmp_name']
            );
			
			if ($check_result['check']) {
				$file->resize_upload_img(
					$_FILES['bwp_image']['tmp_name'],
					$tmp_file_name_preview,
					$height_image,
					$width_image,
					LOOKBOOK_UPLOAD_PATH_IMAGE . "/",
					true);
			}else {
				$file->resize_upload_img(
					$_FILES['bwp_image']['tmp_name'],
					$tmp_file_name_preview,
					$check_result['resize_height'],
					$check_result['resize_width'],
					LOOKBOOK_UPLOAD_PATH_IMAGE . "/",
					false
				);
				$file->center_place_img_to_background(
					$width_image,
					$height_image,
					$check_result['frame_orient'],
					LOOKBOOK_UPLOAD_PATH_IMAGE . "/" . $tmp_file_name_preview
				);
			}

            if ($image = $file->upload_file(
                $_FILES['bwp_image']['tmp_name'],
                LOOKBOOK_UPLOAD_PATH_IMAGE . "/",
                $tmp_file_name
                )
            ){
                echo json_encode(array('error'=>false, 'msg'=>array('File uploaded'), 'tmp_file'=>$image, 'file_name'=>$_FILES['bwp_image']['name'], 'preview_file'=>$tmp_file_name_preview,'width'=>$imagesize[0],'height'=>$imagesize[1]));
            }else {
                echo json_encode(array('error'=>true, 'msg'=>array('File not uploaded')));
            }
        }
    }
    exit();
}


add_action('wp_ajax_wplookbook_free_dismiss_acf_notice', 'wplookbook_free_dismiss_acf_notice');
