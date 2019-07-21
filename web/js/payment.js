/* PAYMENT PROCESSING */

if (stripePublicApiKey) {

    var stripe = Stripe(stripePublicApiKey);
    var elements = stripe.elements();

    // Create an instance of the card Element.
    var card = elements.create('card');

    //var handler = StripeCheckout.configure({
    //    key: stripePublicApiKey,
    //    locale: 'auto',
    //    token: function(token) {
    //        $(".stripe-token").val(token.id);
    //        $(".payment-form").submit();
    //        waitButton($('.payment-submit'));
    //    }
    //});
}

$(document).ready(function() {

    var paymentMethod = $("#paymentMethod");
    var paymentAmount = $("#payment-amount");

    $(document).on('change', "#paymentMethod", function(e) {
        setupPaymentFields();
    });

    // When user clicks payment button, route through Stripe if appropriate
    $(document).on('click', ".payment-submit", function(e) {

        if (paymentAmount.val() > 0 && paymentMethod.val() == "") {
            alert("Please choose a payment method, or set the amount paid to zero.");
            return false;
        }

        if (paymentAmount < minimumPaymentAmount && paymentMethod.val() == stripePaymentMethodId) {
            alert("Minimum card payment amount is "+minimumPaymentAmount.toFixed(2));
            paymentAmount.val(minimumPaymentAmount.toFixed(2));
            return false;
        }

        waitButton($(this));

        if ( paymentMethod.val() == stripePaymentMethodId
            && paymentMethod.val()
            && !$("#stripeCardId").val() ) {

            event.preventDefault();

            stripe.createPaymentMethod('card', card).then(function(result) {
                if (result.error) {
                    // Inform the customer that there was an error.
                    $("#paymentError").html(result.error.message);
                    unWaitButton($('.payment-submit'));
                } else {
                    // Send paymentMethod.id to server to create a payment intent
                    fetch('/stripe/payment-intent', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            stripePaymentMethodId: result.paymentMethod.id,
                            amount: paymentAmount.val() * 100 + (stripePaymentFee * 100)
                        })
                    }).then(function(result) {
                        result.json().then(function(json) {
                            console.log(json);
                            handleServerResponse(json);
                        })
                    });

                }
            });
            // Open Checkout with further options:
            //handler.open({
            //    name: orgName,
            //    zipCode: false,
            //    currency: currencyIsoCode,
            //    allowRememberMe: false,
            //    email:  $(".contact-email").val(),
            //    amount: paymentAmount.val() * 100 + (stripePaymentFee * 100)
            //});
            //e.preventDefault();


        } else {
            $("#paymentForm").submit();
            waitButton($(this));
        }

    });

    function handleServerResponse(response) {
        console.log(response);
        if (response.error) {
            $("#paymentError").html(response.error).show();
            unWaitButton($('.payment-submit'));
        } else if (response.requires_action) {
            // Use Stripe.js to handle required card action
            handleAction(response);
        } else {
            // Add the charge ID into the form
            $("#chargeId").val(response.charge_id);
            $("#paymentForm").submit();
            waitButton($('.payment-submit'));
        }
    }

    function handleAction(response) {
        stripe.handleCardAction(
            response.payment_intent_client_secret
        ).then(function(result) {
                if (result.error) {
                    console.log(result.error);
                    $("#paymentError").html(result.error.message).show();
                    unWaitButton($('.payment-submit'));
                } else {
                    // The card action has been handled
                    // The PaymentIntent can be confirmed again on the server
                    fetch('/stripe/payment-intent', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            paymentIntentId: result.paymentIntent.id
                        })
                    }).then(function(confirmResult) {
                        return confirmResult.json();
                    }).then(handleServerResponse);
                }
            });
    }

    function setupPaymentFields() {
        if (paymentMethod.val() == stripePaymentMethodId) {

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element');

            card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    $("#paymentError").html(event.error.message).show();
                } else {
                    $("#paymentError").hide()
                }
            });

            $("#cardDetails").show();
        }
    }

    setupPaymentFields();

});

// Close Checkout on page navigation:
//$(window).on('popstate', function() {
//    if (handler != undefined) {
//        handler.close();
//    }
//});

/**
 * Used on the payment processing modals to choose from an existing card
 * Also see similar in member_site.js
 * @param cardId
 */
function setCard(cardId) {
    var selectedCard = $("#"+cardId);
    $(".creditCard").removeClass('active');
    $(".card-select").html("Use this card");
    selectedCard.addClass('active');
    selectedCard.find('.card-select').html("This card will be used.");
    $("#stripeCardId").val(cardId);
    $("#paymentMethod").val(stripePaymentMethodId);
    setUpSelectMenus();
}

function showTakePaymentFields() {
    $("#payment-fields").show();
    $(".no-payment-needed").hide();
    setUpSelectMenus();
}

/* END PAYMENT PROCESSING */