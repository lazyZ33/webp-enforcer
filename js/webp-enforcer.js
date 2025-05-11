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