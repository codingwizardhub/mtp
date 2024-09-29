<?php

// enqueue parent stylesheet
add_action('wp_enqueue_scripts', 'oceanica_child_wp_enqueue_scripts');
function oceanica_child_wp_enqueue_scripts()
{

	$parent_theme = wp_get_theme(get_template());
	$child_theme = wp_get_theme();

	// Enqueue the parent stylesheet
	wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css', array(), $parent_theme['Version']);

	wp_enqueue_style('oceanica-style', get_stylesheet_uri(), array('parent-style'), $child_theme['Version']);

    // pure.io grid
    wp_enqueue_style('pure-base-style', 'https://unpkg.com/purecss@2.1.0/build/base-min.css', array(), $parent_theme['Version']);
    wp_enqueue_style('pure-grid-style', 'https://unpkg.com/purecss@2.1.0/build/grids-min.css', array(), $parent_theme['Version']);
    wp_enqueue_style('pure-responsive-style', 'https://unpkg.com/purecss@2.1.0/build/grids-responsive-min.css', array(), $parent_theme['Version']);


	if (is_page('39')) { // Chalets
		wp_enqueue_style('chalet-listing-style', get_stylesheet_directory_uri() . '/chalet-listing.css', array(), $parent_theme['Version']);
	}

	if (is_page('59')) { // Search Results
		wp_enqueue_style('search-listing-style', get_stylesheet_directory_uri() . '/search-listing.css', array(), $parent_theme['Version']);
	}

	if ('mphb_room_type' == get_post_type()) {
		wp_enqueue_style('chalet-detail-style', get_stylesheet_directory_uri() . '/chalet-detail.css', array(), $parent_theme['Version']);
	}

	// Enqueue the parent rtl stylesheet
	if (is_rtl()) {
		wp_enqueue_style('parent-style-rtl', get_template_directory_uri() . '/rtl.css', array(), $parent_theme['Version']);
	}
}

add_action( 'wp_footer', 'oceanica_child_wp_footer_scripts' );
function oceanica_child_wp_footer_scripts(){
  ?>
  <script src="https://kit.fontawesome.com/45b18c07b0.js" crossorigin="anonymous"></script>
  <?php
}

add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_tax()) { //for custom post types
        $title = sprintf(__('%1$s'), single_term_title('', false));
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    }
    return $title;
});

add_shortcode('chalet_categories', 'chalet_cat_shortcode');
/**
 * this function outputs your category list where you
 * use the [my_cat_list] shortcode.
 */
