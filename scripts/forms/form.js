function taoAjaxForm(name, formOptions) {
    var that = {};

    that.form = $('#tao-form-' + name);
    that.options = formOptions;

    function onOk(data) {
        if (typeof data.return_url == 'string' && data.return_url != 'std') {
            location.href = data.return_url;
        } else {
            var ok = $('<div>').addClass('ok-message').html(data.ok_message);
            $('div.tao-form-' + name).empty().append(ok);
        }
    }

    function onError(data) {
        var e = $('div.tao-form-' + name + ' ul.tao-form-errors').empty();
        $.each(data.errors, function (field, message) {
            if (message.length > 1) {
                var li = $('<li>').attr('data-field', field).addClass('error-field-' + field).html(message);
                e.append(li);
            }
            $('div.tao-form-' + name + ' .tao-form-field-' + field).addClass('tao-error-field');
        });
        e.show();
    }

    function beforeSubmit(formData, jqForm, options) {
        $('.tao-form-field').removeClass('tao-error-field');
        var funcName = formOptions.before_submit;
        if (typeof funcName == 'string') {
            var func = window[funcName];
            if (typeof func == 'function') {
                var r = func(formData, jqForm, options);
                if (r === false) {
                    return false;
                }
            }
        }
        var container = $('div.tao-form-' + name);
        var shadow = $('<div>').addClass('shadow');
        container.append(shadow);
    }

    function onAjaxReturn(data) {
        var e = $('div.tao-form-' + name + ' div.shadow').remove();
        if (data.result == 'ok') {
            var std = true;
            var funcName = data.on_ok;
            if (typeof funcName == 'string') {
                var func = window[funcName];
                if (typeof func == 'function') {
                    var r = func(data, that.form, that.options);
                    if (r === false) {
                        std = false;
                    }
                }
            }
            if (std) {
                onOk(data);
            }
        } else {
            var std = true;
            var funcName = data.on_error;
            if (typeof funcName == 'string') {
                var func = window[funcName];
                if (typeof func == 'function') {
                    var r = func(data, that.form, that.options);
                    if (r === false) {
                        std = false;
                    }
                }
            }
            if (std) {
                onError(data);
            }
        }
        return false;
    }

    that.form.ajaxForm({
        dataType: 'json',
        beforeSubmit: beforeSubmit,
        success: onAjaxReturn
    });
}
