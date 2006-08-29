<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2006 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  define('OSC_ADMIN_FILE_MANAGER_ROOT_PATH', realpath('../'));

  if (isset($_SESSION['fm_directory'])) {
    $current_path = $_SESSION['fm_directory'];
  } else {
    $current_path = OSC_ADMIN_FILE_MANAGER_ROOT_PATH;
    $_SESSION['fm_directory'] = $current_path;
  }

  if (isset($_GET['directory'])) {
    $current_path .= '/' . $_GET['directory'];
    $_SESSION['fm_directory'] = $current_path;
  } elseif (isset($_GET['goto'])) {
    $current_path = OSC_ADMIN_FILE_MANAGER_ROOT_PATH . '/' . urldecode($_GET['goto']);
    $_SESSION['fm_directory'] = $current_path;
  }

  $current_path = realpath($current_path);

  if ( (substr($current_path, 0, strlen(OSC_ADMIN_FILE_MANAGER_ROOT_PATH)) != OSC_ADMIN_FILE_MANAGER_ROOT_PATH) || (is_dir($current_path) === false) ) {
    $current_path = OSC_ADMIN_FILE_MANAGER_ROOT_PATH;
    $_SESSION['fm_directory'] = $current_path;
  }

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (!empty($action)) {
    switch ($action) {
      case 'reset':
        unset($_SESSION['fm_directory']);

        osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER));
        break;
      case 'deleteconfirm':
        if (isset($_GET['entry']) && !empty($_GET['entry'])) {
          $target = $current_path . '/' . basename($_GET['entry']);

          if (is_writeable($target)) {
            osc_remove($target);
          } else {
            if (is_file($target)) {
              $osC_MessageStack->add_session('header', sprintf(ERROR_FILE_NOT_WRITEABLE, $target), 'error');
            } else {
              $osC_MessageStack->add_session('header', sprintf(ERROR_DIRECTORY_NOT_WRITEABLE, $target), 'error');
            }
          }
        }

        osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER));
        break;
      case 'new_directory':
        if (isset($_POST['directory_name']) && !empty($_POST['directory_name'])) {
          if (is_writeable($current_path)) {
            $new_directory = $current_path . '/' . basename($_POST['directory_name']);

            if (file_exists($new_directory) === false) {
              if (mkdir($new_directory, 0777)) {
                osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER, 'entry=' . urlencode(basename($_POST['directory_name']))));
              }
            } else {
              $osC_MessageStack->add('header', sprintf(ERROR_DIRECTORY_EXISTS, $new_directory), 'error');
            }
          } else {
            $osC_MessageStack->add_session('header', sprintf(ERROR_DIRECTORY_NOT_WRITEABLE, $current_path), 'error');
          }
        }

        osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER));
        break;
      case 'save':
        if ( (isset($_GET['entry']) && !empty($_GET['entry'])) || (isset($_POST['filename']) && !empty($_POST['filename'])) ) {
          if (isset($_GET['entry']) && !empty($_GET['entry'])) {
            $filename = basename($_GET['entry']);
          } elseif (isset($_POST['filename']) && !empty($_POST['filename'])) {
            $filename = basename($_POST['filename']);
          }

          if ($fp = fopen($current_path . '/' . $filename, 'w+')) {
            fputs($fp, $_POST['contents']);
            fclose($fp);
          }

          osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER, 'entry=' . $filename));
        }

        osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER));
        break;
      case 'processuploads':
        if (is_writeable($current_path)) {
          for ($i=0; $i<10; $i++) {
            $file = new upload('file_' . $i, $current_path);

            if ($file->exists()) {
              $file->parse();
              $file->save();
            }
          }
        } else {
          $osC_MessageStack->add_session('header', sprintf(ERROR_DIRECTORY_NOT_WRITEABLE, $current_path), 'error');
        }

        osc_redirect(osc_href_link_admin(FILENAME_FILE_MANAGER));
        break;
      case 'download':
        if (isset($_GET['entry']) && !empty($_GET['entry'])) {
          $target = $current_path . '/' . basename($_GET['entry']);

          if (file_exists($target)) {
            header('Content-type: application/x-octet-stream');
            header('Content-disposition: attachment; filename=' . urldecode(basename($_GET['entry'])));

            readfile($target);
            exit;
          }
        }
        break;
    }
  }

  $goto_array = array(array('id' => '', 'text' => '--TOP--'));

  if ($current_path != OSC_ADMIN_FILE_MANAGER_ROOT_PATH) {
    $path_array = explode('/', substr($current_path, strlen(OSC_ADMIN_FILE_MANAGER_ROOT_PATH)+1));

    foreach ($path_array as $value) {
      if (sizeof($goto_array) < 2) {
        $goto_array[] = array('id' => $value, 'text' => $value);
      } else {
        $parent = end($goto_array);
        $goto_array[] = array('id' => $parent['id'] . '/' . $value, 'text' => $parent['id'] . '/' . $value);
      }
    }
  }

  switch ($action) {
    case 'fmEdit': $page_contents = 'file_manager_edit.php'; break;
    default: $page_contents = 'file_manager.php';
  }

  require('templates/default.php');

  require('includes/application_bottom.php');
?>
