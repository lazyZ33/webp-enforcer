<?php
/**
 * Plugin Name: WebP Enforcer
 * Description: Enforces WebP image format by displaying a modal before uploading images to the media library, while allowing SVG uploads.
 * Version: 1.0.0
 * Author: Web Pro Geeks
 * Author URI: https://webprogeeks.com/
 * Text Domain: webp-enforcer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WebP_Enforcer {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('wp_handle_upload_prefilter', array($this, 'enforce_webp_format'));
        add_filter('upload_mimes', array($this, 'enable_svg_uploads'));
        add_filter('wp_check_filetype_and_ext', array($this, 'fix_svg_mime_type'), 10, 5);
        add_action('admin_head', array($this, 'fix_svg_thumb_display'));
    }
    
    public function enqueue_scripts($hook) {
        if ('media-new.php' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_register_script(
            'webp-enforcer-js',
            plugin_dir_url(__FILE__) . 'js/webp-enforcer.js',
            array('jquery', 'wp-util'),
            '1.0.0',
            true
        );
        
        wp_register_style(
            'webp-enforcer-css',
            plugin_dir_url(__FILE__) . 'css/webp-enforcer.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script('webp-enforcer-js');
        wp_enqueue_style('webp-enforcer-css');
        
        add_action('admin_footer', array($this, 'modal_template'));
    }
    
    public function modal_template() {
        ?>
        <script type="text/html" id="tmpl-webp-enforcer-modal">
            <div class="webp-enforcer-modal-backdrop"></div>
            <div class="webp-enforcer-modal">
                <div class="webp-enforcer-modal-content">
                    <div class="webp-enforcer-modal-header">
                        <h3><?php _e('Image Format Confirmation', 'webp-enforcer'); ?></h3>
                        <button type="button" class="webp-enforcer-modal-close">
                            <span class="screen-reader-text"><?php _e('Close', 'webp-enforcer'); ?></span>
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="webp-enforcer-modal-body">
                        <p><?php _e('Has this image been compressed and converted to WebP format?', 'webp-enforcer'); ?></p>
                    </div>
                    <div class="webp-enforcer-modal-footer">
                        <button type="button" class="button webp-enforcer-cancel"><?php _e('No', 'webp-enforcer'); ?></button>
                        <button type="button" class="button button-primary webp-enforcer-confirm"><?php _e('Yes, proceed with upload', 'webp-enforcer'); ?></button>
                    </div>
                </div>
            </div>
        </script>
        <?php
    }
    
    public function enforce_webp_format($file) {
        $file_type = wp_check_filetype($file['name']);
        $extension = strtolower($file_type['ext']);
        $allowed_formats = array('webp', 'svg', 'svgz');
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif', 'heic', 'heif', 'avif');
        if (in_array($extension, $image_extensions)) {
            $file['error'] = sprintf(
                __('Error: Please convert your %s image to WebP format before uploading.', 'webp-enforcer'),
                strtoupper($extension)
            );
        }
        
        return $file;
    }
    
    public function enable_svg_uploads($mimes) {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }
    
    public function fix_svg_mime_type($data, $file, $filename, $mimes, $real_mime = null) {
        // WP 5.1+
        if (version_compare($GLOBALS['wp_version'], '5.1.0', '>=')) {
            $wp_5_1_plus = func_num_args() > 4;
        } else {
            $wp_5_1_plus = false;
        }
        
        if (strpos($filename, '.svg') !== false) {
            $data['type'] = 'image/svg+xml';
            $data['ext'] = 'svg';
            $data['proper_filename'] = $filename;
        } elseif (strpos($filename, '.svgz') !== false) {
            $data['type'] = 'image/svg+xml';
            $data['ext'] = 'svgz';
            $data['proper_filename'] = $filename;
        }
        
        return $data;
    }
    
    public function fix_svg_thumb_display() {
        ?>
        <style type="text/css">
            td.media-icon img[src$=".svg"],
            img[src$=".svg"].attachment-post-thumbnail {
                width: 100% !important;
                height: auto !important;
            }
            
            .media-frame-content .attachment-preview .thumbnail img[src$=".svg"] {
                width: 100% !important;
                height: auto !important;
                padding: 10%;
            }
        </style>
        <?php
    }
}

function webp_enforcer_activate() {
    if (!file_exists(plugin_dir_path(__FILE__) . 'js')) {
        mkdir(plugin_dir_path(__FILE__) . 'js', 0755);
    }
    
    if (!file_exists(plugin_dir_path(__FILE__) . 'css')) {
        mkdir(plugin_dir_path(__FILE__) . 'css', 0755);
    }
    
    $js_content = <<<EOT
(function($) {
    'use strict';
    var uploadForm, fileInput, originalSubmit;
    
    function initElements() {
        uploadForm = $('form.media-upload-form, form#file-form, form#image-form');
        fileInput = uploadForm.find('input[type="file"]');
        
        if (uploadForm.length) {
            originalSubmit = uploadForm[0].submit;
            uploadForm[0].submit = customSubmit;
        }
        
        addEventListeners();
    }
    
    function addEventListeners() {
        uploadForm.on('submit', handleFormSubmit);
        
        $('.media-frame-content').on('click', '.button.button-primary', handleUpload);
        
        fileInput.on('change', handleFileSelection);
        
        $(document).on('click', '.webp-enforcer-modal-close, .webp-enforcer-cancel', hideModal);
        $(document).on('click', '.webp-enforcer-confirm', confirmUpload);
    }
    
    function handleFormSubmit(e) {
        if (fileInput.val()) {
            e.preventDefault();
            checkFileFormat();
        }
    }
    
    function handleFileSelection() {
        checkFileFormat();
    }
    
    function handleUpload(e) {
        if (fileInput.val()) {
            e.preventDefault();
            checkFileFormat();
        }
    }
    
    function customSubmit() {
        checkFileFormat();
        return false;
    }
    
    function checkFileFormat() {
        var fileName = fileInput.val().toLowerCase();
        
        if (fileName) {
            var extension = fileName.split('.').pop();
            var allowedFormats = ['webp', 'svg', 'svgz'];
            var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif', 'heic', 'heif', 'avif'];
           
            if (imageExtensions.indexOf(extension) !== -1) {
                showModal();
            } else {
                proceedWithUpload();
            }
        }
    }
    
    function showModal() {
        var template = wp.template('webp-enforcer-modal');
        $('body').append(template());
        $('body').addClass('webp-enforcer-modal-open');
    }
    
    function hideModal() {
        $('.webp-enforcer-modal, .webp-enforcer-modal-backdrop').remove();
        $('body').removeClass('webp-enforcer-modal-open');
    }
    
    function confirmUpload() {
        hideModal();
        proceedWithUpload();
    }
    
    function proceedWithUpload() {
        if (originalSubmit) {
            originalSubmit.call(uploadForm[0]);
        } else {
            uploadForm.off('submit', handleFormSubmit);
            uploadForm.submit();
        }
    }
    
    $(document).ready(function() {
        initElements();
    });
    
})(jQuery);
EOT;
    
    // Create the CSS file
    $css_content = <<<EOT
.webp-enforcer-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 159900;
}

.webp-enforcer-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    max-width: 90%;
    background-color: #fff;
    border-radius: 4px;
    z-index: 159901;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.7);
}

.webp-enforcer-modal-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    position: relative;
}

.webp-enforcer-modal-header h3 {
    margin: 0;
    font-size: 18px;
    line-height: 1.5;
}

.webp-enforcer-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
}

.webp-enforcer-modal-close:hover {
    color: #000;
}

.webp-enforcer-modal-body {
    padding: 15px;
    font-size: 14px;
}

.webp-enforcer-modal-footer {
    padding: 15px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.webp-enforcer-modal-footer .button {
    margin-left: 10px;
}

body.webp-enforcer-modal-open {
    overflow: hidden;
}
EOT;
    
    file_put_contents(plugin_dir_path(__FILE__) . 'js/webp-enforcer.js', $js_content);
    file_put_contents(plugin_dir_path(__FILE__) . 'css/webp-enforcer.css', $css_content);
}

function webp_enforcer_deactivate() {
    // No specific cleanup needed
}

register_activation_hook(__FILE__, 'webp_enforcer_activate');
register_deactivation_hook(__FILE__, 'webp_enforcer_deactivate');

$webp_enforcer = new WebP_Enforcer();