function chalet_cat_shortcode($atts = '')
{

	$args = '';
	$output_string = '';	

	if (isset($_GET['tom'])){
	global $wpdb;
    $chalets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vikbooking_rooms");
	
	$output_string = '';
	$output_string .= '<div class="ci-category-container">';
	$output_string .=  '<div class="pure-g">';
	foreach ( $chalets as $chalet ) {
			
			$meta = get_post_meta( get_the_ID() );
			
			
			$output_string .=  '<div class="pure-u-24-24 pure-u-sm-24-24 pure-u-md-12-24 pure-u-lg-12-24 pure-u-xl-12-24">';
			$output_string .=  '<div class="ci-category-title"><a href="' . $chalet->alias . '/" data-id="' . get_the_ID() . '">' . $chalet->name . '</a></div>';
            $output_string .=  '<div class="ci-category-image"><a href="' . $chalet->alias . '/"><img src="wp-content/plugins/vikbooking/site/resources/uploads/' . $chalet->img . '" class="pure-img" /></a></div>';
			$output_string .= '<div class="ci-category-attributes">';
			$output_string .= '<i class="fa-solid fa-umbrella-beach"></i> Short walk to beach<br>';
			$output_string .= '<i class="fa-solid fa-person"></i> Sleeps ' . $chalet->toadult . '<br>';
			$output_string .= '<i class="fa-solid fa-bed"></i> ' . $chalet->smalldesc . '<br>';
			$output_string .= '<i class="fa-solid fa-parking"></i> ';
			$output_string .= '<i class="fa-solid fa-wifi"></i> ';
			$output_string .= '<i class="fa-solid fa-history"></i> ';
			$output_string .= '<i class="fa-solid fa-dog"></i> ';
			$output_string .= '<i class="fa-solid fa-user-lock"></i> ';
			
			
			$output_string .= '</div>';
			
			$output_string .= '<a href="' . $chalet->alias . '/" class="btn">More Info / Book</a>';
			
			
			$output_string .=  '</div>';

            
	}
		$output_string .=  '</div>';
        $output_string .=  '</div>';
	    
		return $output_string;

    }

  if(is_array($atts)){
	if(!empty($atts['cat_id'])) {
		$args = array(
			'post_type' => 'mphb_room_type',
			'posts_per_page' => 9999,
			'orderby'   => 'order',
			'order' => 'ASC',
			'tax_query' => array(
				array(
					'taxonomy' => 'mphb_room_type_category',
					'field' => 'term_id',
					'terms' => $atts['cat_id'],
				),
			),
		);
	  }
  }

  if($args !== '') {
    $args = array(
        'post_type' => 'mphb_room_type',
        'posts_per_page' => 9999,
        'orderby'   => 'order',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'mphb_room_type_category',
                'field' => 'term_id',
                'terms' => array('61','60','92','93','95'),
            ),
        ),
    );
  }




        $loop = new WP_Query($args);
        if ($loop->have_posts()) {

		    
            $output_string .= '<div class="ci-category-container">';
            
			if(is_array($atts)){
			 if(!empty($atts['cat_slug'])){
				$output_string .=  '<h2><a href="' . get_permalink( get_page_by_path($atts['cat_slug']) ) . '">' . $atts['cat_name'] . '</a></h2>';
			 }
		    }

            $output_string .=  '<div class="pure-g">';

            while ($loop->have_posts()) : $loop->the_post();
			
			$meta = get_post_meta( get_the_ID() );
			
			
			$output_string .=  '<div class="pure-u-24-24 pure-u-sm-24-24 pure-u-md-12-24 pure-u-lg-12-24 pure-u-xl-12-24">';
			$output_string .=  '<div class="ci-category-title"><a href="' . get_permalink() . '" data-id="' . get_the_ID() . '">' . get_the_title() . '</a></div>';
            $output_string .=  '<div class="ci-category-image"><a href="' . get_permalink() . '">' . get_the_post_thumbnail($post = get_the_ID(), $size = 'post-thumbnail', $attr = 'pure-img') . '</a></div>';
			$output_string .= '<div class="ci-category-attributes">';
			$output_string .= '<i class="fa-solid fa-umbrella-beach"></i> ' . $meta['mphb_view'][0] . '<br>';
			$output_string .= '<i class="fa-solid fa-person"></i> Sleeps ' . $meta['mphb_adults_capacity'][0] . '<br>';
			$output_string .= '<i class="fa-solid fa-bed"></i> ' . $meta['mphb_bed'][0] . '<br>';
			foreach (mphb_tmpl_get_room_type_facilities() as $amenity)
			{
				switch ($amenity->name){
					case 'Free parking on premises':
						$output_string .= '<i class="fa-solid fa-parking"></i> ';
					break;
					case 'Fast Free Wi-Fi':
						$output_string .= '<i class="fa-solid fa-wifi"></i> ';
					break;
					case 'Long-term stays allowed':
						$output_string .= '<i class="fa-solid fa-history"></i> ';
					break;
					case 'Pets allowed':
						$output_string .= '<i class="fa-solid fa-dog"></i> ';
					break;
					case 'Self check-in':
						$output_string .= '<i class="fa-solid fa-user-lock"></i> ';
					break;
				}
				$output_string .= $amenity->name;
				$output_string .= '<br>';
			}
			$output_string .= '</div>';
			
			$output_string .= '<a href="' . get_permalink() . '" class="btn">More Info / Book</a>';
			
			
			$output_string .=  '</div>';
            endwhile;

            $output_string .=  '</div>';
            $output_string .=  '</div>';
        }

        return $output_string;

}

function meks_which_template_is_loaded() {
	if ( is_super_admin() ) {
		global $template;
		// print_r( $template );
	}
}
 
add_action( 'wp_footer', 'meks_which_template_is_loaded' );

