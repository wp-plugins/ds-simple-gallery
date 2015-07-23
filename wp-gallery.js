jQuery(function($){
    var effect =  $('#effects').val();
    $('#wrsimplegallery a').colorbox({
        maxWidth: '100%',
        maxHeight: '90%',
        transition: effect
    });
});
