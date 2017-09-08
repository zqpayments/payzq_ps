/**
 * 2017 - PayZQ
 */

function lookupCardType(number)
{
    if (number.match(new RegExp('^4')) !== null) {
        return 'Visa';
    }
    if (number.match(new RegExp('^(34|37)')) !== null) {
        return 'Amex';
    }
    if (number.match(new RegExp('^5[1-5]')) !== null) {
        return 'MasterCard';
    }
    if (number.match(new RegExp('^6011')) !== null) {
        return 'Discover';
    }
    if (number.match(new RegExp('^(?:2131|1800|35[0-9]{3})[0-9]{3,}')) !== null) {
        return 'Jcb';
    }
    if (number.match(new RegExp('^3(?:0[0-5]|[68][0-9])[0-9]{4,}')) !== null) {
        return 'Diners';
    }
}
function cc_format(value) {
    var v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    var matches = v.match(/\d{4,16}/g);
    var match = matches && matches[0] || '';
    var parts = [];
    for (i=0, len=match.length; i<len; i+=4) {
        parts.push(match.substring(i, i+4));
    }
    if (parts.length) {
        return parts.join(' ');
    } else {
        return value;
    }
}

function c(val){
  console.log(val);
}

var payZQ_isInit = false;

function initPayZQOfficial() {
    payZQ_isInit = true;

    $('.payzq-payment').parent().prev().find('input[name=payment-option]').addClass('payzq-official');

    //Put our input DOM element into a jQuery Object
    var jqDate = document.getElementById('card_expiry');

    //Bind keyup/keydown to the input
    $(jqDate).bind('keyup','keydown', function(e){
        var value_exp = $(jqDate).val();
        var v = value_exp.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        var matches = v.match(/\d{2,4}/g);

        //To accomdate for backspacing, we detect which key was pressed - if backspace, do nothing:
        if(e.which !== 8) {
            var numChars = value_exp.length;
            if(numChars === 2){
                var thisVal = value_exp;
                thisVal += '/';
                $(jqDate).val(thisVal);
            }
            if (numChars === 5)
                return false;
        }
    });

    $('#payzq-payment-form input').keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });

    $('#payment-confirmation button').click(function (event) {
        if ($('input[name=payment-option]:checked').hasClass('payzq-official')) {
            $('#payzq-payment-form').submit();
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
    });

    $('#payzq-payment-form').submit(function (event) {
        event.preventDefault();
        var $form = $(this);

        /* Disable the submit button to prevent repeated clicks */
        $('#payment-confirmation button[type=submit]').attr('disabled', 'disabled');
        $('.payzq-payment-errors').hide();
        $('#payzq-payment-form').hide();
        $('#payzq-ajax-loader').show();

        exp_month = $('.payzq-card-expiry').val();
        exp_month_calc = exp_month.substring(0, 2);
        exp_year = $('.payzq-card-expiry').val();
        exp_year_calc = exp_year.substring(3);

        card_number = $('.payzq-card-number').val();

        data = {
          cardholder: $('.payzq-name').val(),
          number: card_number,
          cvv: $('.payzq-card-cvc').val(),
          type: lookupCardType(card_number),
          expiry: exp_month_calc + '' + exp_year_calc
        }

        $.ajax({
          url: baseDir + '/modules/payzq_ps/ajax.php',
          type: 'post',
          dataType: 'json',
          data: data
        }).done(function(data){
          if (data.code == '1') {
            // Charge ok : redirect the customer to order confirmation page
            location.replace(data.url);
          } else {
            //  Charge ko
            $('#payzq-ajax-loader').hide();
            $('#payzq-payment-form').show();
            $('.payzq-payment-errors').show();
            $('.payzq-payment-errors').text(data.msg).fadeIn(1000);
            $('#payment-confirmation button[type=submit]').removeAttr('disabled');
          }
        }).fail(function(e, v){
          console.log('perola');
          console.log(e);
          console.log(v);
          $('#payzq-ajax-loader').hide();
          $('#payzq-payment-form').show();
          $('.payzq-payment-errors').show();
          $('.payzq-payment-errors').text('4- An error occured during the request. Please contact us').fadeIn(1000);
          $('#payment-confirmation button[type=submit]').removeAttr('disabled');
        });

        return false;
    });

    /* Determine the Credit Card Type */
    $('.payzq-card-number').keyup(function () {
        if ($(this).val().length >= 2) {
            payzq_card_type = lookupCardType($('.payzq-card-number').val());
            $('.cc-icon').removeClass('enable');
            $('.cc-icon').removeClass('disable');
            $('.cc-icon').each(function() {
                if ($(this).attr('rel') == payzq_card_type) {
                    $(this).addClass('enable');
                } else {
                    $(this).addClass('disable');
                }
            });
        } else {
            $('.cc-icon').removeClass('enable');
            $('.cc-icon:not(.disable)').addClass('disable');
        }
    });

     /* Catch callback errors */
    if ($('.payzq-payment-errors').text()) {
        $('.payzq-payment-errors').fadeIn(1000);
    }

    $('#payzq-payment-form input').keypress(function () {
        $('.payzq-payment-errors').fadeOut(500);
    });
};

$(document).ready(function() {
    if (!payZQ_isInit) {
        initPayZQOfficial();
    }
});
