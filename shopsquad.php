<?php 
/*
Plugin Name: ShopSquad Advisor Plugin
Plugin URI: http://www.shopsquad.com/
Version: v1.1
Author: ShopSquad
Description: A plugin for <a href="http://www.shopsquad.com" target="_blank">ShopSquad</a> advisors
*/

// inits json decoder/encoder object if not already available
global $wp_version;

if ( version_compare( $wp_version, '2.9', '<' ) && !class_exists( 'Services_JSON' ) ) {
	include_once( dirname( __FILE__ ) . '/class.json.php' );
}

class ShopSquad_Widget extends WP_Widget {

	function ShopSquad_Widget() {
		$widget_ops = array('classname' => 'widget_shopsquad', 'description' => __( 'Display your ShopSquad stats' ) );
		parent::WP_Widget('shopsquad', __('ShopSquad'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$username = trim( urlencode( $instance['username'] ) );
		if ( empty($username) ) return;
    
		# Display title, which links to ShopSquad homepage
		echo "<style>.widget_shopsquad{border: 1px solid #CECECE;}</style>";
		
		echo "{$before_widget}{$before_title}<a href='" . esc_url( "http://www.shopsquad.com/" ) . "' style='display: block;background: #9FC9E3;padding: 5px 0 0;	text-align: center;	margin-bottom: 5px;' target='_blank'><img src='http://www.shopsquad.com/images/logo_small.png' style='max-height: 30px;'/></a>{$after_title}"; 

		if ( !$stats = wp_cache_get( 'widget-shopsquad-' . $this->number , 'widget' ) ) {

			$shopsquad_widget_url = "http://www.shopsquad.com/{$username}/widget_data";
			
			$response = wp_remote_get( $shopsquad_widget_url, array( 'User-Agent' => 'WordPress.com ShopSquad Widget' ) );
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) 
			{
				$stats = wp_remote_retrieve_body( $response );
				$stats = json_decode( $stats, true );
				$expire = 900;
				if ( !is_array( $stats ) || isset( $stats['error'] ) ) {
					$stats = 'error';
					$expire = 300;
				}
			} else {
				$stats = 'error';
				$expire = 300;
				wp_cache_add( 'widget-shopsquad-response-code-' . $this->number, $response_code, 'widget', $expire);
			}

			wp_cache_add( 'widget-shopsquad-' . $this->number, $stats, 'widget', $expire );
		}
		// show image...
		echo "<a href='http://www.shopsquad.com/{$username}' target='_blank'><img src='".$stats['thumbnail_url']."' style='float: left;padding: 0 5px 10px;'></a>";
		
		if ( 'error' != $stats ) 
		{
			# Display seller rank
			$rank = $stats['rank'];
			$rank_category = $stats['rank_category'];
			$rank_category_id = $stats['rank_category_id'];
			if ($rank_category && $rank_category_id) {
			  echo "<a href='http://www.shopsquad.com/{$username}' target='_blank'>{$username}</a>'s advisor rank in <b>{$rank_category}</b>:
				     <font size='4' color='blue'> <a href='http://www.shopsquad.com/advisors/rankings/{$rank_category_id}/{$rank_category}#{$username}' target='_blank'> #{$rank} </a></font><br />";
			}
			else { # If user is not ranked in any one of the default categories, display overall rank
			  echo "<a href='http://www.shopsquad.com/{$username}' target='_blank'>My Advisor</a> rank:
				     <font size='4' color='blue'> <a href='http://www.shopsquad.com/advisors/rankings/0/overall-rank#{$username}' target='_blank'> #{$rank} </a></font><br />";
			}

			# Display star rating
			echo "<a href='http://www.shopsquad.com/{$username}/ratings' target='_blank'>";
			echo ShopSquad_Widget::image_star_tag($stats['rating']);
			echo "</a>";
			echo "<br />";
			
			# Display online/offline status
			$currently_online = $stats['currently_online'];
			if ($currently_online) {
				echo "I'm online now - <a href='http://www.shopsquad.com/{$username}' target='_blank'>Get live shopping advice!</a><br />";
			} else {
				echo "I'm offline, but <a href='http://www.shopsquad.com/{$username}' target='_blank'>leave me a question!</a><br />";
			}

			# Display seller categories/products
			$categories = $stats['category_names'];
			if (!empty($categories)) {
				echo "I can offer advice on ";
				foreach ($categories as $category)
				{
					# Print "and" before the last category (e.g. "and computers") unless there's only one category
					if (end($categories) == $category && sizeof($categories) > 1) {
						echo "and ";
					}
					echo "<b>{$category}</b>";
					# Print a comma unless it's the last category
					if (end($categories) != $category) {
						echo ", ";
					}
				}
				echo ".";				
			}
		}
		else {
			if ( 401 == wp_cache_get( 'widget-shopsquad-response-code-' . $this->number , 'widget' ) )
				echo '<p>' . esc_html( sprintf( __( 'Error: Please make sure the ShopSquad account is <a href="%s">public</a>.'), 'http://www.shopsquad.com/' ) ) . '</p>';
			else
				echo '<p>' . esc_html__('Error: ShopSquad did not respond. Please wait a few minutes and refresh this page.') . '</p>';
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['username'] = trim( strip_tags( stripslashes( $new_instance['username'] ) ) );

		wp_cache_delete( 'widget-shopsquad-' . $this->number , 'widget' );
		wp_cache_delete( 'widget-shopsquad-response-code-' . $this->number, 'widget' );

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('username' => '') );

		$username = esc_attr($instance['username']);

		echo '<p><label for="' . $this->get_field_id('username') . '">' . esc_html__('ShopSquad username:') .
		' <input class="widefat" id="' . $this->get_field_id('username') . '" name="' . $this->get_field_name('username') . '" type="text" value="' . $username . '" />
		</label></p>';
	}

	# Returns the HTML for the rating image, given a rating from 0 to 5
	static function image_star_tag ($score) {
	    $plugin_directory = WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
    	$tag = '';
	    # if no score, return 'no rating'
	    if ( (! $score) || $score == 0 ) {
	      $tag = "<img alt='No_rating' src='{$plugin_directory}star_no_rating.png' />";
	      return $tag;
	    }

	    # compute the number of full stars, whether there's a half star, and the number of empty stars
	    $num_full_stars = floor($score);

	    $half_star = 0;
	    $last_star = $score - $num_full_stars;  # the score is rounded to the nearest half-star
	    if ($last_star >= 0.75) {
	      $num_full_stars = $num_full_stars + 1;
		  }
	    else if ($last_star >= 0.25) {
	      $half_star = 1;
	    }

	    $num_empty_stars = 5 - $num_full_stars - $half_star;

	    # print full stars, then half star, then empty stars
	    for ($i = 0; $i < $num_full_stars; $i++) {
	      $tag .= "<img alt='Full_star' src='{$plugin_directory}star_full.png' height='10px' width='10px' />";
	    }
	    if ($half_star == 1) {
	      $tag .= "<img alt='Half_star' src='{$plugin_directory}star_half.png' height='10px' width='10px' />";
	    }
	    for ($i = 0; $i < $num_empty_stars; $i++) {
	      $tag .= "<img alt='Empty_star' src='{$plugin_directory}star_empty.png' height='10px' width='10px' />";
		}
		return $tag;
	}
}

add_action( 'widgets_init', 'shopsquad_widget_init' );
function shopsquad_widget_init() {
	register_widget('ShopSquad_Widget');
}


?>