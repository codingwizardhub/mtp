function display_bookings_table() {
    global $wpdb;
    if (isset($_POST['save_cleaner'])) {
        foreach ($_POST['cleaner'] as $booking_id => $cleaner_id) {
            update_post_meta($booking_id, 'assigned_cleaner', intval($cleaner_id));
        }
    }

    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $per_page = 50;
    $checkin_filter = isset($_GET['checkin_date']) ? $_GET['checkin_date'] : '';
    $checkout_filter = isset($_GET['checkout_date']) ? $_GET['checkout_date'] : '';
    $name_filter = isset($_GET['customer_name']) ? $_GET['customer_name'] : '';
    $email_filter = isset($_GET['customer_email']) ? $_GET['customer_email'] : '';
    $rooms_filter = isset($_GET['rooms']) ? $_GET['rooms'] : '';
    $customer_postcode = isset($_GET['customer_postcode']) ? strtoupper($_GET['customer_postcode']) : '';
    $balance_filter = isset($_GET['balance']) ? $_GET['balance'] : '';
	$booking_type_filter = isset($_GET['booking_type']) ? $_GET['booking_type'] : '';
	$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

    $meta_query = array('relation' => 'AND');

    if (!empty($checkin_filter)) {
        $meta_query[] = array(
            'key' => 'mphb_check_in_date',
            'value' => $checkin_filter,
            'compare' => '>=',
            'type' => 'DATE'
        );
    }

    if (!empty($checkout_filter)) {
        $meta_query[] = array(
            'key' => 'mphb_check_out_date',
            'value' => $checkout_filter,
            'compare' => '<=',
            'type' => 'DATE'
        );
    }

    if (!empty($name_filter)) {
        $name_filter = strtolower($name_filter);
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => 'mphb_first_name',
                'value' => $name_filter,
                'compare' => 'LIKE',
            ),
            array(
                'key' => 'mphb_last_name',
                'value' => $name_filter,
                'compare' => 'LIKE',
            ),
            array(
                'key' => 'mphb_full_name',
                'value' => $name_filter,
                'compare' => 'LIKE',
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => 'mphb_first_name',
                    'value' => explode(' ', $name_filter)[0],
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'mphb_last_name',
                    'value' => isset(explode(' ', $name_filter)[1]) ? explode(' ', $name_filter)[1] : '',
                    'compare' => 'LIKE',
                )
            )
        );
    }

    if (!empty($email_filter)) {
        $email_filter_escaped = '%' . $wpdb->esc_like($email_filter) . '%';
        $email_query = $wpdb->prepare("SELECT customer_id FROM {$wpdb->prefix}mphb_customers WHERE LOWER(email) LIKE %s", $email_filter_escaped);
        $customer_ids = $wpdb->get_col($email_query);
        
        if (!empty($customer_ids)) {
            $meta_query[] = array(
                'key' => 'mphb_customer_id',
                'value' => $customer_ids,
                'compare' => 'IN',
            );
        } else {
            $meta_query[] = array(
                'key' => 'mphb_customer_id',
                'value' => '',
                'compare' => '=',
            );
        }
    }

    $today = current_time('Y-m-d');

    $meta_query[] = array(
        'relation' => 'OR',
        array(
            'key' => 'mphb_check_in_date',
            'value' => array($today, '9999-12-31'),
            'compare' => 'BETWEEN',
            'type' => 'DATE'
        ),
        array(
            'key' => 'mphb_check_out_date',
            'value' => array($today, '9999-12-31'),
            'compare' => 'BETWEEN',
            'type' => 'DATE'
        )
    );

    $query_args = array(
        'post_type' => 'mphb_booking',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'meta_query' => $meta_query,
        'orderby' => 'meta_value',
        'meta_key' => 'mphb_check_in_date',
        'order' => 'ASC',
    );

    $bookings_query = new \WP_Query($query_args);
    $total_bookings = $bookings_query->found_posts;
    $total_pages = $bookings_query->max_num_pages;