// Customise the WordPress gallery html layout
add_filter('post_gallery','customFormatGallery',10,2);
function customFormatGallery($string,$attr){

	$after_output = '';

	$output = '<style>

	.page-title, .entry-title {
		font-size: 28px !important;
	}
	.content-area.full-width .site-main > .hentry {
		margin-top: 0px !important;
	}
	
	body.single .site-main .entry-header, body.page .site-main .entry-header {
		margin: 0 !important;
		padding: 0 !important;
		padding-bottom: 20px !important;
		margin-bottom: 30px !important;
		
	}
	.content-area.full-width .site-main > .hentry {
		border: none !important;
		padding: 0 20px 20px 20px !important;
	}
	
	.page-header, .entry-header {
	
	}
	
	.nc-gallery{
		display: block; width: 100%; background-repeat: no-repeat; background-position: center center; background-size: cover;
	}
	.nc-gallery-container {
		border-radius: 0px;
		margin-bottom: 20px;
		overflow: hidden;
		position: relative;
	}
	.nc-pad{
		padding: 0 10px 10px 0;
	}

	#content .post-thumbnail {
        background-image: none !important;
	}	

	#content .post-thumbnail img {
		height: 0px !important;
	}	

	@media screen and (min-width: 76.5em){
		#content .post-thumbnail img {
			height: 100px !important;
		}	
	}

	.nc-remaining {
	   padding: 7px;
	   text-align: right;
	   position: absolute;
	   top: 10px;
	   right: 20px;
	   pointer-events: none;
	   background-color: rgba(255, 255, 255, 0.7);
	}

	.single .mphb_room_type .entry-content h2 {
       border-top: none !important;
	   padding-top: 0px !important;
	}

	</style>';

	$output .= '<div class="nc-gallery-container">';
    $output .= '<div class="pure-g">';
	$output .= '<div class="pure-u-24-24 pure-u-sm-24-24 pure-u-md-12-24 pure-u-lg-12-24 pure-u-xl-12-24">';

	$pre_posts = get_posts(array('include' => $attr['ids'],'post_type' => 'attachment', 'orderby' => 'title', 'order' => 'ASC'));

	foreach($pre_posts as $pre_imagePost){
		$multiArray[] = array("id" => $pre_imagePost->ID, "name" => wp_get_attachment_caption($pre_imagePost->ID));
	}

	$tmp = array();

	foreach($multiArray as $ma){
      if($ma["name"] == ''){
		$tmp[] = '9999';
	  } else {
		$tmp[] = $ma["name"];
	  }
	}
		
	array_multisort($tmp, SORT_ASC, $multiArray);

	$count= 1;

    foreach($multiArray as $imagePost){
		if($count < 6){	
     if($count == 1){
		$output .= "<div class='nc-pad'><a href='".wp_get_attachment_image_src($imagePost['id'], 'mihan-large')[0]."' class='nc-gallery' style='height: 310px; background-image: url(".wp_get_attachment_image_src($imagePost['id'], 'mihan-medium')[0].")'></a></div>";
		$output .= '</div><div class="pure-u-24-24 pure-u-sm-24-24 pure-u-md-12-24 pure-u-lg-12-24 pure-u-xl-12-24"><div class="pure-g">';
	 } else {
		$output .= '<div class="pure-u-24-24 pure-u-sm-24-24 pure-u-md-12-24 pure-u-lg-12-24 pure-u-xl-12-24">';
		$output .= "<div class='nc-pad'><a href='".wp_get_attachment_image_src($imagePost['id'], 'mihan-large')[0]."' class='nc-gallery' style='height: 150px; background-image: url(".wp_get_attachment_image_src($imagePost['id'], 'mihan-medium')[0].")'></a>";
		$output .= '</div></div>';
	 }
		} else {
			$after_output .= '<div class="pure-u-24-24" style="display: none;"><a style="display: none;" href="'.wp_get_attachment_image_src($imagePost['id'], 'mihan-large')[0].'" class="nc-gallery" style="height: 1px;"></a></div>';
		}

	  $count++;
	}

    $remaining = ($count - 6);
    $output .= '<div class="pure-u-24-24"><div class="nc-remaining">+ ' . $remaining . ' images</div>';
	$output .= $after_output;
    $output .= "</div></div><div class=\"clear\"></div></div>";
    return $output;
}

// admin hide notices on every page

function pr_disable_admin_notices() {
    global $wp_filter, $pagenow;
    if ($pagenow != 'index.php') {
        if ( is_user_admin() ) {
            if ( isset( $wp_filter['user_admin_notices'] ) ) {
                            unset( $wp_filter['user_admin_notices'] );
            }
        } elseif ( isset( $wp_filter['admin_notices'] ) ) {
                    unset( $wp_filter['admin_notices'] );
        }
        if ( isset( $wp_filter['all_admin_notices'] ) ) {
                    unset( $wp_filter['all_admin_notices'] );
        }
    }    
}
add_action( 'admin_print_scripts', 'pr_disable_admin_notices' );

