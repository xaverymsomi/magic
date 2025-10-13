$(function () {
    // Check if the state of the current menu has been saved before
    if (localStorage.getItem('CurrentMenu') !== null) {
        SetScreenMenu(localStorage.getItem('CurrentMenu'));
    }

    // Set the page title in case it is not properly set
    if (localStorage.getItem('CurrentPageTitle') !== null) {
        document.title = localStorage.getItem('CurrentPageTitle');
    }

    // Store the current location for backward navigation
    window.history.pushState({
        html: $('#mabrexPageContent').html(),
        pageTitle: window.document.title,
        pageLink: window.location.href
    }, '', window.location.href);

    //Menu Toggler
    $('#mabrexMenuToggler').click(function () {
        if ($('#mabrexSideNavBar').hasClass('hidden-lg')) {
            SetScreenMenu('LARGE');
            localStorage.setItem('CurrentMenu', 'LARGE');
        } else {
            SetScreenMenu('SMALL');
            localStorage.setItem('CurrentMenu', 'SMALL');
        }
    });

    // For adding devices to partner and agents
    $(document).on('click', 'tr.selectable-list > td > input[type=checkbox]', function () {
        var scope = $(this).scope();
        //$(this).prev('input:hidden').val($(this).is(':checked'));
        if ($(this).is(':checked')) {
            $(this).closest('tr').addClass('success');
        } else {
            $(this).closest('tr').removeClass('success');
        }
        scope.$apply();
        //console.log(scope.form);
    });

    if (location.href.indexOf("/Card/printcard") > -1) {
        if ($(document).find('#data_content').attr('data-form') !== undefined) {
            var scope = $(this).scope();
            scope.form = JSON.parse($(document).find('#data_content').attr('data-form'));
            for (var i = 0; i < scope.form.cards_list.length; i++) {
                scope.form.cards_list[i]['Last Update'] = new Date(scope.form.cards_list[i]['Last Update']);
            }
            scope.$apply();
            //console.log(scope.form);
        }
    }
    
    if (location.href.indexOf("/Application/index") > -1) {
        if (localStorage.getItem("ShowProfile") !== null && localStorage.getItem("ApplicantId") !== null){
            var scope = $(document).find('#appPageContainer').scope();
            scope.showProfile('Application', localStorage.getItem("ApplicantId"));
            
            localStorage.removeItem("ShowProfile");
            localStorage.removeItem("ApplicantId");
        }
    }
    
    var degr = 90;
    $(document).on('click', '.image-rotate', function () {
        var rotate_angle_string = 'rotate(' + degr + 'deg)';

        $(document).find('#applicationPassportPreviewImage').find('img').css({
            '- webkit - transform': rotate_angle_string,
            '- moz - transform': rotate_angle_string,
            '- o - transform': rotate_angle_string,
            '- ms - transform': rotate_angle_string,
            'transform': rotate_angle_string
        });

        degr = degr + 90;
    });
});

// When the back or foreward buttons of the browser are clicked
window.onpopstate = function (e) {
    if (e.state) {
        $('#mabrexPageContent').load(e.state.pageLink + ' #page-content');
        document.title = e.state.pageTitle;
    }
};

$(document).on('shown.bs.modal', function () {
    $('#myModal').on('focus', '#page-content div.modal-body div.form-horizontal input.autocomplete', function () {
        var table_name = $(this).attr('data-table');
        var label_name = $(this).attr('id');
        var id_name = $(this).parent().children('input[type="hidden"]').attr('id');

        autoComplete(table_name, label_name, id_name);
    });
});

function autoComplete(table_name, label_name, id_name) {
    $('#fullModal input.' + label_name).autocomplete({
        minLength: 2,
        source: "/inc/autoComplete.php?table=" + table_name,
        appendTo: "#fullModal",
        select: function (event, ui) {
            event.preventDefault();
            $(this).val(ui.item.label);
            $(this).parent().children('input.' + id_name).val(ui.item.value);
            return false;
        }
    });
}

$(document).on("click", "tbody > tr.mabrex-clickable-row > td:not('td.mabrex-clickable-exclude')", function () {
    var id = $(this).parents('tr').children('td[data-mabrex-id]').attr('data-mabrex-id');
    if ($(this).parents('tr').children('td[data-mabrex-row-id]').length > -1) {
        id = $(this).parents('tr').children('td[data-mabrex-row-id]').attr('data-mabrex-row-id');
    }
    var classname = $(this).parents('tr').children('td[data-mabrex-id]').attr('data-mabrex-class');
    //var table = $(this).parents('tr').children('td[data-mabrex-id]').attr('data-mabrex-table');

    if ($(this).parents('tr').find('td').children('.show_pending').length > 0) {
//        console.log('do');
        angular.element('#data-view').scope().redirectToApproval($(this).parents('tr').children('#row_id').children('.show_pending').val());
    } else {
        angular.element('#data-view').scope().showProfile(classname, id);
    }


});