$filtered_bookings = [];

while ($bookings_query->have_posts()) {
    $bookings_query->the_post();
    $booking_id = get_the_ID();
    $booking = MPHB()->getBookingRepository()->findById($booking_id);
    $customer_name = $booking->getCustomer()->getName();
    $customer_name = !empty($customer_name) ? esc_html($customer_name) : __('<em>AIRBNB BOOKING</em>', 'motopress-hotel-booking');
    $postcode = strtoupper(get_post_meta($booking_id, 'mphb_zip', true));
    $total_price = $booking->getTotalPrice();
    $payments = MPHB()->getPaymentRepository()->findAll(array(
        'booking_id' => $booking->getId(),
        'post_status' => \MPHB\PostTypes\PaymentCPT\Statuses::STATUS_COMPLETED
    ));
    $total_paid = 0.0;
    if (!empty($payments)) {
        foreach ($payments as $payment) {
            $total_paid += $payment->getAmount();
        }
    }

    // Apply postcode filter
    if (!empty($customer_postcode) && strpos($postcode, str_replace('%', '', $customer_postcode)) === false) {
        continue;
    }

    // Apply balance filter
    if (!empty($balance_filter)) {
        if ($balance_filter == 'Paid' && ($total_paid < $total_price || ($total_price == 0 && $total_paid == 0))) {
            continue;
        } elseif ($balance_filter == 'Unpaid' && ($total_paid >= $total_price || ($total_price == 0 && $total_paid == 0))) {
            continue;
        }
    }

    // Apply booking type filter
    if (!empty($booking_type_filter)) {
        if ($booking_type_filter == 'airbnb' && ($total_price != 0 || $total_paid != 0 || $customer_name != '<em>AIRBNB BOOKING</em>')) {
            continue;
        } elseif ($booking_type_filter == 'website' && ($total_price == 0 && $total_paid == 0 && $customer_name == '<em>AIRBNB BOOKING</em>')) {
            continue;
        }
    }

    // Apply status filter
    $status = $booking->getStatus();
    if (!empty($status_filter) && $status_filter != $status) {
        continue;
    }

    $filtered_bookings[] = $booking_id;
}
wp_reset_postdata();

