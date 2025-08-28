jQuery(function($){
  // Product edit
  $(document).on('submit', '#la-product-edit-form', function(e){
    e.preventDefault();
    var $form = $(this);
    var pid = $form.data('product');
    var data = {
      product_id: pid,
      access_days: $form.find('input[name="access_days"]').val(),
      is_assessoria: $form.find('input[name="is_assessoria"]').is(':checked') ? 1 : 0,
    };
    $.ajax({
      url: LA_ADMIN.rest_url + 'product-update',
      method: 'POST',
      beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', LA_ADMIN.rest_nonce); },
      data: data,
      success: function(res){ $('#la-product-edit-result').text('Salvo com sucesso.'); },
      error: function(xhr){ $('#la-product-edit-result').text('Erro ao salvar: ' + xhr.responseText); }
    });
  });

  // manage access form
  $(document).on('click', '#la-manage-access-form .la-btn', function(e){
    e.preventDefault();
    var action = $(this).data('action');
    var $form = $('#la-manage-access-form');
    var data = {
      user_id: $form.find('input[name="user_id"]').val(),
      product_id: $form.find('input[name="product_id"]').val(),
      days: $form.find('input[name="days"]').val(),
      action: action
    };
    $.ajax({
      url: LA_ADMIN.rest_url + 'access-action',
      method: 'POST',
      beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', LA_ADMIN.rest_nonce); },
      data: data,
      success: function(res){ $('#la-manage-access-result').text('Operação: ' + res.status); },
      error: function(xhr){ $('#la-manage-access-result').text('Erro: ' + xhr.responseText); }
    });
  });
});