//get applicant id from dashboard to applicant profile when applicant with pending leave is clicked 
$(document).on('click', ".mabrex-clickable-dashboard-row", function () {

    //$.cookie("id", $(this).children('td:first-child').attr('value'));

    //window.location.href = "/applicant/index";

    var id = $(this).children('td:first-child').attr('value');

    angular.element('.mabrex-clickable-dashboard-row').scope().showProfile('applicant', 'mx_applicant', id);
});

//1
$(document).on("click", "tbody > tr > td.mabrex-clickable-exclude > a", function (e) {
    e.preventDefault();
    var id = $(this).attr('data-mabrex-row-id');
    var url = $(this).attr('data-mabrex-url');
    var action = $(this).attr('data-action');
    var scope = angular.element('#data-view-actions').scope();
    scope.showActionForm(id, url, action);
});
//Both 1 above and 2 below are pointing to the same a, so when you click action buttons both were affected, that is why i commented the #2 one.
//2
//$(document).on("click", "tbody > tr.mabrex-action-clickable-row > td.mabrex-clickable-exclude > a", function (e) {//for rows excluded from opening profile
//    e.preventDefault();
//    var id = $(this).attr('data-mabrex');
//    var url = $(this).attr('data-mabrex-url');
//    var action = $(this).attr('data-action');
//    angular.element('#data-view-actions').scope().showActionForm(id, url, action);
//});

$(".modal-fullscreen").on('show.bs.modal', function () {

    setTimeout(function () {
        $(".modal-backdrop").addClass("modal-backdrop-fullscreen");
    }, 0);
});

$(".modal-fullscreen").on('hidden.bs.modal', function () {
    alert();
    $(".modal-backdrop").addClass("modal-backdrop-fullscreen");
});

$(document).on('click', '.mabrex-submenu-hide', function () {
    $(this).parent('li').parent('ul').css('display', 'none');
});

$(document).on('click', '.mabrex-submenu-show', function () {
    $(this).siblings('ul').css('display', 'block');
});

$(window).resize(function () {
    if ($(window).width() < 992) {
        SetScreenMenu('SMALL');
        localStorage.setItem('CurrentMenu', 'SMALL');
    } else {
        SetScreenMenu('LARGE');
        localStorage.setItem('CurrentMenu', 'LARGE');
    }
});

$(document).on('click', '.commission-slab-adder', function () {
    var row = $(this).parent().parent();
    var copy = row.clone();
    copy.find('.commission-slab-remover').removeClass('hidden');
    copy.find('input[type=number]').val('');
    row.parent().append(copy);
});

$(document).on('click', '.commission-slab-remover', function () {
    $(this).parent().parent().remove();
});

$(document).on('click', '.account-adder', function () {
    var row = $(this).parent().parent();
    var copy = row.clone();
    copy.find('.account-remover').removeClass('hidden');
    copy.find('input[type=text]').val('');
    row.parent().append(copy);
});

$(document).on('click', '.account-remover', function () {
    $(this).parent().parent().remove();
});

$(document).on('click', '.image-adder', function () {
    var row = $(this).parent().parent();
    var copy = row.clone();
    copy.find('.image-remover').removeClass('hidden');
    copy.find('input[type=file]').val('');
    copy.find('div.preview').remove();
    row.parent().append(copy);
    var $scope = copy.find('input[type=file]').scope();
    $scope.callmxImageUploader();
});

$(document).on('click', '.image-remover', function () {
    $(this).parent().parent().remove();
});


$(document).on('click', '.property-adder', function () {
    var row = $(this).parent().parent();
    var copy = row.clone();
    copy.find('.property-remover').removeClass('hidden');
    row.parent().append(copy);
});

$(document).on('click', '.property-remover', function () {
    $(this).parent().parent().remove();
});


$(document).on('click', '.class-data-adder', function () {
    var row = $(this).parent().parent();
    var copy = row.clone();
    copy.find('.class-data-remover').removeClass('hidden').prop('disabled', false);
    copy.find('input[type=number]').val('');
    copy.find('input[type=text]').val('');
    copy.find('input[type=checkbox]').prop('checked', false);
    copy.attr('class_row_id', '');
    row.parent().append(copy);
});

$(document).on('click', '.class-data-remover', function () {
    $(this).parent().parent().remove();
});

$(document).on('click', '.service-extender', function () {
    let icon = $(this).children('i');
    let text = $(this).children('span');
    if ($(icon).hasClass('fa-angle-down')) {
        $(icon).removeClass('fa-angle-down').addClass('fa-angle-up');
        $(text).text(' Collapse');
    } else {
        $(icon).removeClass('fa-angle-up').addClass('fa-angle-down');
        $(text).text(' Expand');
    }
});

