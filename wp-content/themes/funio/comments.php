<?php
if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
	<h3 class="comments-title">
		<?php $comments_number = get_comments_number(); ?>
		<?php if($comments_number == 1){?>
			<?php echo esc_attr($comments_number).esc_html__(' Comment', 'funio'); ?>
		<?php }else{ ?>
			<?php echo esc_attr($comments_number).esc_html__(' Comments', 'funio'); ?>
		<?php } ?>
	</h3>
	<div class="comment-list">
		<?php wp_list_comments( array('callback'   => 'funio_comment') ); ?>
	</div><!-- .comment-list -->
	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
	<nav id="comment-nav-below" class="navigation comment-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php echo esc_html__( 'Comment navigation', 'funio' ); ?></h1>
		<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'funio' ) ); ?></div>
		<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'funio' ) ); ?></div>
	</nav><!-- #comment-nav-below -->
	<?php endif; // Check for comment navigation. ?>	
	<?php if ( ! comments_open() ) : ?>
	<p class="no-comments"><?php echo esc_html__( 'Comments are closed.', 'funio' ); ?></p>
	<?php endif; ?>
	<?php endif; // have_comments() ?>
	<?php 
		echo '<div class="comment-form">';	
			$args = array(
			'fields' => apply_filters(
				'comment_form_default_fields', array(
					'author' =>'<div class="form-group col-md-6 col-sm-6">' . '<input id="author" placeholder="' . esc_attr__( 'Your Name *', 'funio'  ) . '" name="author"  type="text" value="' .
						esc_attr( $commenter['comment_author'] ) . '" size="30" class="form-control" />'.
						'</div>'
						,
					'email'  => '<div class="form-group col-md-6 col-sm-6">' . '<input id="email" placeholder="' . esc_attr__( 'Your Email *', 'funio'  ) . '" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) .
						'" size="30" class="form-control"   />'  .
						'</div>',
					'url'    => '<div class="form-group col-md-12 col-sm-12">' .
					 '<input id="url" name="url" placeholder="' . esc_attr__( 'Website', 'funio' ) . '" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" class="form-control"   /> ' .
					   '</div>',
				)
			),
			 'comment_field' =>  '<div class="form-group col-md-12 col-sm-12"><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"  placeholder="' . esc_attr__( 'Comment', 'funio' ) . '" class="form-control"  >' .
			'</textarea></div>',
			'comment_notes_after' => '',
			'class_form'      => 'row ',
			'title_reply_before'	=> ' <div class="section-header comment_reply_header"><h3>',
			'title_reply_after'	=>	'</h3></div>',
			 'logged_in_as' => '<div class="logged-in-as col-md-12 col-sm-12">' .
			sprintf(
			wp_kses( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="'.esc_attr__('Log out of this account','funio').'">'.esc_html__('Log out?','funio').'</a>','funio' ),
			  admin_url( 'profile.php' ),
			  $user_identity,
			  wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) )
			) . '</div>',
			'must_log_in' => '<div class="must-log-in col-md-12 col-sm-12">' .
			sprintf(
			  wp_kses( 'You must be <a href="%s">logged in</a> to post a comment.','funio' ),
			  wp_login_url( apply_filters( 'the_permalink', get_permalink() ) )
			) . '</div>',
			'comment_notes_before' => '<div class="comment-notes col-md-12 col-sm-12">' .
			esc_html__( 'Your email address will not be published.','funio' ) .
			'</div>',
			 'class_submit'      => 'btn',
			'submit_button' => '<div class="form-group col-md-12">
					<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />
				</div>'
			);
		comment_form($args);
		echo '</div>';
	?>
</div><!-- #comments -->