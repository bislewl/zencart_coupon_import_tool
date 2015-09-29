<?php
  $zc150 = (PROJECT_VERSION_MAJOR > 1 || (PROJECT_VERSION_MAJOR == 1 && substr(PROJECT_VERSION_MINOR, 0, 3) >= 5));  
  if ($zc150) { // continue Zen Cart 1.5.0
    // add configuration menu
    if (!zen_page_key_exists('toolsCouponImportTool')) {
      zen_register_admin_page('toolsCouponImportTool',
                              'BOX_COUPON_IMPORT_TOOL', 
                              'FILENAME_COUPON_IMPORT_TOOL',
                              '', 
                              'tools', 
                              'Y',
                              '99');
        
      $messageStack->add('Enabled Coupon Import Tool on Tools menu.', 'success');
    }     
  }