if (!empty($filtered_bookings)) {
    $bookings_query = new \WP_Query(array(
        'post_type' => 'mphb_booking',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'post__in' => $filtered_bookings,
        'orderby' => 'meta_value',
        'meta_key' => 'mphb_check_in_date',
        'order' => 'ASC',
    ));
}
	
    $cleaners = get_terms(array(
        'taxonomy' => 'cleaner',
        'hide_empty' => false,
    ));
    ?>

    <div class="wrap">
        <h1><?php _e('Ongoing Bookings', 'motopress-hotel-booking'); ?></h1>

        <form method="get" action="">
            <input type="hidden" name="page" value="ongoing-bookings">
            <div class="filter-row">
                <label for="checkin_date"><?php _e('Check-in Date', 'motopress-hotel-booking'); ?></label>
                <input type="date" name="checkin_date" id="checkin_date" value="<?php echo esc_attr($checkin_filter); ?>">
                <label for="checkout_date"><?php _e('Check-out Date', 'motopress-hotel-booking'); ?></label>
                <input type="date" name="checkout_date" id="checkout_date" value="<?php echo esc_attr($checkout_filter); ?>">
                <label for="customer_name"><?php _e('Customer Name', 'motopress-hotel-booking'); ?></label>
                <input type="text" name="customer_name" id="customer_name" value="<?php echo esc_attr($name_filter); ?>">
                <label for="customer_email"><?php _e('Customer Email', 'motopress-hotel-booking'); ?></label>
                <input type="text" name="customer_email" id="customer_email" value="<?php echo esc_attr($email_filter); ?>">
                <label for="rooms"><?php _e('Rooms', 'motopress-hotel-booking'); ?></label>
                <input type="text" name="rooms" id="rooms" value="<?php echo esc_attr($rooms_filter); ?>">
				<br>
                <label for="customer_postcode"><?php _e('Customer Postcode', 'motopress-hotel-booking'); ?></label>
                <input type="text" name="customer_postcode" id="customer_postcode" value="<?php echo esc_attr($customer_postcode); ?>">
				<label for="booking_type"><?php _e('Booking Type', 'motopress-hotel-booking'); ?></label>
				<select name="booking_type" id="booking_type">
					<option value=""><?php _e('All', 'motopress-hotel-booking'); ?></option>
					<option value="airbnb" <?php selected($booking_type_filter, 'airbnb'); ?>><?php _e('AIRBNB Bookings', 'motopress-hotel-booking'); ?></option>
					<option value="website" <?php selected($booking_type_filter, 'website'); ?>><?php _e('Website Bookings', 'motopress-hotel-booking'); ?></option>
				</select>
                <label for="balance"><?php _e('Balance', 'motopress-hotel-booking'); ?></label>
                <select name="balance" id="balance">
                    <option value=""><?php _e('All', 'motopress-hotel-booking'); ?></option>
                    <option value="Paid" <?php selected($balance_filter, 'Paid'); ?>><?php _e('Paid', 'motopress-hotel-booking'); ?></option>
                    <option value="Unpaid" <?php selected($balance_filter, 'Unpaid'); ?>><?php _e('Unpaid', 'motopress-hotel-booking'); ?></option>
                </select>
				<label for="status"><?php _e('Status', 'motopress-hotel-booking'); ?></label>
				<select name="status" id="status">
					<option value=""><?php _e('All', 'motopress-hotel-booking'); ?></option>
					<option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Confirmed', 'motopress-hotel-booking'); ?></option>
					<option value="draft" <?php selected($status_filter, 'draft'); ?>><?php _e('Draft', 'motopress-hotel-booking'); ?></option>
					<option value="abandoned" <?php selected($status_filter, 'abandoned'); ?>><?php _e('Abandoned', 'motopress-hotel-booking'); ?></option>
				</select>
                <input type="submit" class="button button-primary" value="<?php _e('Filter', 'motopress-hotel-booking'); ?>">
            </div>
        </form>

        <form method="post">
        <div class="tablenav">
            <div class="tablenav-pages" style="float: right;">
                <?php
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;', 'motopress-hotel-booking'),
                    'next_text' => __('&raquo;', 'motopress-hotel-booking'),
                    'total' => $total_pages,
                    'current' => $paged
                ));

                if ($page_links) {
                    echo $page_links;
                }
                ?>
            </div>
            <div class="alignleft actions">
                <input type="submit" name="save_cleaner" class="button button-primary" value="<?php _e('Save Changes', 'motopress-hotel-booking'); ?>">
            </div>
        </div>

        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Full Name', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Email', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Phone', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Check-in', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Check-out', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Status', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Postcode', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Total Guests', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Rooms', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Price', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Cleaners', 'motopress-hotel-booking'); ?></th>
                        <th><?php _e('Actions', 'motopress-hotel-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings_query->have_posts()) : ?>
                        <?php while ($bookings_query->have_posts()) : $bookings_query->the_post();
                            $booking_id = get_the_ID();
                            $booking = MPHB()->getBookingRepository()->findById($booking_id);
                            $customer_name = $booking->getCustomer()->getName();
                            $customer_name = !empty($customer_name) ? esc_html($customer_name) : __('<em>AIRBNB BOOKING</em>', 'motopress-hotel-booking');
                            $postcode = strtoupper(get_post_meta($booking_id, 'mphb_zip', true));
                            $adults_total = 0;
                            $children_total = 0;

                            $reserved_rooms = $booking->getReservedRooms();
                            if (!empty($reserved_rooms) && !$booking->isImported()) {
                                foreach ($reserved_rooms as $reserved_room) {
                                    $adults_total += $reserved_room->getAdults();
                                    $children_total += $reserved_room->getChildren();
                                }
                            }

                            $total_guests = $adults_total + $children_total;
                            $rooms = $booking->getReservedRooms();

                            $payments = MPHB()->getPaymentRepository()->findAll(array(
                                'booking_id' => $booking->getId(),
                                'post_status' => \MPHB\PostTypes\PaymentCPT\Statuses::STATUS_COMPLETED
                            ));
                            $total_price = $booking->getTotalPrice();
                            $total_paid = 0.0;

                            if (!empty($payments)) {
                                foreach ($payments as $payment) {
                                    $total_paid += $payment->getAmount();
                                }
                            }

                            $payment_link = admin_url('edit.php?post_type=mphb_payment&s=' . $booking_id);

                            // Fetch assigned cleaner
                            $assigned_cleaner = get_post_meta($booking_id, 'assigned_cleaner', true);
                        ?>
                            <tr>
                                <td><?php echo $booking_id; ?></td>
                                <td><?php echo $customer_name; ?></td>
                                <td><?php echo esc_html($booking->getCustomer()->getEmail()); ?></td>
                                <td><?php echo esc_html($booking->getCustomer()->getPhone()); ?></td>
                                <td><?php echo esc_html($booking->getCheckInDate()->format('Y-m-d')); ?></td>
                                <td><?php echo esc_html($booking->getCheckOutDate()->format('Y-m-d')); ?></td>
                                <td><?php echo esc_html($booking->getStatus()); ?></td>
                                <td><?php echo esc_html($postcode); ?></td>
                                <td>
                                    <?php
                                    if ($adults_total == 0 && $children_total == 0) {
                                        echo __('<em>null</em>', 'motopress-hotel-booking');
                                    } else {
                                        if ($adults_total > 0) {
                                            echo 'Adults: ' . $adults_total . '<br>';
                                        }
                                        if ($children_total > 0) {
                                            echo 'Children: ' . $children_total . '<br>';
                                        }
                                        echo '<b>Total:</b> ' . '<b>'.$total_guests.'</b>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php foreach ($rooms as $reserved_room) : ?>
                                        <div>
                                            <?php
                                            $room_id = $reserved_room->getRoomId();
                                            $room_name = MPHB()->getRoomRepository()->findById($room_id)->getTitle();
                                            ?>
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $room_id . '&action=edit')); ?>">
                                                <?php echo esc_html($room_name); ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php 
                                    $formatted_price = mphb_format_price($booking->getTotalPrice()); // Corrected to fetch total price of the booking
                                    $formatted_paid = mphb_format_price($total_paid);
                                    echo "Price: $formatted_price<br>";
                                    echo "<a href='$payment_link'>Paid: $formatted_paid</a>"; 
                                    ?>
                                </td>
                                <td>
                                    <select name="cleaner[<?php echo esc_attr($booking_id); ?>]">
                                        <option value=""><?php _e('None', 'motopress-hotel-booking'); ?></option>
                                        <?php foreach ($cleaners as $cleaner) : ?>
                                            <?php $selected = $cleaner->term_id == $assigned_cleaner ? ' selected="selected"' : ''; ?>
                                            <option value="<?php echo esc_attr($cleaner->term_id); ?>"<?php echo $selected; ?>><?php echo esc_html($cleaner->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $booking_id . '&action=edit')); ?>" class="button button-primary">
                                        <?php _e('Edit', 'motopress-hotel-booking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="13"><?php _e('No ongoing or future bookings found.', 'motopress-hotel-booking'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;', 'motopress-hotel-booking'),
                        'next_text' => __('&raquo;', 'motopress-hotel-booking'),
                        'total' => $total_pages,
                        'current' => $paged
                    ));

                    if ($page_links) {
                        echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
                    }
                    ?>
                </div>
            </div>

            <p><input type="submit" name="save_cleaner" class="button button-primary" value="<?php _e('Save Changes', 'motopress-hotel-booking'); ?>"></p>
        </form>
    </div>
<?php
    wp_reset_postdata();
}
