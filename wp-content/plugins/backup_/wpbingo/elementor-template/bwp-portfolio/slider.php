<?php 
	$widget_id = isset( $widget_id ) ? $widget_id : 'bwp_portfolio_'.rand().time();
	$show_tab 		= ( isset( $show_tab ) ) 		? $show_tab : true;
	$title	 		= ( isset( $title ) ) 			? $title : '';
	$description 	= ( isset( $description ) ) 	? $description : '';
	if( count($portfolio) == 0 ){
		return ;
	}
	$attributes     = '';
	if( $style == 'masonry' ){
		$class_col_lg = ($col1 == 5) ? '2-4'  : (12/$col1);
		$attributes .= 'portfolio-item col-lg-'.$class_col_lg .' col-md-'.$class_col_lg .' col-sm-'.$class_col_lg .' col-xs-12';
	}else{
		$class_col_lg = ($col1 == 5) ? '2-4'  : (12/$col1);
		$class_col_md = ($col2 == 5) ? '2-4'  : (12/$col2);
		$class_col_sm = ($col3 == 5) ? '2-4'  : (12/$col3);
		$class_col_xs = ($col4 == 5) ? '2-4'  : (12/$col4);
		$attributes .= 'grid-item col-lg-'.$class_col_lg .' col-md-'.$class_col_md .' col-sm-'.$class_col_sm .' col-xs-'.$class_col_xs; 
	}	
	$args = array(
		'post_type' => 'portfolio',
		'tax_query' => array(
			array(
				'taxonomy'	=> 'category_portfolio',
				'field' 	=> 'slug',
				'terms'		=> $portfolio
			)
		),
		'orderby'	=> $orderby,
		'showposts' => $number
	);
	$query = new wp_query( $args );
	$max_page = $query -> max_num_pages;
	$j=0;
	$pcout = get_posts( $args );
?>
<div id="<?php echo esc_attr( $widget_id ); ?>" class="wpb-portfolio <?php echo esc_attr( $style ) ?>" >
	<!-- Title & description -->
	<?php if( $title != '' || $description != '' ){ ?>
	<div class="portfolio-desc">
		<?php echo ( $title != '' ) ? '<h2>'. $title .'</h2>' : ""; ?>
		<?php echo ( $description != '' ) ? '<div class="p-desc">'. $description .'</div>' : ""; ?>
	</div>
	<?php } ?>
	<!-- Tab  -->

	<!-- Container -->
	<div class="container-portfolio">
		<div class="content-portfolio clearfix slick-carousel" data-columns4="1"  data-columns3="<?php echo $col4; ?>" data-columns2="<?php echo $col3; ?>" data-columns1="<?php echo $col2; ?>" data-columns="<?php echo $col1; ?>">
		<?php
			while( $query -> have_posts() ) : $query -> the_post();
			global $post;
			$pterms	   	= get_the_terms( $post->ID, 'category_portfolio' );
			$term_str  = '';
			if( count($pterms) > 0 ){
				foreach( $pterms as $key => $term ){
					$term_str .= $term -> slug . ' ';
				}
			}	
			$img = '';
			?>	
			<?php	if( ( $j % $item_row ) == 0 && $item_row !=1) { ?>
				<div class="portfolio-slider">
			<?php } ?>
				<div class="portfolio-item">
					<div class="portfolio-item-inner">
						<div class="portfolio-in">
							<?php 
								if( has_post_thumbnail() ){
									$img = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
								}
							?>							
							<a class="portfolio-img" href="<?php the_permalink() ?>" title="<?php the_title_attribute(); ?>">
								<?php the_post_thumbnail( 'post-thumbnail', array( 'alt' => get_the_title() ) ); ?>
							</a>
						</div>
						<div class="pitem-text">
							<div class="info">
								<div class="content">
									<?php $portfolio = get_the_terms(get_the_ID(),'category_portfolio'); ?>
									<div class="pitem-text">
										<a href="<?php the_permalink(); ?>" class="p-item item-more" title="<?php the_title_attribute(); ?>"><span class="fa fa-link"></span></a>
										<a href="<?php echo esc_attr( $img ); ?>" class="p-item item-popup" title="<?php the_title_attribute(); ?>"><span class="zmdi zmdi-eye"></span></a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php if((($j + 1) % $item_row == 0 || $j== count($pcout)) && $item_row !=1  ){?>
				</div>
			<?php  } $j++; ?>
		<?php 
			endwhile;
			wp_reset_postdata();
		?>
		</div>
	</div>
</div>