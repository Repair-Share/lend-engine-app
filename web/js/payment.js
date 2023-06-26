/* PAYMENT PROCESSING */

var paymentInitiated = false;

if (stripePublicApiKey && typeof Stripe !== 'undefined') {
    var stripe = Stripe(stripePublicApiKey);
    var elements = stripe.elements();
    var card = elements.create('card');
}

$(document).delegate(".payment-method", "change", function() {
    setupPaymentFields();
});

$(document).delegate(".payment-submit", "click", function(e) {
    return processPaymentForm(e);
});

$(document).delegate(".pay-membership-at-pickup", "click", function(e) {
    $('#membership_subscribe_payMembershipAtPickup').val(1);
    return $("#paymentForm").submit();
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
    } else {
        $("#cardDetails").hide();
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

    if (stripePaymentFee > 0 && paymentMethod.val() == stripePaymentMethodId && paymentAmount.val() > 0) {
        if (!window.confirm("A card payment fee of "+currencySymbol+stripePaymentFee+" will be added.")) {
            event.preventDefault();
            return false;
        }
    }

    if (paymentInitiated == true) {
        // Deal with double clicks
        return false;
    }

    if ( paymentMethod.val() == stripePaymentMethodId
        && paymentMethod.val()
        && paymentAmount.val() > 0 ) {
        event.preventDefault();

        if ( $("#stripeCardId").val() ) {
            // We've been given an existing card, use it for the paymentIntent
            createPaymentIntent($("#stripeCardId").val(), paymentAmount);
        } else {
            stripe.createPaymentMethod('card', card).then(function(result) {
                if (result.error) {
                    // Inform the customer that there was an error.
                    $("#paymentErrorMessage").html(result.error.message);
                    $("#paymentError").show();
                } else {
                    createPaymentIntent(result.paymentMethod.id, paymentAmount);
                }
            });
        }
    } else {
        waitButton($('.payment-submit'));
        $("#paymentForm").submit();
    }
}

/**
 * First, instruct the server to create a payment intent on Stripe
 * If payment processing has begun, use the already created payment intent
 * @param paymentMethodId
 * @param paymentAmount
 * @returns {boolean}
 */
function createPaymentIntent(paymentMethodId, paymentAmount) {
    var paymentType = $("#paymentType").val();
    var depositsAmount = $("#depositTotal").val();
    waitButton($('.payment-submit'));

    // Send paymentMethod.id to server to create a payment intent
    fetch('/stripe/payment-intent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            stripePaymentMethodId: paymentMethodId,
            contactId: $("#contactId").val(),
            saveCard: $("#saveCard").prop("checked"),
            deposits: depositsAmount,
            paymentType: paymentType,
            amount: paymentAmount.val() * 100 + (stripePaymentFee * 100)
        })
    }).then(function(response) {
        response.json().then(function(json) {
            handleServerResponse(json);
        })
    });
}

/**
 * The payment intent was created and completed, or not
 * If it requires action (eg 3D secure) then get the client to do the required work
 * If the intent does not require action, submit the payment form
 * @param response
 */
function handleServerResponse(response) {
    console.log("handleServerResponse:");
    console.log(response);
    if (response.error) {
        paymentInitiated = false;
        $("#paymentErrorMessage").html(response.error);
        $("#paymentError").show();
        unWaitButton($('.payment-submit'));
    } else if (response.requires_action) {
        // Use Stripe.js to handle required card action
        paymentInitiated = true;
        handleAction(response);
    } else {
        // Add the charge and payment ID into the form and submit it
        // The form POST controller will update the payment with loan/membership/event info
        paymentInitiated = true;
        $("#chargeId").val(response.charge_id);
        $("#paymentId").val(response.payment_id);
        $("#paymentForm").submit();
    }
}

/**
 * Instruct the Stripe client-side code to perform any further actions (eg 3D secure)
 * Return any errors to the user and open the form up for re-submission
 * Or check to see if the payment intent is now completed
 * @param response
 */
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
                    paymentIntentId: result.paymentIntent.id,
                    amount: $(".payment-amount").val(),
                    contactId: $("#contactId").val()
                })
            }).then(function(confirmResult) {
                return confirmResult.json();
            }).then(handleServerResponse);
        }
    });
}

$(document).ready(function() {
    setupPaymentFields();

    if (pendingPaymentType) {
        // User has not completed the previous payment
        $("#pendingPaymentWarning").show();
    }
});

/**
 * Used on the payment processing modals to choose from an existing card
 * @param cardId
 */
function setCard(cardId) {
    var selectedCard = $("#"+cardId);
    $(".creditCard").removeClass('active');
    $(".card-select").html("Use this card");
    selectedCard.addClass('active');
    selectedCard.find('.card-select').html("This card will be used.");
    selectedCard.find('.card-delete').remove();
    $("#stripeCardId").val(cardId);
    $(".payment-method").val(stripePaymentMethodId);
    setUpSelectMenus();
}

/* END PAYMENT PROCESSING */