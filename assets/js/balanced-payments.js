jQuery(document).ready(function(){
    balanced.init( balancedPayments.URI );

	card = new Skeuocard( jQuery( "#cc-form .credit-card-input" ) );

	jQuery('#cc-form #cc_submit').click(function (e) {
        e.preventDefault();

        var $this = jQuery(this);

        if( $this.hasClass('processing') ) {
            return;
        }

        $this.addClass('processing');

        jQuery('#cc-form #response').hide();

        var payload = {
            name: jQuery('#cc-form #cc_name').val(),

            number: jQuery('#cc-form #cc_number').val(),
            expiration_month: jQuery('#cc-form #cc_exp_month').val(),
            expiration_year: jQuery('#cc-form #cc_exp_year').val(),
            cvv: jQuery('#cc-form #cc_cvc').val(),

            address: {
                postal_code: jQuery('#cc-form #cc_post_code').val()
            }
        };

        // Tokenize credit card
        balanced.card.create(payload, function (response) {
            // Successful tokenization
            if(201 === response.status_code) {
                // Send to your backend
                jQuery.post( balancedPayments.ajax_url, {
                	action: 'bp_post_listener',
                    cc_uri: response.cards[0].href,
                    cc_amount: jQuery('#cc-form #cc_amount').val(),
                    cc_name: jQuery('#cc-form #cc_name').val(),
                    cc_post_code: jQuery('#cc-form #cc_post_code').val()
                }, function(data) {
                    console.log(data);
                    if ( data.success == true ) {
                        jQuery('#cc-form').html('<p class="success">' + balancedPayments.success + '</p>');
                    } else {
                        jQuery('#cc-form').html('<p class="alert">' + data.error + '</p>');
                    }
                });
            } else {
                jQuery('#cc-form').html('<p class="alert">' + balancedPayments.cardCreateError + '</p>');
            }

            $this.removeClass('processing');
        });
    });
	// 
});