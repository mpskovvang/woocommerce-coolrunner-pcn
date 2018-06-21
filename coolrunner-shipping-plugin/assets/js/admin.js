jQuery(function ($) {
    if ($('.wc-shipping-zone-methods').length !== 0) {
        var bound = {
            carrier: false,
            product: false
        };
        (function digest() {
            var carrierSelect = $('#woocommerce_coolrunner_carrier'),
                productSelect = $('#woocommerce_coolrunner_product'),
                carrierOptions = carrierSelect.find('option:not(:first-child)'),
                productOptions = productSelect.find('option:not(:first-child)'),
                serviceOptions = $('[name*=coolrunner_service_]');
            if ((carrierSelect.length + productSelect.length) === 0) {
                bound = {
                    carrier: false,
                    product: false
                }
            } else {
                if (!bound.carrier) {
                    carrierSelect.on('change input', function () {
                        if (carrierSelect.val()) {
                            productOptions.each(function () {
                                var _po = $(this);
                                if (_po.val().indexOf(carrierSelect.val()) !== -1) {
                                    _po.css('display', '');
                                } else {
                                    if (_po.prop('selected')) {
                                        productSelect[0].selectedIndex = 0;
                                    }
                                    _po.css('display', 'none');
                                }
                            });
                        } else {
                            productOptions.css('display', 'none');
                            productSelect[0].selectedIndex = 0;
                        }
                        productSelect.trigger('change');
                    }).trigger('change');

                    productSelect.on('change input', function () {
                        serviceOptions.each(function () {
                            var _so = $(this),
                                parent = _so.parents('tr');
                            if (_so.is('[data-service=' + productSelect.val() + ']')) {
                                parent.css('display', '');
                            } else {
                                _so.val(-1);
                                parent.css('display', 'none');
                            }
                        });
                    }).trigger('change');

                    $('#btn-ok').on('click', function (e) {
                        var invalids = $('[name*=woocommerce_coolrunner]:invalid');
                        if (invalids.length !== 0) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            invalids.each(function () {
                                if (!$(this).next().is('.cr-color-red')) {
                                    $('<div class="cr-color-red">This field is required</div>').insertAfter($(this));
                                }

                                $(this).on('input change keyup', function () {
                                    if ($(this).next().is('.cr-color-red')) {
                                        $(this).next().remove();
                                    }
                                })
                            });

                            return false;
                        }
                    });
                    bound.carrier = true;

                }
            }
            setTimeout(digest, 100);
        })()
    }
});