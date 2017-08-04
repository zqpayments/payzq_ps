/**
 * 2017 - PayZQ
 */

$(document).ready(function() {

	//rename switch fiels value labels
	$('label[for=_PS_PAYZQ_mode_on]').html(payzq_test_mode);
	$('label[for=_PS_PAYZQ_mode_off]').html(live);

	// refund
	$('#section-shape-3 .form-wrapper').first().prepend('<p class="mt-15 ">'+conf_refund_1+'.</p>');

	// API config
	$('#section-shape-4 .form-wrapper').first().prepend('<p class="mt-15 ">'+conf_mode_description3+' <a href="http://payzq.net">payzq.net</a>.</p>');
	$('#section-shape-4 .form-wrapper').first().prepend('<p class="mt-15 ">'+conf_mode_description2+'</p>');
	$('#section-shape-4 .form-wrapper').first().prepend('<p class="mt-15 ">'+conf_mode_description1+'</p>');

	// multistore
	var old = $('.bootstrap.panel');
	$('#content').after(old);
	old.css('margin-left', '12%');

	var value = 0;
	value = $('input[name=_PS_PAYZQ_mode]:checked', '#configuration_form').val();

	if (value == 1)
	{
		$("#secret_key").parent().parent().hide();
		$("#test_secret_key").parent().parent().show();
	}
	else
	{
		$("#secret_key").parent().parent().show();
		$("#test_secret_key").parent().parent().hide();
	}

	$('#configuration_form input').on('change', function() {
		value = $('input[name=_PS_PAYZQ_mode]:checked', '#configuration_form').val();

		if (value == 1)
		{
			$("#secret_key").parent().parent().hide();
			$("#test_secret_key").parent().parent().show();
		}
		else
		{
			$("#secret_key").parent().parent().show();
			$("#test_secret_key").parent().parent().hide();
		}
	});

	/* Alert Confirmation Refund */
	$("#configuration_form_submit_btn_2").click(function(){
		if (confirm('Are you sure that you want to refund this order?'))
	  		return true;
		return false;
	});

	/* Refund Option */
	var value = 0;
	value = $('input[name=_PS_PAYZQ_refund_mode]:checked').val();

	if (value == 1)
		$("#refund_amount").parent().parent().hide();
	else
		$("#refund_amount").parent().parent().show();

	$('input[name=_PS_PAYZQ_refund_mode]').on('change', function() {
		value = $('input[name=_PS_PAYZQ_refund_mode]:checked').val();

		if (value == 1)
			$("#refund_amount").parent().parent().hide();
		else
			$("#refund_amount").parent().parent().show();
	});

	// $('.process-icon-refresh').click(function(){
  //       $.ajax({
  //           url: validate + 'refresh.php',
  //           data: {'token_payzq' : token_payzq,
  //           'id_employee' : id_employee}
  //       }).done(function(response) {
  //           $('.table').html(response);
  //       });
  //   });

});
