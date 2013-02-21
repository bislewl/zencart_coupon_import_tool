<?php
/**
 *
 * @package Coupon Import Tool
 * @copyright Copyright 2007 Numinix Technology http://www.numinix.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: coupon_import_tool.php 3 2011-10-17 22:40:40Z numinix $
 */

  require('includes/application_top.php');
  
  $debug = false; // change to true or false to enable/disable debug output
  
  switch($_GET['action']) {
    case 'import':
      ini_set('auto_detect_line_endings', true);
      $error = false;
      $_SESSION['errors'] = array();
      if (!strpos($_FILES['upload']['name'], '.csv')) {
        $error = true;
        $_SESSION['errors'][] = 'Invalid file type, please upload a Comma Separated Value (.csv) file.';
      }
      if (!(float)$_POST['discount_amount'] > 0) {
        $_SESSION['errors'][] = 'Only a positive floating point decimal number is accepted.';
        $error = true;
      }
      if (!isset($_POST['discount_type'])) {
        $error = true;
        $_SESSION['errors'][] = 'You must select a discount type.';
      }
      if ($debug) {
        echo '<pre>';
        print_r($_FILES);
        echo '</pre>';
        
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';
        
        $coupons = fopen($_FILES['upload']['tmp_name'], r);
        while (($line = fgetcsv($coupons)) !== FALSE) {
          echo '<pre>';
          print_r($line);
          echo '</pre>';
        }
        die();
      }
      
      if ($error) {
        zen_redirect(zen_href_link(FILENAME_COUPON_IMPORT_TOOL));
      } else {
        // get file content
        //$coupons = file_get_contents($_FILES['upload']['tmp_name']);
        switch($_POST['type']) {
          case 'groupon':
            $coupons = fopen($_FILES['upload']['tmp_name'], r);
            $products = $_POST['products'];
            $categories = $_POST['categories'];
            $counter = 0;
            while (($line = fgetcsv($coupons)) !== FALSE) {
              $counter++;
              if ($counter == 1) {
                // column headers
                foreach ($line as $key => $value) {
                  switch($value) {
                    case 'Groupon No.':
                      $coupon_code_key = $key;
                      break;
                    case 'Offer Option':
                      $coupon_name_key = $key;
                      break;
                  } 
                }
              } else {
                $sql_data_array = array('coupon_code' => zen_db_prepare_input($line[$coupon_code_key]),
                                        'coupon_amount' => zen_db_prepare_input($_POST['discount_amount']),
                                        'coupon_type' => zen_db_prepare_input($_POST['discount_type']),
                                        'uses_per_coupon' => 1,
                                        'uses_per_user' => 1,
                                        'coupon_minimum_order' => 0.00,
                                        'restrict_to_products' => '',
                                        'restrict_to_categories' => '',
                                        'coupon_start_date' => 'now()',
                                        'coupon_expire_date' => date('Y-m-d', time() + 31536000) . ' ' . '00:00:00', // expires in 365 days
                                        'date_created' => 'now()',
                                        'date_modified' => 'now()',
                                        'coupon_zone_restriction' => 0);
                zen_db_perform(TABLE_COUPONS, $sql_data_array);
                $insert_id = $db->Insert_ID();
                $sql_data_desc_array = array('coupon_id' => $insert_id,
                                             'coupon_name' => zen_db_prepare_input($line[$coupon_name_key]),
                                             'coupon_description' => zen_db_prepare_input($line[$coupon_name_key]),
                                             'language_id' => $_SESSION['languages_id']
                                             );
                zen_db_perform(TABLE_COUPONS_DESCRIPTION, $sql_data_desc_array);
                foreach($products as $products_id) {
                  $sql_data_restrict_array = array('coupon_id' => $insert_id,
                                                   'product_id' => $products_id,
                                                   'coupon_restrict' => 'N');
                  zen_db_perform(TABLE_COUPON_RESTRICT, $sql_data_restrict_array);
                }
                foreach ($categories as $categories_id) {
                  $sql_data_restrict_array = array('coupon_id' => $insert_id,
                                                   'category_id' => $categories_id,
                                                   'coupon_restrict' => 'N');
                  zen_db_perform(TABLE_COUPON_RESTRICT, $sql_data_restrict_array);                  
                }
              }
            }
            break;
          case 'generic':
            $coupons = file_get_contents($_FILES['upload']['tmp_name']);        
            if ($coupons == '') {
              $error = true;
              $_SESSION['errors'][] = 'Uploaded file is empty.'; 
              zen_redirect(zen_href_link(FILENAME_COUPON_IMPORT_TOOL));
            } else {
              $coupons = explode(',', $coupons);
              $products = $_POST['products'];
              $categories = $_POST['categories'];
              foreach($coupons as $coupon_code) {
                $sql_data_array = array('coupon_code' => zen_db_prepare_input($coupon_code),
                                        'coupon_amount' => zen_db_prepare_input($_POST['discount_amount']),
                                        'coupon_type' => zen_db_prepare_input($_POST['discount_type']),
                                        'uses_per_coupon' => 1,
                                        'uses_per_user' => 1,
                                        'coupon_minimum_order' => 0.00,
                                        'restrict_to_products' => '',
                                        'restrict_to_categories' => '',
                                        'coupon_start_date' => 'now()',
                                        'coupon_expire_date' => date('Y-m-d', time() + 31536000) . ' ' . '00:00:00', // expires in 365 days
                                        'date_created' => 'now()',
                                        'date_modified' => 'now()',
                                        'coupon_zone_restriction' => 0);
                zen_db_perform(TABLE_COUPONS, $sql_data_array);
                $insert_id = $db->Insert_ID();
                $sql_data_desc_array = array('coupon_id' => $insert_id,
                                             'coupon_name' => zen_db_prepare_input($coupon_code),
                                             'coupon_description' => zen_db_prepare_input($coupon_code),
                                             'language_id' => $_SESSION['languages_id']
                                             );
                zen_db_perform(TABLE_COUPONS_DESCRIPTION, $sql_data_desc_array);
                foreach($products as $products_id) {
                  $sql_data_restrict_array = array('coupon_id' => $insert_id,
                                                   'product_id' => $products_id,
                                                   'coupon_restrict' => 'N');
                  zen_db_perform(TABLE_COUPON_RESTRICT, $sql_data_restrict_array);
                }
                foreach ($categories as $categories_id) {
                  $sql_data_restrict_array = array('coupon_id' => $insert_id,
                                                   'category_id' => $categories_id,
                                                   'coupon_restrict' => 'N');
                  zen_db_perform(TABLE_COUPON_RESTRICT, $sql_data_restrict_array);                  
                }                
              } 
            }
            break;
        }
        zen_redirect(zen_href_link(FILENAME_COUPON_IMPORT_TOOL, 'action=success'));
      }
      
      break;
    default:
      // build list of all products
      $products = $db->Execute("SELECT p.products_id, pd.products_name FROM " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd 
                                WHERE p.products_id = pd.products_id
                                AND p.products_status = 1
                                AND p.product_is_call = 0
                                AND pd.language_id = " . (int)$_SESSION['languages_id'] . "  
                                ORDER BY pd.products_name ASC;");
      // build list of categories
      $categories_query = "SELECT c.categories_id, cd.categories_name FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd
                           WHERE c.categories_id = cd.categories_id
                           AND c.categories_status = 1
                           AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
                           ORDER BY cd.categories_name ASC;";
      
      if (defined('TAGS_MASTER_CATEGORY_ID') && TAGS_MASTER_CATEGORY_ID > 0) {
        $categories_query = str_replace('WHERE', 'WHERE c.categories_id <> ' . (int)TAGS_MASTER_CATEGORY_ID . ' AND c.parent_id <> ' . (int)TAGS_MASTER_CATEGORY_ID . ' AND ', $categories_query);
      }
      $categories = $db->Execute($categories_query);
  }
?> 
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
<style type="text/css">
h1 {margin: 0 0 20px;}
#couponImportTool {width: 650px; border: 1px solid #CCC; border-radius: 20px; padding: 30px; margin: 40px auto;}
#couponImportTool label {font-weight: bold; display: inline-block; width: 195px; vertical-align: top;}
label#discountType {width: auto !important; margin-left: 20px;}
label.discountTypes {width: auto !important; font-weight: normal !important;}
#couponImportTool input[type="text"], #couponImportTool input[type="file"], #couponImportTool select {margin-bottom: 20px; border: 1px solid #CCC;}
#couponImportTool input[type="radio"] {vertical-align: top; margin: 0 5px 0 10px;}
#couponImportTool select {width: 451px; border: 1px solid #CCC;} 
#submitForm {width: 100; height: 32px; background:url(images/buttons/submitButton.png) no-repeat 0px 0px; margin: auto; padding: 0; border: none; cursor: pointer; display: block}
#submitForm:hover {background-position: 0px -41px;}
.errors {padding: 5px; border: 1px solid red; color: red; margin-bottom: 20px;}
.success {padding: 5px; border: 1px solid green; color: green; margin-bottom: 20px;}
</style>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body_bof //-->
<div id="couponImportTool">
  <h1><?php echo HEADING_TITLE; ?></h1>
  <?php 
    if (isset($_SESSION['errors']) && sizeof($_SESSION['errors']) > 0) {
      echo '<div class="errors">';
      foreach ($_SESSION['errors'] as $error) {
        echo '<p>' . $error . '</p>';
      }
      echo '</div>';
    } elseif ($_GET['action'] == 'success') {
      echo '<div class="success">Coupon codes imported successfully, please check the Coupon Admin to verify.</div>';
    }  
  ?> 
  <?php echo zen_draw_form('import_coupons', FILENAME_COUPON_IMPORT_TOOL, 'action=import', 'post', 'enctype="multipart/form-data"'); ?>

    <label for="type"><?php echo LABEL_TYPE; ?></label>
    <select name="type">
      <option value="groupon">Groupon</option>
      <option value="generic">Generic</option>
    </select>
    <br />
  
    <label for="upload"><?php echo LABEL_UPLOAD; ?></label>
    <?php echo zen_draw_file_field('upload'); ?>
    <br />
    
    <?php
      if ($products->RecordCount() > 0) {
        echo '<label for="products">' . LABEL_PRODUCTS . '</label>' . "\n";
        $products_size = ($products->RecordCount() > 10 ? 10 : $products->RecordCount()); 
        echo '<select name="products[]" multiple="multiple" size="' . $products_size . '">' . "\n";
        while (!$products->EOF) {
          echo '<option value="' . $products->fields['products_id'] . '">' . $products->fields['products_name'] . '</option>' . "\n";
          $products->MoveNext();
        }
        echo '</select><br />' . "\n";
      }
    ?>
    <?php
      if ($categories->RecordCount() > 0) {
        echo '<label for="categories">' . LABEL_CATEGORIES . '</label>' . "\n";
        $categories_size = ($categories->RecordCount() > 10 ? 10 : $categories->RecordCount()); 
        /*
        echo '<select name="categories[]" multiple="multiple" size="' . $categories_size . '">' . "\n";
        while (!$categories->EOF) {
          echo '<option value="' . $categories->fields['categories_id'] . '">' . $categories->fields['categories_name'] . '</option>' . "\n";
          $categories->MoveNext();
        }*/
        echo zen_draw_pull_down_menu('categories[]', zen_get_category_tree('', '', '0', '', '', true, false, array(TAGS_MASTER_CATEGORY_ID)), '', 'multiple="multiple" size="' . $categories_size . '"');
        echo '</select><br />' . "\n";        
      }
    ?>
    
    <label for="discount_amount"><?php echo LABEL_DISCOUNT_AMOUNT; ?></label>
    <?php echo zen_draw_input_field('discount_amount', '', 'size=10 id="discount_amount"'); ?>
    <label id="discountType"><?php echo LABEL_DISCOUNT_TYPE; ?></label>
    <?php echo zen_draw_radio_field('discount_type', 'P'); ?><label for="discount_type" class="discountTypes"><?php echo LABEL_DISCOUNT_PERCENTAGE; ?></label>
    <?php echo zen_draw_radio_field('discount_type', 'F'); ?><label for="discount_type" class="discountTypes"><?php echo LABEL_DISCOUNT_DOLLAR_AMOUNT; ?></label>
    <br />
    <button type="submit" name="submitForm" id="submitForm"></button>  
  </form>
</div>
<?php if (isset($_SESSION['errors'])) unset($_SESSION['errors']); // clear the errors ?>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>