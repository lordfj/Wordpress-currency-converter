<?php
 // Check that code was called from WordPress with uninstallation constant declared
 if (!defined( 'WP_UNINSTALL_PLUGIN' ))
 {
    exit;
 }
 // Check if options exist and delete them if present
 if (get_option('fwcc_dollar_to_naira_rate') != false)
 {
    delete_option( 'fwcc_dollar_to_naira_rate');
 }


 if (get_option( 'fwcc_version' ) != false)
 {
    delete_option( 'fwcc_version');
 }


?>