/*
function register_properties_and_cleaners_menu() {
    add_menu_page(
        __('Ongoing Bookings', 'textdomain'),
        __('Ongoing Bookings', 'textdomain'),
        'manage_options',
        'ongoing-bookings',
        'display_ongoing_bookings',
        'dashicons-admin-home',
        6
    );
    add_submenu_page(
        'ongoing-bookings',
        __('Calendar', 'textdomain'),
        __('Calendar', 'textdomain'),
        'manage_options',
        'calendar',
        'display_calendar_page'
    );
	/*
    add_submenu_page(
        'ongoing-bookings',
        __('Properties', 'textdomain'),
        __('Properties', 'textdomain'),
        'manage_options',
        'properties',
        'display_properties_page'
    );
	
    add_submenu_page(
        'ongoing-bookings',
        __('Cleaners', 'textdomain'),
        __('Cleaners', 'textdomain'),
        'manage_options',
        'cleaners',
        'cleaners_redirect_page'
    );
    add_submenu_page(
        'ongoing-bookings',
        __('Previous Bookings', 'textdomain'),
        __('Previous Bookings', 'textdomain'),
        'manage_options',
        'previous-bookings',
        'display_previous_bookings'
    );
}
add_action('admin_menu', 'register_properties_and_cleaners_menu');
*/

function cleaners_redirect_page() {
    echo '<script type="text/javascript">';
    echo 'window.location.href="' . admin_url('edit-tags.php?taxonomy=cleaner&post_type=mphb_booking') . '";';
    echo '</script>';
    exit;
}

function register_cleaners_taxonomy() {
    $labels = array(
        'name' => _x('Cleaners', 'taxonomy general name', 'textdomain'),
        'singular_name' => _x('Cleaner', 'taxonomy singular name', 'textdomain'),
        'search_items' => __('Search Cleaners', 'textdomain'),
        'all_items' => __('All Cleaners', 'textdomain'),
        'edit_item' => __('Edit Cleaner', 'textdomain'),
        'update_item' => __('Update Cleaner', 'textdomain'),
        'add_new_item' => __('Add New Cleaner', 'textdomain'),
        'new_item_name' => __('New Cleaner Name', 'textdomain'),
        'menu_name' => __('Cleaners', 'textdomain'),
    );

    $args = array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'cleaner'),
    );

    register_taxonomy('cleaner', array('mphb_booking'), $args);
}
add_action('init', 'register_cleaners_taxonomy', 0);

function set_active_menu_class($parent_file) {
    global $submenu_file, $current_screen;
    if (is_object($current_screen) && isset($current_screen->taxonomy) && $current_screen->taxonomy === 'cleaner') {
        $parent_file = 'ongoing-bookings';
        $submenu_file = 'cleaners';
    }

    return $parent_file;
}
add_filter('parent_file', 'set_active_menu_class');





function add_custom_meta_boxes() {
    add_meta_box(
        'sendcheckin',
        __('Send Check-in Details', 'motopress-hotel-booking'),
        'render_checkin_meta_box',
        'mphb_booking', // Ensure this matches your booking post type
        'side',
        'low'
    );
}
add_action('add_meta_boxes', 'add_custom_meta_boxes');

function render_checkin_meta_box($post) {
    wp_nonce_field('mphb_send_checkin', 'mphb_send_checkin_nonce');
    ?>
    <p>
        <input type="hidden" id="mphb_post_id" value="<?php echo esc_attr($post->ID); ?>" />
        <button id="send-checkin-details" class="button button-primary button-large">
            <?php esc_attr_e('Send Check-in Details', 'motopress-hotel-booking'); ?>
        </button>
    </p>
    <p id="checkin-email-response"><?php esc_html_e('Send a copy of the Check-in Details email to the customer`s email address.', 'motopress-hotel-booking'); ?></p>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#send-checkin-details').on('click', function(e) {
                e.preventDefault(); // Prevent default form submission

                var postId = $('#mphb_post_id').val();
                var nonce = $('#mphb_send_checkin_nonce').val();

                $.post(ajaxurl, {
                    action: 'send_checkin_details',
                    mphb_post_id: postId,
                    mphb_send_checkin_nonce: nonce
                }, function(response) {
                    $('#checkin-email-response').html(response.message);
                });
            });
        });
    </script>
    <?php
}


add_action('wp_ajax_send_checkin_details', 'send_checkin_details_via_ajax');