function SetScreenMenu(menu_state) {
    var $scope = $('#mabrexMenuContainer').scope();
    if (menu_state === 'SMALL') {
        $('#mabrexSideNavBar').addClass('hidden-lg').addClass('hidden-md');
        $('#mabrexSideNavBarSmall').removeClass('hidden-lg').removeClass('hidden-md');
        $('body div.main').css('padding-left', 60);
        $('#mabrexTopNavBar').css('margin-left', 50);
        $('#mabrexLogoPlaceholder').addClass('hidden-lg hidden-md');
        $('#mabrexLogoPlaceholderMobile').removeClass('hidden-lg hidden-md');

        if ($scope !== undefined) {
            $scope.$apply(function () {
                $scope.current_task = 'hide';
            });
        }
    } else {
        $('#mabrexSideNavBar').removeClass('hidden-lg').removeClass('hidden-md');
        $('#mabrexSideNavBarSmall').addClass('hidden-lg').addClass('hidden-md');
        $('body div.main').css('padding-left', 250);
        $('#mabrexTopNavBar').css('margin-left', 250);
        $('#mabrexLogoPlaceholder').removeClass('hidden-lg hidden-md');
        $('#mabrexLogoPlaceholderMobile').addClass('hidden-lg hidden-md');

        if ($scope !== undefined) {
            $scope.$apply(function () {
                $scope.current_task = 'display';
            });
        }
    }
}

// Function to Check or Unckeck checkbox inputs contained in a column
function selectColumn(e) {
    var col = e.parent().index() + 2;
    var body = e.parent().parent().parent().siblings('tbody');

    body.children('tr').each(function () {
        $(this).children('td:nth-child(' + col + ')').children('input').prop('checked', e.is(':checked'));
    });
}

// Function to Checl or Unchek checkbox inputs contained in a column within a single section
function selectSectionPermission(e) {
    var section = e.parent().index() + 2;
    var container = e.parent().parent().parent();

    container.children('tr').not(':first').each(function () {
        $(this).children('td:nth-child(' + section + ')').children('input').prop('checked', e.is(':checked'));
    });
}


//checking from stakeholder for email existing if email exist it wont register
var checkEmailExisting = function (el, source) {
    var email = $(el).val();
    $.get(app_url + '/views/stakeholder/checkemail.php?email=' + email + '&source=' + source, function (response) {
        if (response > 0) {
            var $scope = $(document).find('input#email').scope();

            if (source === 'partner') {
                $scope.form.partner_data.email = '';
            } else if (source === 'agent') {
                $scope.form.agent_data.email = '';
            } else if (source === 'retailer') {
                $scope.form.retailer_data.email = '';
            } else if (source === 'customer') {
                $scope.form.email = '';
            } else if (source === 'user') {
                $scope.form.email = '';
            }
            var error = "${email} is already used";
            $(el).val('');
            $scope.$apply();
            $(el).siblings('span.error-message').text(error);
        } else {
            $(el).siblings('span.error-message').text('');
        }
    });
};

$(document).on('blur', '#txt_mobile', function () {
    phone = $(document).find('input#txt_mobile').val();
    var $scope = $(document).find('input#txt_mobile').scope();
    if (phone !== undefined) {
        phone = phone.replace(/[^0-9]/g, '');
        $scope.form.txt_mobile = phone;
    }

    // if (phone.length != 12)
    if (phone.length != 10)
    {
        // console.log('Phone number must be 10 digits.');
        // $(document).find('div.mobile_error').text('Phone number must be 12 digits.');
        $(document).find('div.mobile_error').text('Phone number must be 10 digits in format 07XXXXXXXX or 06XXXXXXXX.');
        // $('#txt_mobile').focus();
    } else {
        $(document).find('div.mobile_error').text('');
    }
    ;
});
$(document).on('blur', '.txt_mobile', function () {
    phone = $(document).find('input#' + $(this).attr('id')).val();
    var $scope = $(document).find('input#' + $(this).attr('id')).scope();
    if (phone !== undefined) {
        phone = phone.replace(/[^0-9]/g, '');
        $scope.form[$(this).attr('name')] = phone;
    }
    if (phone.length != 12)
    {
        // console.log('Phone number must be 10 digits.');
        $(document).find('div.' + $(this).attr('name') + '_error').text('Phone number must be 12 digits.');
        // $(this).focus();
    } else {
        $(document).find('div.' + $(this).attr('name') + '_error').text('');
    }
    ;
});
//checking from stakeholder for mobile existing if mobile exist it wont register
var checkPhoneExisting = function (el, source) {
    var phone = $(el).val();
    $.get(app_url + '/views/stakeholder/checkphone.php?phone=' + phone + '&source=' + source, function (response) {
        if (response > 0) {
            var $scope = $(document).find('input#email').scope();

            if (source === 'partner') {
                $scope.form.partner_data.txt_mobile = '';
            } else if (source === 'agent') {
                $scope.form.agent_data.txt_mobile = '';
            } else if (source === 'retailer') {
                $scope.form.retailer_data.txt_mobile = '';
            } else if (source === 'customer') {
                $scope.form.txt_mobile = '';
            } else if (source === 'user') {
                $scope.form.txt_mobile = '';
            }
            var error = "${phone} is already used";
            $(el).val('');
            $scope.$apply();
            $(el).siblings('span.error-message').text(error);
        } else {
            $(el).siblings('span.error-message').text('');
        }
    });
};

