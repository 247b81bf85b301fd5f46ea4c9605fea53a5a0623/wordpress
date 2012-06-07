<?php
function fb_insights_page() {
	$options = get_option('fb_options');

	if (!empty($options["app_id"])) {
		echo '<script>window.location = ' . json_encode( 'https://www.facebook.com/insights/?' . http_build_query( array( 'sk' => 'ao_' . $options['app_id'] ) ) ) . ';</script>';
	}
}


function fb_hide_wp_comments() {
	print "<script>document.getElementById('comments').style.display = 'none';</script>";
}

function fb_set_wp_comment_status ( $posts ) {
	if ( ! empty( $posts ) && is_singular() ) {
		$posts[0]->comment_status = 'open';
		$posts[0]->post_status = 'open';
	}
	return $posts;
}

function fb_close_wp_comments($comments) {
	return null;
}

function fb_get_comments($options = array()) {
	if (isset($options['href']) == '') {
		$options['href'] = get_permalink();
	}

	$params = fb_build_social_plugin_params($options);

	$output = fb_get_fb_comments_seo();
	$output .= '<div class="fb-comments fb-social-plugin" ' . $params . '></div>';

	return $output;
}

function fb_get_comments_count() {
		return '<iframe src="' . ( is_ssl() ? 'https' : 'http' ) . '://www.facebook.com/plugins/comments.php?' . http_build_query( array( 'href' => get_permalink(), 'permalink' => 1 ) ) . '" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:130px; height:16px;" allowTransparency="true"></iframe>';
}

function fb_comments_automatic($content) {
	if ( comments_open( get_the_ID() ) && post_type_supports( get_post_type(), 'comments' ) ) {
		if ( is_singular() ) {
			$options = get_option('fb_options');

			foreach($options['comments'] as $param => $val) {
				$param = str_replace('_', '-', $param);

				$params['data-' . $param] = $val;
			}

			$content .= fb_get_comments($params);
		}
		else {
			$content .= fb_get_comments_count();
		}
	}

	return $content;
}

function fb_get_fb_comments_seo() {
	global $facebook;
	global $post;

	$url = get_permalink();
	
	$comments_cache_timestamp = get_post_meta($post->ID, 'fb_comments_cache_timestamp', true);
	
	if (!isset($comments_cache_timestamp) || ($comments_cache_timestamp + 900) <= time()) {
		try {
			$comments = $facebook->api('/comments', array('ids' => $url));
		}
		catch (FacebookApiException $e) {
			//error_log(var_export($e,1));
		}
		
		if (!update_post_meta($post->ID, 'fb_comments', $comments)) {
			add_post_meta($post->ID, 'fb_comments', $comments, true);
		}
		if (!update_post_meta($post->ID, 'fb_comments_cache_timestamp', time())) {
			add_post_meta($post->ID, 'fb_comments_cache_timestamp', time(), false);
		}
		
		error_log('got comments from API');
	}
	else {
		$comments = get_post_meta($post->ID, 'fb_comments', true);
		
		error_log('got cached comments');
	}
	
	if ( ! isset( $comments[$url] ) )
		return '';

	$output = '<noscript><ol class="commentlist">';

	foreach ($comments[$url]['comments']['data'] as $key => $comment_info) {
		$unix_timestamp = strtotime($comment_info['created_time']);
		$output .= '<li id="' . esc_attr( 'comment-' . $key ) . '">
			<p><a href="' . esc_url( 'http://www.facebook.com/' . $comment_info['from']['id'], array( 'http', 'https' ) ) . '">' . esc_html( $comment_info['from']['name'] ) . '</a>:</p>
			<p class="metadata">' . date('F jS, Y', $unix_timestamp) . ' at ' . date('g:i a', $unix_timestamp) . '</p>
			' . $comment_info['message'] . '
			</li>';
	}

	$output .= '</ol></noscript>';

	return $output;
}


function fb_get_comments_fields($placement = 'settings', $object = null) {
	$fields_array = fb_get_comments_fields_array();

	fb_construct_fields($placement, $fields_array['children'], $fields_array['parent'], $object);
}

function fb_get_comments_fields_array() {
	$array['parent'] = array('name' => 'comments',
									'type' => 'checkbox',
									'label' => 'Comments',
									'description' => 'The Comments Box is a social plugin that enables user commenting on your site. Features include moderation tools and distribution.',
									'help_link' => 'https://developers.facebook.com/docs/reference/plugins/comments/',
									'image' => plugins_url( '/images/settings_comments.png', dirname(__FILE__))
									);

	$array['children'] = array(array('name' => 'num_posts',
													'label' => 'Number of posts',
													'type' => 'text',
													'default' => 20,
													'help_text' => 'The number of posts to display by default.',
													),
										array('name' => 'width',
													'type' => 'text',
													'default' => '470',
													'help_text' => 'The width of the plugin, in pixels.',
													),
										array('name' => 'colorscheme',
													'label' => 'Color scheme',
													'type' => 'dropdown',
													'default' => 'light',
													'options' => array('light' => 'light', 'dark' => 'dark'),
													'help_text' => 'The color scheme of the plugin.',
													),
										);

	return $array;
}

?>