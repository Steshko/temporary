
$(document).ready(function(){

    //Добавление товара в корзину
    $('.cart-button').click(function() {
        var productId = $(this).attr('value');
        //alert(productId);
        var array = $('#'+productId).serialize();
        //var array = $('#'+productId).serializeArray();
        //alert($('#'+productId).attr('name'))
        //alert(array);
        $.ajax({
            url: '/cart/additem', //url страницы (action_ajax_form.php)
            type:     'POST', //метод отправки
            dataType: 'html', //формат данных
            data: array,  // Сеарилизуем объект
            success: function(response, array) { //Данные отправлены успешно
                $('a.nav-link').attr('data-toggle', 'dropdown');
                $('#top-cart').html(response);
                quantity = $('#cart-quantity-down').text();
                $('#cart-quantity').text(quantity);
                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                  }
                toastr.success('Товар успешно добавлен в Корзину');
 
            },
            error: function(response) { // Данные не отправлены
                alert('Ошибка. Данные не отправлены.');
            }
         });
            
    });

    //Удаление позиции товара из корзины
    $('.cart-item-destroy').click(function() {
        var productId = $(this).attr('name').substr(4);
        
        var data = $('#form-cart').serializeArray();

        $.ajax({
            url: '/cart/delitem/'+productId,
            type:     'POST', 
            dataType: 'html', 
            data: data,  
            success: function(response) {
                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                  }
                toastr.warning('Товар удален из Корзины');
            },
            error: function(response) {
                alert('Ошибка. Данные не отправлены.');
            }
         });

        var count = $('#cart-count').attr('value');
        //alert(count);
        var itemSum = $('#itemSum-'+productId).attr('value');
        //alert(itemSum);
       count = count - itemSum;
       $('#cart-count').attr('value', count);
        $('#cart-count').text(count+' грн');
        $('#tr-'+productId).remove();//  .text('');
         //Перенумерация списка в корзине
        $('tr.cart-item').each(function(i){
            $(this).find('td.cart-item-number').text(i + 1);
        });
        //alert('Товар удален');
    });

    //Изменение количества товара в корзине
    $('td input').change(function() {
        var quantity = $(this).val();//  .attr('value');
        //alert(quantity);
        var tr = $(this).parent().parent();
        var id = tr.attr('id').substr(3);
        var price = tr.find('#itemPrice-'+id).attr('value');
        var count = quantity * price;
        var old_sum = tr.find('#itemSum-'+id).attr('value');
        tr.find('#itemSum-'+id).attr('value', count);
        tr.find('#itemSum-'+id).text(count);
        var old_count = $('#cart-count').attr('value');
        $('#cart-count').attr('value', +old_count - +old_sum + +tr.find('#itemSum-'+id).attr('value'));
        $('#cart-count').text( +old_count - +old_sum + +tr.find('#itemSum-'+id).attr('value')+' грн');
        
        //alert($('div #save-button').text().length);
        //Добавление кнопки Сохранить
        if($('div #save-button').text().length < 3) {
            //alert($('div #save-button').text());
            $('div #save-button').html(
                        '<button class="btn btn-block bg-gradient-danger btn-lg">Сохранить изменения в корзине!</button>'
                        );
            $('#save-order').addClass('disabled');//   .attr('class', 'disabled');
        };
        // alert(quantity);
    });//  .focusout(function () {

    //Сохранение изменений в корзине
    $('#save-button').click(function(){
        var data = $('#form-cart').serializeArray();
        
        $.ajax({
        url: '/cart/update', //url страницы (action_ajax_form.php)
        type:     'POST', //метод отправки
        dataType: 'html', //формат данных
        data: data,  // Сеарилизуем объект
        success: function(response) { //Данные отправлены успешно
            //alert(response);

        },
        error: function(response) { // Данные не отправлены
            alert('Ошибка. Данные не отправлены.');
        }
     });
        //alert(arr);


        //Возврат кнопок в изначальное положение
        $('div #save-button').text('');
        $('#save-order').removeClass('disabled');
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": true,
            "progressBar": false,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
          }
        toastr.success('Изменения в корзине успешно зафиксированы.');
    });
  
    //Переключение картинок
    $('#thumbs').children('.product-image-thumb').click(function(){
        
        var src = $(this).attr('value');
        $('#thumbs').children('.product-image-thumb').removeClass('active');
        $(this).addClass('active');
        $('#medium-img').attr('src', src);

    });

    $('.select-css').change(function() {
        var form = $(this).parent('form');
        var url = form.attr('action');
        //alert(form.serialize());
        //window.location = url + '?' + form.serialize();
        window.location = url + '?' + form.serialize();

    });

    //$('#medium-img').parent('div').height($('#medium-img').parent('div').width());

    $('.note-toolbar.card-header').removeAttr('style');



    //
    // Админка
    //

    //Активация/Деактивация товара из админки
    $('.product-active-button').click(function() {
        var button = $(this);
        var form = $(this).parent().parent();
        var productId = form.find('#product-id').attr('value');

        var data = form.serializeArray();

        $.ajax({
            url: '/admin/product/'+productId+'/activate',
            type:     'POST', 
            dataType: 'html', 
            data: data,  
            success: function(response) {
                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                  };
                
                if(button.hasClass('icon-green')) {
                    button.removeClass('fa-check-circle icon-green');
                    button.addClass('fa-ban icon-red');
                    toastr.warning('Товар деактивирован');
                }
                else {
                    button.removeClass('fa-ban icon-red');
                    button.addClass('fa-check-circle icon-green');
                    toastr.success('Товар Активирован');
                }
               
            },
            error: function(response) {
                alert('Ошибка. Данные не отправлены.');
            }
        });
    });

    //Удаление HTML-символов в описании товара
    $('.delete-html').click(function(e) {
        e.preventDefault();
        var dsc = $('#description.textarea');
        //alert(dsc.text());
        dsc.text(dsc.text().replace(/<[^>]+>/g,'').trim());
    });

    //Отмена Кнопка
    $('#cancel-button').click(function(e) {
        e.preventDefault();
        location.reload();
    });

    //Активация описания
    $('.button-active-dsc').click(function(e) {
        e.preventDefault();
        var productId = $(this).attr('id');
        var btn = $(this);
        var form = $('#form-active-dsc-'+productId);
        var data = form.serializeArray();

        $.ajax({
            url: '/admin/product/'+productId+'/dsca',
            type:     'POST', 
            dataType: 'html', 
            data: data,  
            success: function(response) {
                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                  };
                toastr.success('Описание активировано!');
                btn.remove();
               
            },
            error: function(response) {
                alert('Ошибка. Данные не отправлены.');
            }
        });

    });

    //ДЕ-Активация описания
    $('.button-deactive-dsc').click(function(e) {
        e.preventDefault();
        var productId = $(this).attr('id');
        var btn = $(this);
        var form = $('#dsc-form');
        var data = form.serializeArray();

        $.ajax({
            url: '/admin/product/'+productId+'/dsca',
            type:     'POST', 
            dataType: 'html', 
            data: data,  
            success: function(response) {
                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                  };
                toastr.success('Описание ДЕактивировано!');
                btn.remove();
                
               
            },
            error: function(response) {
                alert('Ошибка. Данные не отправлены.');
            }
        });

    });

    //Удаление изображения товара
    $('.button-del-img').click(function(e) {
        e.preventDefault(); 
        var id = $(this).attr('id');
        
        var form = $(this).parent('form') ;
        var data = form.serializeArray();
        //alert(data);

        $.ajax({
            url: '/admin/product/img/delete',
            type:     'POST', 
            dataType: 'html', 
            data: data,  
            success: function(response) {
                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                  };
                toastr.warning('Изображение удалено!');
                
                
               
            },
            error: function(response) {
                alert('Ошибка. Данные не отправлены.');
            }
        });


    });

    $('.article-destroy').click(function(e){
       var id = $(this).attr('id');
        var form = $(this).parent('form') ;
        var data = form.serializeArray();
        if(confirm('Действительно удалить статью?')){
            $.ajax({
                url: form.attr('action'),
                type:     'POST', 
                dataType: 'html', 
                data: data,  
                success: function(response) {
                    toastr.options = {
                        "closeButton": false,
                        "debug": false,
                        "newestOnTop": true,
                        "progressBar": false,
                        "positionClass": "toast-top-right",
                        "preventDuplicates": false,
                        "onclick": null,
                        "showDuration": "300",
                        "hideDuration": "1000",
                        "timeOut": "2000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                      }
                    toastr.warning('Статья удалена');
                    
                    $('#tr-'+id).remove();
                },
                error: function(response) {
                    alert('Ошибка. Данные не отправлены.');
                }
            });

        }
    });
    
});

