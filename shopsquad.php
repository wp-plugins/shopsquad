<?php 
/*
Plugin Name: ShopSquad Advisor Plugin
Plugin URI: http://www.shopsquad.com/
Version: v2.3
Author: ShopSquad
Description: A plugin for <a href="http://www.shopsquad.com" target="_blank">ShopSquad</a> advisors
*/

// inits json decoder/encoder object if not already available
global $wp_version;

if ( version_compare( $wp_version, '2.9', '<' ) && !class_exists( 'Services_JSON' ) ) {
	include_once( dirname( __FILE__ ) . '/class.json.php' );
}

class ShopSquad_Widget extends WP_Widget {

	public function ShopSquad_Widget() {
		$widget_ops = array('classname' => 'shopsquad_widget', 'description' => __( 'A ShopSquad widget' ) );

    $control_ops = array(
			'width' => 220,
			'height' => 262,
			'id_base' => 'shopsquad'
		);
		
		parent::WP_Widget('shopsquad', __('ShopSquad'), $widget_ops, $control_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$username = trim( urlencode( $instance['username'] ) );
		# Set default ShopSquad background color if it's blank
		$background_color = trim( $instance['background_color'] );
		if ( empty($background_color) ) {
		  $background_color = "#1167BC";
		}
		if ( empty($username) ) return;

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
		
		if ( 'error' != $stats ) 
		{
      Shopsquad_Widget::print_widget($before_widget, $before_title, $background_color, $username, $stats['thumbnail_url'], $after_title, $after_widget);
    }
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['username'] = trim( strip_tags( stripslashes( $new_instance['username'] ) ) );
		$instance['background_color'] = trim( $new_instance['background_color'] );

		wp_cache_delete( 'widget-shopsquad-' . $this->number , 'widget' );
		wp_cache_delete( 'widget-shopsquad-response-code-' . $this->number, 'widget' );

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('username' => '', 'background_color' => '#1167BC')); 

		$username = esc_attr($instance['username']);
		$background_color = $instance['background_color'];
    # Don't show background color in form if it's the default
    if ($background_color == '#1167BC') {
      $background_color = "";
    }
		echo '<p>';
		echo '  <label for="' . $this->get_field_id('username') . '">' . esc_html__('ShopSquad username:');
		echo '    <input class="widefat" id="' . $this->get_field_id('username') . '" name="' . $this->get_field_name('username') . '" type="text" value="' . $username . '" />';
		echo '  </label>';
		echo '</p>';
		echo '<p>';
		echo '  <label for="' . $this->get_field_id('background_color') . '">' . esc_html__('Enter background color (e.g. blue, red, black, #FA4239). Leave blank for default or enter transparent to make the background transparent:');
		echo '    <input class="widefat" id="' . $this->get_field_id('background_color') . '" name="' . $this->get_field_name('background_color') . '" type="text" value="' . $background_color . '" />';
		echo '  </label>';
		echo '</p>';
		echo '<p>For a list of all background colors, <a href="http://www.w3schools.com/html/html_colornames.asp" target="_blank">click here</a>.</p>';
	}

	public static function print_widget($before_widget, $before_title, $background_color, $username, $thumbnail_url, $after_title, $after_widget) {
            print <<<END
$before_widget 

<style>
#shopsquad_widget {
  border:2px solid #777; 
  text-align:center; 
  font-family: 'Lato', arial, sans-serif;
  -moz-border-radius: 15px;
  border-radius: 15px;
/*  height:262px; */
  line-height:1.2;
}
#shopsquad_widget .header {height:58px; padding:10px}
#shopsquad_widget .header img {padding:0; margin:0; border:none; max-width:100%;}
#shopsquad_widget .header .logo {margin:10px 0px;float:right;}
#shopsquad_widget .header .thumbnail {float:left;}
#shopsquad_widget .header .thumbnail img {
  -moz-border-radius: 15px;
  border-radius: 15px;
  -moz-box-shadow: 2px 2px 2px #444;
  -webkit-box-shadow: 2px 2px 2px #444;
  box-shadow: 2px 2px 2px #444;
}
#shopsquad_widget .body {
/*  height:91px;  */
  color:black;
  font-size:15px; 
  background-color:white; 
  padding-top:15px; 
  padding-bottom:15px;
