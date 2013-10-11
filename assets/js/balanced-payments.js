jQuery(document).ready(function(){
	balanced.init( balancedPayments.URI );
	card = new Skeuocard( jQuery( "#cc-form .credit-card-input" ) );
	jQuery('#cc-form #cc_submit').click(function (e) {
        e.preventDefault();

        jQuery('#cc-form #response').hide();

        var payload = {
            card_number: jQuery('#cc-form #cc_number').val(),
            expiration_month: jQuery('#cc-form #cc_exp_month').val(),
            expiration_year: jQuery('#cc-form #cc_exp_year').val(),
            security_code: jQuery('#cc-form #cc_cvc').val()
        };

        // Tokenize credit card
        balanced.card.create(payload, function (response) {

            // Successful tokenization
            if(response.status === 201) {
                // Send to your backend
                jQuery.post( balancedPayments.ajax_url, {
                	action: 'bp_post_listener',
                    cc_uri: response.data.uri,
                    cc_amount: jQuery('#cc-form #cc_amount').val(),
                    cc_name: jQuery('#cc-form #cc_name').val(),
                    cc_post_code: jQuery('#cc-form #cc_post_code').val()
                }, function(r) {
                	result = JSON.parse( r );
                    if ( result.success == true ) {
                        jQuery('#cc-form').html('<p class="success">' + balancedPayments.success + '</p>');
                    } else {
                        jQuery('#cc-form').html('<p class="alert">' + result.error + '</p>');
                    }
                });
            } else {
                jQuery('#cc-form').html('<p class="alert">' + balancedPayments.cardCreateError + '</p>');
            }

        });
    });
	// 
});