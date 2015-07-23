jQuery(function($) {
    if (typeof plupload !== 'undefined' && typeof WPSGwpUploaderInit !== 'undefined') {
        var uploader = new plupload.Uploader(WPSGwpUploaderInit);
        uploader.init();
        uploader.bind('FilesAdded', function(up) {
            up.start();
            $('#wrsimplegallyer_spinner').show();
        });
        uploader.bind('FileUploaded', function(up, file, res) {
            var hidespinner = (uploader.total.queued < 1) ? function() {
                $('#wrsimplegallyer_spinner').hide();
            } : function() {

            };
            if (typeof res.response !== 'undefined') {
                wrsimplegallery.get_thumbnail(res.response, hidespinner);
            }
        });
    } else {
        $('#wrsg-plupload-browse-button').hide();
    }

    var file_frame,
            wrsimplegallery = {
        admin_thumb_ul: '',
        init: function() {
            this.admin_thumb_ul = $('#wrsimplegallery_thumbs');
            this.admin_thumb_ul.sortable({
                placeholder: 'wrsimplegallery_placeholder'
            });
            this.admin_thumb_ul.on('click', '.wrsimplegallery_remove', function() {
                if (confirm('Are you sure you want to delete this?')) {
                    $(this).parent().fadeOut(1000, function() {
                        $(this).remove();
                    });
                }
                return false;
            });
            
            $('#wrsimplegallery_upload_button').on('click', function() {
                event.preventDefault();
                if (file_frame) {
                    file_frame.open();
                    return;
                }

                file_frame = wp.media.frames.file_frame = wp.media({
                    title: jQuery(this).data('uploader_title'),
                    button: {
                        text: jQuery(this).data('uploader_button_text'),
                    },
                    multiple: true
                });

                file_frame.on('select', function() {
                    var images = file_frame.state().get('selection').toJSON(),
                            length = images.length;
                    for (var i = 0; i < length; i++) {
                        wrsimplegallery.get_thumbnail(images[i]['id']);
                    }
                });
                file_frame.open();
            });

            $('#wrsimplegallery_add_attachments_button').on('click', function() {
                var included = [];
                $('#wrsimplegallery_thumbs input[type=hidden]').each(function(i, e) {
                    included.push($(this).val());
                });
                wrsimplegallery.get_all_thumbnails(POST_ID, included);
            });

            $('#wrsimplegallery_delete_all_button').on('click', function() {
                if (confirm('Are you sure you want to delete all the images in the gallery?')) {
                    wrsimplegallery.admin_thumb_ul.empty();
                }
                return false;
            });
        },
        get_thumbnail: function(id, cb) {
            cb = cb || function() {
            };
            var data = {
                action: 'wrsimplegallery_get_thumbnail',
                imageid: id
            };
            jQuery.post(ajaxurl, data, function(response) {
                wrsimplegallery.admin_thumb_ul.append(response);
                cb();
            });
        },
        get_all_thumbnails: function(post_id, included) {
            var data = {
                action: 'wrsimplegallery_get_all_thumbnail',
                post_id: post_id,
                included: included
            };
            $('#wrsimplegallyer_spinner').show();
            $.post(ajaxurl, data, function(response) {
                wrsimplegallery.admin_thumb_ul.append(response);
                $('#wrsimplegallyer_spinner').hide();
            });
        }
    };
    wrsimplegallery.init();
});