/*  margin-left:2px;
  margin-right:2px;
*/
  border-top: 2px solid #777;
  border-bottom: 2px solid #777;
}
#shopsquad_widget .body div {}
#shopsquad_widget .body div .top_advisor {padding:0;}
#shopsquad_widget .body div .get_advice {padding-top:14px;}
#shopsquad_widget .body p a {
  padding:5px 9px;
  border-radius:15px; 
  border:1px solid #666;
  color:black;
  text-shadow: 0 1px 1px rgba(255, 255, 255, 0.44);
  background: linear-gradient(top, #FFE500 0%,#FF9600 100%);
  background: -moz-linear-gradient(top, #FFE500 0%, #FF9600 100%);
  background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#FFE500), color-stop(100%,#FF9600));
  background: -webkit-linear-gradient(top, #FFE500 0%,#FF9600 100%);
  background: -o-linear-gradient(top, #FFE500 0%,#FF9600 100%);
  background: -ms-linear-gradient(top, #FFE500 0%,#FF9600 100%);
  background: linear-gradient(top, #FFE500 0%,#FF9600 100%);  
  text-decoration: none;
  -webkit-box-shadow: 2px 3px 5px 0 rgba(0, 0, 0, 0.52);
  -moz-box-shadow: 2px 3px 5px 0 rgba(0, 0, 0, 0.52);
  box-shadow: 2px 3px 5px 0 rgba(0, 0, 0, 0.52);
}
#shopsquad_widget .body p {}
#shopsquad_widget .footer {padding-top:10px; padding-bottom:10px;}
#shopsquad_widget p {-webkit-margin-after:0; -webkit-margin-before:0;} /* Remove line breaks from p tags */
#shopsquad_widget .footer .shopping_expert {font-size:12px; padding-bottom:3px;} /* Are you a shopping expert? */
#shopsquad_widget .footer p a {font-size:14px; text-decoration:none; font-weight:bold; text-shadow:0px 0px 12px #fff;} /* Join the Squad */
#shopsquad_widget .clearfix:after {content:"."; display:block; height:0; clear:both; visibility:hidden; font-size:0; line-height:0;}

</style>
<div id="shopsquad_widget" style="background-color:$background_color;">
  <div class="header">
    <div class="logo">
      <a href='http://www.shopsquad.com?vsrc=$username' target='_blank'>
        <img src='http://production.static.shopsquad.com/assets/blog_widgets/shopsquad_logo.png'  width="124" height="50" />
      </a>
    </div>
    <div class="thumbnail">
      <a href='http://www.shopsquad.com/$username?vsrc=$username' target='_blank'>
        <img src='$thumbnail_url' width="67" height="67" />
      </a>
    </div>
  </div>
  <div class="body clearfix">
    <div>
      <p class="top_advisor">
        I'm a top Advisor on ShopSquad, where you get exclusive discounts based on what you own:
      </p>
      <p class="get_advice">
        <a href='http://www.shopsquad.com/$username?vsrc=$username' target='_blank'>See What I Own</a>
      </p>
    </div>
  </div>
  <div class="footer">
    <p class="shopping_expert">Are you a shopping expert?</p>
    <p><a href='http://www.shopsquad.com?vsrc=$username' target='_blank'>Join the Squad!</a></p>
  </div>
</div>
<script type="text/javascript">
// Rescale logo and thumbnail based on the actual widget width so they come on the same line
function ShopSquad_resizeImages() {
  var scaleFactor, widget_element, logo_element, thumbnail_element;
  widget_element = document.getElementById("shopsquad_widget");
  // If the actual plugin width is 220px or more, we should be fine
  if (widget_element.offsetWidth < 220) {
    logo_element = widget_element.getElementsByClassName('logo')[0].getElementsByTagName('img')[0];
    thumbnail_element = widget_element.getElementsByClassName('thumbnail')[0].getElementsByTagName('img')[0];
    // Get scale factor to rescale images by
    scaleFactor = (widget_element.offsetWidth - 24) / (logo_element.offsetWidth + thumbnail_element.offsetWidth); // subtract 20px to account for padding
    // Rescale images
    logo_element.setAttribute('width', scaleFactor * logo_element.getAttribute('width') - 1);
    logo_element.setAttribute('height', scaleFactor * logo_element.getAttribute('height') - 1);
    thumbnail_element.setAttribute('width', scaleFactor * thumbnail_element.getAttribute('width') - 1);
    thumbnail_element.setAttribute('height', scaleFactor * thumbnail_element.getAttribute('height') - 1);
  }
}
ShopSquad_resizeImages();
function ShopSquad_setWhiteFooterTextIfDefaultBackground() {
  var widget_element = document.getElementById("shopsquad_widget");
  if (widget_element.style.backgroundColor.match(/#1167bc/i) || widget_element.style.backgroundColor.match(/rgb.+17.+103.+188/)) {
    widget_element.getElementsByClassName("footer")[0].getElementsByTagName('p')[0].style.color = "white";
    widget_element.getElementsByClassName("footer")[0].getElementsByTagName('a')[0].style.color = "white";
  }
}
ShopSquad_setWhiteFooterTextIfDefaultBackground();
</script>

$after_widget
END;
    
    
    }
}

add_action( 'widgets_init', 'shopsquad_widget_init' );
function shopsquad_widget_init() {
	register_widget('ShopSquad_Widget');
}


?>