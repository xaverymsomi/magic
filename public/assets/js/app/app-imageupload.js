(function($) {
    $.fn.mxImageUploader = function(options) {
        var settings = $.extend({
            // default settings
            height: 128,
            emptyMessage: 'No file selected',
            errorMessage: 'Not a valid file image. Update your selection',
            fileTypes: ['image/jpeg', 'image/pjpeg', 'image/png', 'application/pdf']
        }, options);
        var input = this;
        // input.css('opacity', 0);
        input.after("<div class='preview'>" + settings.emptyMessage + "</div>");
        input.on('change', function() {
            var files = input.prop('files');
            var filename;
            if (files.length === 0) {
                input.next('div').html("<p>" + settings.emptyMessage + "</p>");
            } else {
                var file = files[0];
                filename = file.name;
                if (_validFileType(file)) {
                    input.next('div').html("<label class='well well-sm'><img src='" + window.URL.createObjectURL(file) + "' height='" + settings.height + "' style='display: block; margin-left: auto; margin-right: auto;' /><p>Name: " + filename + ", Size: " + _returnFileSize(file.size) + ".</p></label>");
                } else {
                    input.next('div').html("<p>" + filename + ": " + settings.errorMessage + "</p>");
                }
            }
        });
        
        _validFileType = function(file) {
            for (var i = 0; i < settings.fileTypes.length; i++) {
                if (file.type === settings.fileTypes[i]) {
                    return true;
                }
            }
            return false;
        };
        
        _returnFileSize = function(number) {
            if (number < 1024) {
                return number + 'bytes';
            } else if (number >= 1024 && number < 1048576) {
                return (number/1024).toFixed(1) + 'KB';
            } else if (number >= 1048576) {
                return (number/1048576).toFixed(1) + 'MB';
            }
        };
        
        return this;
    }; 
    $.fn.mxResultUploader = function(options) {
        var settings = $.extend({
            // default settings
            height: 128,
            emptyMessage: 'No file selected',
            errorMessage: 'Not a valid file Type. Update your selection',
            fileTypes: [ 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']
        }, options);
        var input = this;
        input.css('opacity', 0);
        input.after("<div class='preview'>" + settings.emptyMessage + "</div>");
        input.on('change', function() {
            var files = input.prop('files');
            var filename;
            if (files.length === 0) {
                input.next('div').html("<p>" + settings.emptyMessage + "</p>");
            } else {
                var file = files[0];
                filename = file.name;
                if (_validFileType(file)) {
                    input.next('div').html("<label class='well well-sm'><p>Name: " + filename + ", Size: " + _returnFileSize(file.size) + ".</p></label>");
                    // $scope.failed_upload = true;
                } else {
                    // $scope.failed_upload = false;
                    input.next('div').html("<p>" + filename + ": " + settings.errorMessage + "</p>");
                }
            }
        });
        
        _validFileType = function(file) {
            for (var i = 0; i < settings.fileTypes.length; i++) {
                if (file.type === settings.fileTypes[i]) {
                    return true;
                }
            }
            return false;
        };
        
        _returnFileSize = function(number) {
            if (number < 1024) {
                return number + 'bytes';
            } else if (number >= 1024 && number < 1048576) {
                return (number/1024).toFixed(1) + 'KB';
            } else if (number >= 1048576) {
                return (number/1048576).toFixed(1) + 'MB';
            }
        };
        
        return this;
    };
}(jQuery));