<?php
/*
Plugin Name: CSS File Selector
Plugin URI: http://www.chrgiga.com/css-file-selector
Description: Add CSS files and/or CSS custom rules to any single page or post
Version: 1.0.4
Author: Christian Gil
Author URI: http://www.chrgiga.com
License: GPLv3 or later
Copyright 2014 Christian Gil
*/


/* Adds a box to the main column on the Post and Page edit screens */
function gil_css_file_selector_add_custom_box()
{
    $screens = array( 'post', 'page' );

    foreach ($screens as $screen) {
      add_meta_box(
        'css-file-selector',
        __('Select CSS files and/or write your custom CSS rules', 'cssfileselector'),
        'gil_css_file_selector_inner_custom_box',
        $screen
      );
    }
}
/* Get and list the CSS files */
function gil_get_css_file($cssfiles)
{
  // Recursive function for read directories and subdirectories
  function gil_read_css_directories($directory, &$files)
  {
  if (is_dir($directory)) {
    if ($open_dir = opendir($directory)) {
      while (($file = readdir($open_dir)) !== false) {
        if ($file != '.' AND $file != '..') {
          // Verify if is directory or file
          if (is_dir( $directory.'/'.$file)) {
            gil_read_css_directories($directory.'/'.$file , $files);
          } else {
            // Ready File
            $explodefile = explode('.', $file);

            if (is_file($directory.'/'.$file) && end($explodefile) == 'css') {
              $files[dirname($directory.'/'.$file)][] = $directory.'/'.$file;
            }
          }
        }
      }
      closedir($open_dir);
      }
    }
  }

  // Get path of actual template
  $path_template = get_template_directory();
  $files = array();
  gil_read_css_directories($path_template, $files);
  $select = '';

  foreach($cssfiles as $cssfile) {
    $option_css = '';
    $option_group = '';
    $select .= '<select name="gil_css_file_selector_file[]">';

    foreach ($files as $css_dir => $css_list) {
      $name_dir = str_replace($path_template, '', $css_dir);
      $name_dir = $name_dir == '' ? '/' : $name_dir;

      if ($name_dir != '' && $option_group != $name_dir) {
        $option_css .= '<optgroup label="'.$name_dir.'">';
        $option_group = $name_dir;
      } else {
        $option_css .= '</optgroup>';
      }
      foreach ($css_list as $css) {
        $selected = $css == $cssfile ? 'selected="selected"' : '';
        $option_css .= '<option value="'.$css.'" '.$selected.'>'.basename($css).'</option>';
      }
    }
    $option_css = $option_css == '' ? '<option value="">CSS files not found</option>' : '<option value="">Without CSS file</option>'.$option_css.'</optgroup>';
    $select .= $option_css.'</select>';
  }

  return $select;
}
/* Prints the box content */
function gil_css_file_selector_inner_custom_box($post)
{
  // Use nonce for verification
  wp_nonce_field(plugin_basename( __FILE__ ), 'gil_css_file_selector_chrgiga');

  // The actual fields for data entry
  // Use get_post_meta to retrieve an existing value from the database and use the value for the form
  $cssfiles = get_post_meta($post->ID, 'gil_css_file_selector_file', true);
  $cssrules = get_post_meta($post->ID, 'gil_css_file_selector_rules', true);
  echo '<div class="row"><label>Select CSS files</label><br />'.gil_get_css_file(explode(',', $cssfiles)).' <button type="button" class="add-select-css button button-primary button-large">Add other file</button><hr /></div>';
  echo '<div class="row"><label for="css-file-selector-rules">Write your custom CSS rules</label><br /><textarea id="css-file-selector-rules" name="gil_css_file_selector_rules">'.esc_attr($cssrules).'</textarea></div>';
}

function gil_css_file_selector_admin_scripts()
{
  wp_enqueue_style('cssfileselector.css', plugins_url('inc/css/cssfileselector.css', __FILE__));
  wp_enqueue_script('cssfileselector.js', plugins_url('inc/js/cssfileselector.js', __FILE__), array(), '1.0.0', true);
}

/* When the post is saved, saves our custom data */
function gil_css_file_selector_save_postdata($post_id)
{
  // First we need to check if the current user is authorised to do this action.
  if ('page' == $_POST['post_type']) {
    if (!current_user_can( 'edit_page', $post_id)) {
      return;
    }
  } else {
    if (!current_user_can('edit_post', $post_id)) {
      return;
    }
  }

  // Secondly we need to check if the user intended to change this value.
  if (!isset($_POST['gil_css_file_selector_chrgiga']) || !wp_verify_nonce($_POST['gil_css_file_selector_chrgiga'], plugin_basename( __FILE__ ))) {
    return;
  }

  // Thirdly we can save the value to the database
  $post_ID = $_POST['post_ID'];
  $cssfiles = implode(',', $_POST['gil_css_file_selector_file']);
  $cssrules = $_POST['gil_css_file_selector_rules'];

  add_post_meta($post_ID, 'gil_css_file_selector_file', $cssfiles, true) or
  update_post_meta($post_ID, 'gil_css_file_selector_file', $cssfiles);
  add_post_meta($post_ID, 'gil_css_file_selector_rules', $cssrules, true) or
  update_post_meta($post_ID, 'gil_css_file_selector_rules', $cssrules);
}

function gil_css_file_selector_insert_css_file()
{
  global $post;

  if (is_single() || is_page()) {
    $cssfiles = get_post_meta($post->ID, 'gil_css_file_selector_file');

    if (count($cssfiles) && $cssfiles[0] != '') {
      foreach (explode(',', $cssfiles[0]) as $cssfile) {
        $css_uri = str_replace(get_template_directory(), get_template_directory_uri(), $cssfile);
        wp_enqueue_style(str_replace('.min', '', basename($cssfile, '.css')), $css_uri);
      }
    }
  }
}

function gil_css_file_selector_insert_css_rules()
{
  global $post;

  if (is_single() || is_page()) {
    $cssrules = get_post_meta($post->ID, 'gil_css_file_selector_rules');
    if (count($cssrules) && $cssrules[0] != '') { ?>
      <!-- CSS File Selector (custom rules) -->
      <style type="text/css">
      <?php echo $cssrules[0]; ?>
      </style>
    <?php
    }
  }
}

function gil_delete_post_meta()
{
  global $post;

  if ('trash' == get_post_status($post_id)) {
    delete_post_meta($post->ID, 'gil_css_file_selector_file');
    delete_post_meta($post->ID, 'gil_css_file_selector_rules');
  }
}

/* Define the custom box */
add_action('add_meta_boxes', 'gil_css_file_selector_add_custom_box');
/* backwards compatible (before WP 3.0) */
add_action('admin_init', 'gil_css_file_selector_add_custom_box', 1);
/* Save the selected css files and the custom css rules */
add_action('save_post', 'gil_css_file_selector_save_postdata');
/* Enqueue styles ans function in editor page/post */
add_action('admin_enqueue_scripts', 'gil_css_file_selector_admin_scripts');
/* Put the css files selected */
add_action('wp_enqueue_scripts', 'gil_css_file_selector_insert_css_file');
/* Add the custom css rules */
add_action('wp_head', 'gil_css_file_selector_insert_css_rules');
/* Delete options when post is deleted */
add_action('delete_post', 'gil_delete_post_meta');
/* Delete all options when the plugin is uninstalling */
register_uninstall_hook(plugin_dir_path( __FILE__ ).'uninstall.php', 'uninstall');
?>