<?php
/*
Plugin Name: WP Reference Manager
Description: Manage and cite global references in IEEE format. Insert citations via TinyMCE and display reference lists with a shortcode.
Version: 0.2.0
Author: Andreas Galistel
*/

if (!defined('ABSPATH')) exit;

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'wprm_activate');
register_deactivation_hook(__FILE__, 'wprm_deactivate');

function wprm_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wprm_references';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title TEXT NOT NULL,
        authors TEXT,
        year VARCHAR(10),
        publication TEXT,
        url TEXT,
        attachment_id BIGINT UNSIGNED,
        extra LONGTEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function wprm_deactivate() {
    // No action needed for now (keep data)
}

// Admin menu for managing references
add_action('admin_menu', 'wprm_admin_menu');
function wprm_admin_menu() {
    add_menu_page('References', 'References', 'manage_options', 'wprm_references', 'wprm_references_page', 'dashicons-book-alt');
}

function wprm_references_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'wprm_references';
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $delete_id = isset($_GET['delete']) ? intval($_GET['delete']) : 0;

    // Handle add/edit
    if (isset($_POST['wprm_save_ref'])) {
        $data = array(
            'title' => sanitize_text_field($_POST['wprm_title']),
            'authors' => sanitize_text_field($_POST['wprm_authors']),
            'year' => sanitize_text_field($_POST['wprm_year']),
            'publication' => sanitize_text_field($_POST['wprm_publication']),
            'url' => esc_url_raw($_POST['wprm_url']),
        );
        if (!empty($_POST['wprm_id'])) {
            $wpdb->update($table, $data, array('id' => intval($_POST['wprm_id'])));
            echo '<div class="updated"><p>Reference updated.</p></div>';
        } else {
            $wpdb->insert($table, $data);
            echo '<div class="updated"><p>Reference added.</p></div>';
        }
    }

    // Handle delete
    if ($delete_id) {
        $wpdb->delete($table, array('id' => $delete_id));
        echo '<div class="updated"><p>Reference deleted.</p></div>';
    }

    // Build the admin page HTML without closing PHP tags
    if ($action === 'edit' && $edit_id) {
        $ref = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
        $title = $ref ? esc_attr($ref->title) : '';
        $authors = $ref ? esc_attr($ref->authors) : '';
        $year = $ref ? esc_attr($ref->year) : '';
        $publication = $ref ? esc_attr($ref->publication) : '';
        $url = $ref ? esc_attr($ref->url) : '';
        $id = $ref ? intval($ref->id) : 0;
        $heading = 'Edit Reference';
    } else {
        $title = $authors = $year = $publication = $url = '';
        $id = 0;
        $heading = 'Add Reference';
    }

    $html = '<div class="wrap"><h1>Reference Manager</h1>';
    $html .= '<h2>' . esc_html($heading) . '</h2>';
    $html .= '<form method="post">';
    $html .= '<input type="hidden" name="wprm_id" value="' . intval($id) . '">';
    $html .= '<table class="form-table">';
    $html .= '<tr><th>Authors</th><td><input type="text" name="wprm_authors" value="' . $authors . '" class="regular-text"></td></tr>';
    $html .= '<tr><th>Title</th><td><input type="text" name="wprm_title" value="' . $title . '" class="regular-text" required></td></tr>';
    $html .= "<tr><th>Publication</th><td><input type=\"text\" name=\"wprm_publication\" value=\"" . $publication . "\" class=\"regular-text\"> <input type=\"button\" class=\"button-secondary\" value=\"NA\" onclick=\"document.getElementsByName('wprm_publication')[0].value='Nerikes Allehanda';\"></td></tr>";
    $html .= '<tr><th>Year</th><td><input type="text" name="wprm_year" value="' . $year . '" style="width:150px;" class="regular-text"></td></tr>';
    $html .= '<tr><th>URL</th><td><input type="url" name="wprm_url" value="' . $url . '" class="regular-text"></td></tr>';
    $html .= '</table>';
    $html .= '<p><input type="submit" name="wprm_save_ref" class="button-primary" value="' . ($id ? 'Update Reference' : 'Add Reference') . '"></p>';
    $html .= '</form>';
    echo $html;

    // List references
    $refs = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    if ($refs) {
        echo '<h2>All References</h2>';
        echo '<table class="widefat"><thead><tr><th>ID</th><th>Authors</th><th>Title</th><th>Year</th><th>Publication</th><th>Actions</th></tr></thead><tbody>';
        foreach ($refs as $r) {
            echo '<tr>';
            echo '<td>' . intval($r->id) . '</td>';
            echo '<td>' . esc_html($r->authors) . '</td>';
            echo '<td>' . esc_html($r->title) . '</td>';
            echo '<td>' . esc_html($r->year) . '</td>';
            echo '<td>' . esc_html($r->publication) . '</td>';
            echo '<td>';
            echo '<a href="?page=wprm_references&action=edit&edit=' . intval($r->id) . '" class="button">Edit</a> ';
            echo '<a href="?page=wprm_references&delete=' . intval($r->id) . '" class="button" onclick="return confirm(\'Delete this reference?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No references found.</p>';
    }
    echo '</div>';
}

