/* PAYMENT PROCESSING */

if (stripePublicApiKey) {
    var stripe = Stripe(stripePublicApiKey);
    var elements = stripe.elements();
    var card = elements.create('card');
}

$(document).delegate(".payment-method", "change", function(event) {
    setupPaymentFields();
});

$(document).delegate(".payment-submit", "click", function(e) {
    processPaymentForm(e);
});

// Show the card fields when a user (or onLoad) selects the Stripe payment method.
function setupPaymentFields() {
    if ($(".payment-method").val() == stripePaymentMethodId && stripePublicApiKey) {
        // Add an instance of the card Element into the `card-element` <div>.
        card.mount('#card-element');
        card.addEventListener('change', function(event) {
            if (event.error) {
                $("#paymentErrorMessage").html(event.error.message);
                $("#paymentError").show();
            } else {
                $("#paymentError").hide();
            }
        });
        $("#cardDetails").show();
    }
}

function processPaymentForm(e) {

    console.log("Processing payment form");
    $("#paymentError").hide();

    var paymentMethod = $(".payment-method");
    var paymentAmount = $(".payment-amount");

    if (paymentAmount.val() > 0 && paymentMethod.val() == "") {
        alert("Please choose a payment method, or set the amount paid to zero.");
        return false;
    }

    if (paymentAmount < minimumPaymentAmount && paymentMethod.val() == stripePaymentMethodId) {
        alert("Minimum card payment amount is "+minimumPaymentAmount.toFixed(2));
        paymentAmount.val(minimumPaymentAmount.toFixed(2));
        return false;
    }

    waitButton($('.payment-submit'));

    if ( paymentMethod.val() == stripePaymentMethodId
        && paymentMethod.val()
        && !$("#stripeCardId").val() ) {

        event.preventDefault();

        stripe.createPaymentMethod('card', card).then(function(result) {
            if (result.error) {
                // Inform the customer that there was an error.
                $("#paymentErrorMessage").html(result.error.message);
                $("#paymentError").show();
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
    } else {
        $("#paymentForm").submit();
    }
}

function handleServerResponse(response) {
    console.log(response);
    if (response.error) {
        $("#paymentErrorMessage").html(response.error);
        $("#paymentError").show();
        unWaitButton($('.payment-submit'));
    } else if (response.requires_action) {
        // Use Stripe.js to handle required card action
        handleAction(response);
    } else {
        // Add the charge ID into the form
        $("#chargeId").val(response.charge_id);
        $("#paymentForm").submit();
    }
}

function handleAction(response) {
    stripe.handleCardAction(
        response.payment_intent_client_secret
    ).then(function(result) {
            if (result.error) {
                console.log(result.error);
                $("#paymentErrorMessage").html(result.error.message);
                $("#paymentError").show();
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

$(document).ready(function() {
    setupPaymentFields();
});

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