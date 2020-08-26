<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*
Plugin Name: Fola Woocommerce Currency Converter
Plugin URI:
Description: Adds multiple currency to woocommerce checkout page. Requires woocommerce to work.
Version: 1.0
Author: Folaranmi Jesutofunmi
Author URI: http://symmetrical-happiness.com
License: GPLv2
*/


/**
 * Check if WooCommerce is active
 **/

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    //to set the default naira to dollar conversion rate when you install/update plugin
    register_activation_hook( __FILE__, 'fwcc_set_default_options');

    function fwcc_set_default_options()
    {
        //'fwcc_dollar_to_naira_rate' is the rate of dollar to naira stored in db. 
        //The value stored is the dollar value. Hence fwcc_dollar_to_naira_rate = 400, means 400 dollars to 1 naira
        if ( get_option( 'fwcc_version' ) === false )
        {
            $new_fwcc_options['fwcc_dollar_to_naira_rate'] = '400';
            $new_fwcc_options['fwcc_version'] = '1.0';

            add_option('fwcc_options', $new_fwcc_options);

        }else if (get_option( 'fwcc_version' ) < 1.0)
        {
            //update the version if the version is less than 1.0
            $update_fwcc_options['fwcc_version'] = '1.0';
            update_option('fwcc_options', $update_fwcc_options);
        }
    }

    add_action('admin_menu', 'fwcc_settings_menu');

    function fwcc_settings_menu()
    {
        add_options_page( 'Currency Converter',
    'Currency Converter', 'manage_options',
    'fwcc-settings-menu', 'fwcc_config_page' );
    }

    //function to reder html on the currency converter settings page.
    function fwcc_config_page()
    {
        $fwcc_options = get_option('fwcc_options');
    ?>

    <div class="fwcc_currency_converter">

    <h2>Dollar To Naira Currency Converter</h2>
     <p>NB: The value stored is the field below is the dollar value. Hence a value of 400, means the rate is 400 dollars to 1 naira</p>
    <?php 
    //to display a message after settings has been saved
    if ( isset( $_GET['message'] ) && $_GET['message'] == '1' )
    { ?>

    <div id='message' class='updated fade'><p><strong>Settings Saved</strong></p></div>

    <?php } ?>

        <form action="admin-post.php" method="post">

            <input type="hidden" name="action" value="save_fwcc_options" />

            <!-- Adding security through hidden referrer field -->
            <?php wp_nonce_field( 'fwcc' ); ?>

            <label style="margin-right:40px;">Dollar To Naira Rate:</label>
            <input style="margin-right:20px;" name="fwcc_dollar_to_naira_rate" placeholder="Dollar To Naira Rate" value="<?php echo esc_html($fwcc_options['fwcc_dollar_to_naira_rate']); ?>" required />
            <input type="submit" value="Submit" class="button-primary"/>
        </form>
    </div>

    <?php
    }

    add_action( 'admin_init', 'fwcc_admin_init' );

    function fwcc_admin_init()
    {
        add_action('admin_post_save_fwcc_options', 'process_fwcc_options');
    }

    function process_fwcc_options()
    {
        if (!current_user_can('manage_options'))
        {
            wp_die( 'Not allowed' );
        }

        // Check that nonce field created in configuration form is present
        check_admin_referer( 'fwcc' );

        $fwcc_options = get_option('fwcc_options');

        if (isset($_POST["fwcc_dollar_to_naira_rate"])) 
        {
            $fwcc_options["fwcc_dollar_to_naira_rate"] = sanitize_text_field($_POST["fwcc_dollar_to_naira_rate"]); 
        }

        update_option('fwcc_options', $fwcc_options);

        // Redirect the page to the configuration form that was  processed
        wp_redirect(add_query_arg(
            array( 'page' => 'fwcc-settings-menu',
                    'message' => '1' ),
            admin_url( 'options-general.php')));

        exit;
    }

    add_action("woocommerce_cart_contents", "fwcc_get_cart");
    function fwcc_get_cart()
    {
        global $woocommerce;
        
        // Will get you cart object
        $cart = $woocommerce->cart;
        // Will get you cart object
        $cart_total = strip_tags($woocommerce->cart->get_total());
        //$cart_total = $woocommerce->cart->get_total();
        return $cart_total;//preg_replace( '#[^\d.]#', '',$cart_total );
    }

    function fwcc_get_amount($money)
    {
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousandSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

        return (float) str_replace(',', '.', $removedThousandSeparator);
    }

    //add the currency display on the checkout page
    add_action('woocommerce_review_order_before_payment', 'fcww_add_naira_currency');
    function fcww_add_naira_currency()
    {
        $cart_total = fwcc_get_cart();
       $fwcc_cart_total_string = substr($cart_total, 5);
       $fwcc_cart_total_number = fwcc_get_amount($fwcc_cart_total_string);

        //$fwcc_cart_total = 1;
        $fwcc_options = get_option('fwcc_options');
        $fwcc_dollar_to_naira_rate = $fwcc_options['fwcc_dollar_to_naira_rate'];
        $naira_value = $fwcc_cart_total_number*$fwcc_dollar_to_naira_rate;
        echo "<table><tr><th>Total (naira):</th><td><b>N" . number_format( $naira_value, 2, ".", "," ) ."<b></td></tr></table>";    
    }

        /*
    //update email sent from woocommerce
    add_action('woocommerce_email_after_order_table', 'fwcc_update_order_email_with_naira_value', 20, 4);
    function fwcc_update_order_email_with_naira_value($order, $sent_to_admin, $plain_text, $email)
    {
        $order_total = $order->get_total();
        $fwcc_options = get_option('fwcc_options');
        $fwcc_dollar_to_naira_rate = $fwcc_options['fwcc_dollar_to_naira_rate'];
        $naira_value = $order_total*$fwcc_dollar_to_naira_rate;
        echo "<table><tr><th>Total (naira):</th><td><b>N" . number_format($naira_value, 2, ".", "," ) ."<b></td></tr></table>";  
    } */

  /*  add_action('woocommerce_thankyou', 'fwcc_add_naira_value_to_thank_you_page', 10, 2);

    function fwcc_add_naira_value_to_thank_you_page($order_id)
    {
       $order = wc_get_order($order_id);
       $order_total = $order->total;
       $fwcc_options = get_option('fwcc_options');
       $fwcc_dollar_to_naira_rate = $fwcc_options['fwcc_dollar_to_naira_rate'];
       $naira_value = $order_total*$fwcc_dollar_to_naira_rate;

       echo "<table><tr><th>Total (naira):</th><td><b>N" . number_format($naira_value, 2, ".", "," ) ."<b></td></tr></table>";
    } **/

    //add extra row to orders table on the thank you page and email
    add_filter( 'woocommerce_get_order_item_totals', 'fwcc_add_recurring_row_email', 10, 2 );
 
    function fwcc_add_recurring_row_email( $total_rows, $myorder_obj )
    {
        $order_total = $myorder_obj->total;
        $fwcc_options = get_option('fwcc_options');
        $fwcc_dollar_to_naira_rate = $fwcc_options['fwcc_dollar_to_naira_rate'];
        $naira_value = $order_total*$fwcc_dollar_to_naira_rate;
        

        $total_rows['recurr_not'] = array(
        'label' => __( 'Total (naira):', 'woocommerce' ),
        'value'   => "N". number_format($naira_value, 2, ".", "," ) 
        );
        
        return $total_rows;
    }   

}

?>