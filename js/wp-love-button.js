jQuery(document).ready(function() {
    jQuery(".wplb-wrapper a").click(function() {

        var love_button = jQuery(this);
        var post_id = love_button.data("post_id");

        jQuery.ajax({
            type: "post",
            url: ajax_var.url,
            data: "action=wplb_love_post&_wpnonce=" + ajax_var.nonce + "&wplb_love_post=&post_id=" + post_id,
            success: function(response) {
                jQuery(love_button).parent().toggleClass("wplb-loved")
                jQuery(love_button).siblings("span").html(response.count);
                jQuery(love_button).html(response.message);
            },
        });

        return false;
    })
});