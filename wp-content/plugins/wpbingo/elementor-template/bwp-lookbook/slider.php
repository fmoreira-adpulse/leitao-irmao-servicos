<?php
	global $wpdb;
	if($lookbook){ ?>
	<div class="bwp-lookbook slider <?php echo esc_attr($class); ?>">
			<div class="slick-carousel" data-nav="<?php echo esc_attr($show_nav);?>" data-dots="<?php echo esc_attr($show_pag);?>" data-columns4="<?php echo $columns4; ?>" data-columns3="<?php echo $columns3; ?>" data-columns2="<?php echo $columns2; ?>" data-columns1="<?php echo $columns1; ?>" data-columns="<?php echo $columns; ?>" >
			<?php foreach($lookbook as $item){
					$item = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT
							*
						FROM
							`" . $wpdb->prefix . LOOKBOOK_TABLE . "`
						WHERE
							id = %d",
						$item
					),
					ARRAY_A
				);
					if(!empty($item)){?>
						<div class="item">
							<div class="bwp-content-lookbook">
								<img src="<?php echo esc_url(LOOKBOOK_UPLOAD_URL_IMAGE . '/'.UPLOAD_FOLDER_NAME.'/' . $item[0]['image']); ?>" alt="<?php echo esc_html($item[0]['name']); ?>"/>
								<?php
									$pins = ($item[0]['pins']) ? $item[0]['pins'] : "";
									if($pins){
										$pins = json_decode($pins);
										foreach($pins as $key => $pin){
											$style = "";
											$left = round(($pin->left/$pin->img_width)*100, 2);
											$top = round(($pin->top/$pin->img_height)*100, 2);		
											
											if($left > 50)
												$style .= " right:33px;";
											else
												$style .= " left:33px;";

											if($top > 50)
												$style .= " bottom:10px;";
											else
												$style .= " top:10px;";									
											?>
											<div class="item-lookbook" style="height:<?php echo esc_attr($pin->height)?>px;width:<?php echo esc_attr($pin->width)?>px;left:<?php echo esc_attr($left)?>%;top:<?php echo esc_attr($top)?>%">
												<span class="number-lookbook"><?php echo esc_attr($key +1); ?></span>
												<?php 
												if (!empty($pin->slug)){
													$result = get_posts(array(
														'name' => $pin->slug,
														'posts_per_page' => 1,
														'post_type' => 'product',
														'post_status' => 'publish'
													));
													if(isset($result[0]) && $result[0]){
														$post_data = $result[0];
														$id_product = $post_data->ID;
														$product = new WC_Product( $id_product );
														$product_url = get_permalink($id_product);
														$url = wp_get_attachment_image_src( get_post_thumbnail_id($id_product),'shop_catalog');															
														$img_block = (!empty($url[0])) ? '<img src="' . $url[0] . '"  alt="'.esc_attr__("Image Lookbook","wpbingo").'" />' : ''; ?>
														<div class="content-lookbook" style="<?php echo esc_attr($style); ?>">
															<div class="item-thumb">
																<a href="<?php echo esc_url($product_url); ?>"><?php echo wp_kses_post($img_block); ?></a>
															</div>
															<div class="content-lookbook-bottom">
																<div class="item-title">
																	<a href="<?php echo esc_url($product_url); ?>"><?php echo $product->get_title(); ?></a>
																</div>
																<div class="price">
																	<?php echo $product->get_price_html(); ?>
																</div>
															</div>
														</div>
													<?php }	
												} ?>
											</div>
											<?php
										}
									} ?>															
							</div>
							<?php if($item[0]['title'] || $item[0]['description']):?>
							<div class="info-lookbook">
								<?php if($item[0]['description']) : ?>
									<div class="description-lookbook">
										<?php echo esc_html($item[0]['description']); ?>
									</div>
								<?php endif; ?>
								<?php if($item[0]['title']) : ?>
									<h2 class="title-lookbook">
										<?php echo esc_html($item[0]['title']); ?>
									</h2>
								<?php endif; ?>								
							</div>
							<?php endif; ?>	
						</div>						
				<?php }
			}?>
			</div>
	</div>
<?php }?>