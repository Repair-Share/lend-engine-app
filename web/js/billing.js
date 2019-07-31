/**
 * Similar to payment.js but isolated for processing subscriptions to Lend Engine
 */

$(document).delegate(".subscription-submit", "click", function(e) {
    e.preventDefault();
    processPaymentForm(e);
});

var billingStripe = Stripe(billingPublicApiKey);
var billingElements = billingStripe.elements();
var billingCard = billingElements.create('card');

billingCard.mount('#card-element');
billingCard.addEventListener('change', function(event) {
    if (event.error) {
        handleErrors(event);
    } else {
        $("#paymentError").hide();
    }
});

$(document).ready(function() {
    if ($("#billing_paymentAmount").val() == 0) {
        $("#nothing-to-pay").fadeIn();
        $("#cardDetails").hide();
        $("#billInfo").hide();
    } else {
        $("#cardDetails").show();
    }
});

function processPaymentForm(e) {
    console.log("Processing subscription form");
    var errorMessage = '';
    $("#paymentError").hide();
    var paymentAmount = $(".payment-amount");
    if ( paymentAmount.val() > 0 ) {
        billingStripe.createToken(billingCard).then(function(result) {
            if (result.error) {
                handleErrors(result);
            } else {
                submitFormWithToken(result.token.id);
            }
        });
    } else {
        waitButton($('.subscription-submit'));
        $("#paymentForm").submit();
    }
}

function submitFormWithToken(tokenId) {
    $("#billing_stripeTokenId").val(tokenId);
    waitButton($('.subscription-submit'));
    fetch('/admin/billing_payment_handler', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            planCode: $("#billing_planCode").val(),
            stripeTokenId: tokenId
        })
    }).then(function(response) {
        response.json().then(function(json) {
            console.log(json);
            handleServerResponse(json);
        })
    });
}

function handleServerResponse(response) {
    console.log(response);
    if (response.error) {
        handleErrors(response);
        unWaitButton($('.subscription-submit'));
    } else if (response.requires_action) {
        // Use Stripe.js to handle required card action
        handleAction(response);
    } else {
        // Add the subscription ID into the form
        $("#billing_subscriptionId").val(response.subscription_id);
        $("#paymentForm").submit();
    }
}

function handleAction(response) {
    billingStripe.handleCardPayment(
        response.payment_intent_client_secret
    ).then(function(result) {
        if (result.error) {
            handleErrors(result);
            unWaitButton($('.subscription-submit'));
        } else {
            // success, submit the form
            $("#billing_subscriptionId").val(response.subscription_id);
            $("#paymentForm").submit();
        }
    });
}

function handleErrors(result) {
    console.log(result);
    var errorMessage = '';
    if (result.error.message != undefined) {
        errorMessage += result.error.message;
    }
    if (result.error != undefined) {
        errorMessage += result.error;
    }
    if (result.errors != undefined) {
        if (result.errors.length > 0) {
            $.each(result.errors, function(k,v) {
                errorMessage += '<br>'+v;
            });
        }
    }
    $("#paymentErrorMessage").html(errorMessage);
    $("#paymentError").show();
}