function send_checkin_details_email() {
    if (!isset($_REQUEST['mphb_send_checkin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['mphb_send_checkin_nonce'])), 'mphb_send_checkin')) {
        return;
    }

    if (isset($_REQUEST['mphb_post_id'])) {
        $postID = absint($_REQUEST['mphb_post_id']);
        $booking = MPHB()->getBookingRepository()->findById($postID);

        if (!$booking) {
            error_log('Booking not found for post ID: ' . $postID);
            return;
        }

        $customer = $booking->getCustomer();
        $customer_email = $customer->getEmail();
        $customer_first_name = $customer->getFirstName();
        $customer_last_name = $customer->getLastName();

        // Retrieve reserved accommodation details (Room)
        $reserved_rooms = $booking->getReservedRooms();
        if (empty($reserved_rooms)) {
            error_log('No reserved rooms found for booking ID: ' . $booking->getId());
            return;
        }

        // Get the room type ID
        $room_type_id = $reserved_rooms[0]->getRoomId(); // Assuming the first room reserved
        error_log('Room type ID: ' . $room_type_id);

        // Get the template based on the room type ID
        $template_name = get_checkin_template_by_room_type_id($room_type_id);

        if (file_exists($template_name)) {
            error_log('Template found: ' . $template_name);
            ob_start();
            include $template_name;
            $message = ob_get_clean();
        } else {
            error_log('Template not found: ' . $template_name);
            $message = 'No valid check-in template found.';
        }

        $subject = sprintf('norfolkchalets.co.uk - Your booking #%d Check-in Details', $booking->getId());
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: norfolkchalets.co.uk <no-reply@norfolkchalets.co.uk>'
        );

        // Send the email
        if (wp_mail($customer_email, $subject, $message, $headers)) {
            error_log('Email successfully sent to: ' . $customer_email);
        } else {
            error_log('Email failed to send to: ' . $customer_email);
        }
    }
}



function get_checkin_template_by_room_type_id($room_type_id) {
    // Map room type IDs to their corresponding template files
    $template_path = get_stylesheet_directory() . '/checkin/templates/';

    switch ($room_type_id) {
        case 11795:
            return $template_path . 'seaside-retreat-california-sands.php';
        case 2688:
            return $template_path . 'daydreamers-retreat-california-sands.php';
        case 589:
            return $template_path . 'rainbow-retreat-belle-aire.php';
        case 587:
            return $template_path . 'moonlight-retreat-belle-aire.php';
        case 585:
            return $template_path . 'mermaids-retreat-belle-aire.php';
        case 583:
            return $template_path . 'sunsets-sandcastles-sundowner.php';
        case 581:
            return $template_path . 'sunsets-sundaes-sundowner.php';
        case 579:
            return $template_path . 'sunsets-dreams-sundowner.php';
        case 577:
            return $template_path . 'sunsets-seashells-sundowner.php';
        case 508:
            return $template_path . 'the-retreat-131-beach-road.php';
        case 488:
            return $template_path . 'sunsets-stars-sundowner.php';
        case 475:
            return $template_path . 'sunsets-rainbows-sundowner.php';
        case 447:
            return $template_path . 'sunsets-moons-sundowner.php';
        case 414:
            return $template_path . 'sunsets-hearts-sundowner.php';
        default:
            return $template_path . 'default-template.php'; // Fallback template
    }
}






function on_redirect_post_location_checkin($loc) {
    return add_query_arg('checkin_sent', 'true', $loc);
}

add_action('wp_ajax_send_checkin_details', 'send_checkin_details_via_ajax');

function send_checkin_details_via_ajax() {
    // After validation and before sending the email
    error_log('Preparing to send email to ' . $customer_email);

    // Send the email
    if (wp_mail($customer_email, $subject, $message, $headers)) {
        error_log('Email successfully sent to: ' . $customer_email); // Log success
    } else {
        error_log('Email failed to send to: ' . $customer_email); // Log failure
    }
	
	
    error_log('AJAX request received'); // Add this log to confirm the AJAX call is received.
    
    send_checkin_details_email(); // Directly call the email function
    
    wp_send_json_success(array('message' => 'Check-in Details email has been sent.'));
}





function enqueue_fullcalendar_assets() {
    wp_enqueue_script('moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js', array('jquery'), null, true);
    wp_enqueue_script('fullcalendar-js', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js', array('jquery', 'moment-js'), null, true);
    wp_enqueue_style('fullcalendar-css', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css');
    wp_enqueue_script('custom-calendar-js', get_stylesheet_directory_uri() . '/js/custom-calendar.js', array('jquery', 'fullcalendar-js'), null, true);
    wp_enqueue_style('custom-calendar-css', get_stylesheet_directory_uri() . '/css/custom-calendar.css');
}
add_action('admin_enqueue_scripts', 'enqueue_fullcalendar_assets');