function getElementType(id) {
    return id.split("_")[0];
}

function getImageBase64Encoding(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.onload = function () {
        var reader = new FileReader();
        reader.onloadend = function () {
            callback(reader.result);
        };
        reader.readAsDataURL(xhr.response);
    };
    xhr.open('GET', url);
    xhr.responseType = 'blob';
    xhr.send();
}

function populateField(element, value) {
    var type = getElementType(element.id);
    if (type === 'opt') {
        element.selectedIndex = value;
    } else if (type === 'dat') {
        var d = new Date(value);
        var month = d.getMonth().toString().length === 1 ? ("0" + d.getMonth()) : d.getMonth();
        element.value = `${d.getFullYear().toString()}-${month.toString()}-${d.getDate().toString()}`;
    } else {
        element.value = value;
    }
    $(`#${element.id}`).change();
}

function processMerchantDetails(data) {
    var merchant_details = $("#merchant_details");
    getImageBase64Encoding('http://localhost:8089/assets/images/people/ashok.jpg', function (dataUrl) {
        $("#txt_image").val(dataUrl.split(",")[1]).change();
    });
    (Object.keys(data)).forEach(function (key) {
        var element = $('#' + key)[0];
        if (element !== undefined) {
            populateField(element, data[element.id])
        }
    });
    merchant_details.css("display", "block");
    $("#div_image").css("display", "block");
}
var merchantsFormElements = [];
function modifyMerchantsInputForm(readonly, hide) {
    readonly = readonly ? "readonly" : "";
    hide = hide ? "none" : "block";

    $("#div_image").css('display', hide);
    $("#merchant_details").css("display", hide);

    var merchant_details_form_elements = $("#merchant_details").children();
    $.each(merchant_details_form_elements, function (index, value) {
        var element = value.children[1].children[0];
        $(element).attr("readonly", readonly);
        merchantsFormElements.push(element)
    });
}

// $(document).on('click', '#add_merchant', function () {
//     setTimeout(function () {
//         $("#div_image").css('display',"none");
//         modifyMerchantsInputForm(true,true);
//     },100);
// });

// $(document).on('click', '#verify_zanid', function () {
//     clearNotification();
//     event.preventDefault();
//     var zanid = $("#txt_id_number").val();
//     var url = "/Merchant/verify_zan_id";
//     var headers = {
//         Accept: 'application/json',
//     };
//     var data = {
//         ZANID: zanid
//     };
//     $.ajax({
//         url: url,
//         type: 'post',
//         data: data,
//         headers: headers,
//         success: function (response) {
//             let values = JSON.parse($(response).find('#mabrexPageContent').text().trim());
//             if (values.error){
//                 notify('error',values.error);
//             } else {
//                 processMerchantDetails(JSON.parse(values));
//             }
//
//         },
//     });
// });

function clearNotification() {
    $(".notification-area").removeClass().addClass("notification-area").text(" ")
}

function notify(type, message = "") {
    clearNotification();
    var alertClass = '';
    if (type === 'error') {
        alertClass = 'alert alert-danger';
    } else if (type === 'success') {
        alertClass = 'alert alert-success';
    } else if (type === 'warning') {
        alertClass = 'alert alert-warning';
    } else if (type === 'info') {
        alertClass = 'alert alert-info';
    }
    $('.notification-area').addClass(alertClass).html(message);
}

$(document).on('click', '.investigation-opener', function () {
    var Complaint = $(document).find('#Complaint');
    var height = Complaint.height();
    var width = Complaint.width();
    var container = $(document).find('#investigationContainer');
    container.height(height + 50);
    container.width(width);
    container.css({'position': 'absolute', 'right': '25px', 'top': '90px', 'display': 'block'});
    $(document).find('#dialogTitle').text('Structure');
    setTimeout(function () {
    }, 200);
});

$(document).on('click', '.investigation-closer', function () {
    $(document).find('#dialogTitle').text('Profile');
    $(document).find('#investigationContainer').css('display', 'none');
});