// TinyMCE button integration
add_action('admin_init', 'wprm_add_tinymce_button');
function wprm_add_tinymce_button() {
    if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
        add_filter('mce_external_plugins', 'wprm_tinymce_plugin');
        add_filter('mce_buttons', 'wprm_register_tinymce_button');
    }
}
function wprm_tinymce_plugin($plugin_array) {
    $plugin_array['wprm'] = plugins_url('tinymce/wprm-tinymce.js', __FILE__);
    return $plugin_array;
}
function wprm_register_tinymce_button($buttons) {
    array_push($buttons, 'wprm');
    return $buttons;
}


// Parse citation shortcodes in a post's content and return ordered unique ref IDs
function wprm_get_citation_order($post_id) {
    $content = get_post_field('post_content', $post_id);
    if (!$content) return array();
    $pattern = '/\[wprm_cite\s+[^\]]*id=["\']?(\d+)["\']?[^\]]*\]/i';
    if (!preg_match_all($pattern, $content, $matches)) return array();
    $ids = array_map('intval', $matches[1]);
    $unique = array();
    foreach ($ids as $id) {
        if (!in_array($id, $unique)) $unique[] = $id;
    }
    return $unique;
}

// Citation shortcode: [wprm_cite id="123"] — outputs sequential number based on appearance order
add_shortcode('wprm_cite', 'wprm_cite_shortcode');
function wprm_cite_shortcode($atts) {
    if (empty($atts['id'])) return '';
    $ref_id = intval($atts['id']);
    if (!$ref_id) return '';
    if (is_singular()) {
        global $post;
        if ($post && $post->ID) {
            $order = wprm_get_citation_order($post->ID);
            $pos = array_search($ref_id, $order, true);
            if ($pos !== false) {
                $num = $pos + 1;
                return '<span class="wprm-cite" data-ref-id="' . esc_attr($ref_id) . '">[' . esc_html($num) . ']</span>';
            }
        }
    }
    // Fallback: show id
    return '<span class="wprm-cite" data-ref-id="' . esc_attr($ref_id) . '">[' . esc_html($ref_id) . ']</span>';
}

// Shortcode to display references
add_shortcode('wprm_references', 'wprm_references_shortcode');
function wprm_references_shortcode($atts) {
    global $post, $wpdb;
    if (!$post || !$post->ID) return '';
    $ref_ids = wprm_get_citation_order($post->ID);
    if (!$ref_ids || !is_array($ref_ids) || empty($ref_ids)) {
        return '<div class="wprm-references">No references cited.</div>';
    }
    $table = $wpdb->prefix . 'wprm_references';
    $placeholders = implode(',', array_fill(0, count($ref_ids), '%d'));
    $refs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE id IN ($placeholders)",
        $ref_ids
    ));
    // IEEE formatting
    $out = '<ol class="wprm-references">';
    foreach ($ref_ids as $id) {
        $ref = null;
        foreach ($refs as $r) { if ($r->id == $id) { $ref = $r; break; } }
        if ($ref) {
            $authors = $ref->authors ? esc_html($ref->authors) : '';
            $title = $ref->title ? esc_html($ref->title) : '';
            $publication = $ref->publication ? esc_html($ref->publication) : '';
            $year = $ref->year ? esc_html($ref->year) : '';
            $url = $ref->url ? esc_url($ref->url) : '';
            $ieee = '';
            if ($authors) $ieee .= $authors . ', ';
            if ($title) $ieee .= '"' . $title . '", ';
            if ($publication) $ieee .= '<em>' . $publication . '</em>, ';
            if ($year) $ieee .= $year . '. ';
            if ($url) $ieee .= '<a href="' . $url . '">' . $url . '</a>';
            $ieee = rtrim($ieee, ', ');
            $out .= '<li>' . $ieee . '</li>';
        } else {
            $out .= '<li>Reference #' . esc_html($id) . ' not found.</li>';
        }
    }
    $out .= '</ol>';
    return $out;
}

// AJAX handler to add a new reference from the editor
add_action('wp_ajax_wprm_add_reference', 'wprm_ajax_add_reference');
function wprm_ajax_add_reference() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    global $wpdb;
    $table = $wpdb->prefix . 'wprm_references';
    $data = array(
        'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
        'authors' => isset($_POST['authors']) ? sanitize_text_field($_POST['authors']) : '',
        'year' => isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '',
        'publication' => isset($_POST['publication']) ? sanitize_text_field($_POST['publication']) : '',
        'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
    );
    $wpdb->insert($table, $data);
    $id = $wpdb->insert_id;
    if ($id) {
        wp_send_json_success(array('id' => $id));
    } else {
        wp_send_json_error('Insert failed');
    }
}
