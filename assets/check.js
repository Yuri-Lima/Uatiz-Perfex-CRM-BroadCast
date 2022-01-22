$('.check_label').on('click', function(event) {
	$(this).parents('.chek_div').find('.check').trigger('click');
});