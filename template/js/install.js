//Check third-party library usable
if (!Utils) throw new Error('Utils-Full not found');
if (!window.jQuery) throw new Error('jQuery not found');

class InstallHelper {
    'use strict'
    constructor() {
        this.act_btn = {};
    }

    /* Init */
    init(config = null) {
        return this.initValidation(config);
    }

    showMsg = (isValid, msg, target = '#display') => {
        const textWarn = `<span style='color: red'>`;
        const textNorm = `<span style='color: green'>`;
        const message = (isValid ? textNorm : textWarn) + msg + `</span>`;
        $(target).html(message);
    }

    checkInput = ($element, value) => {
        if (!$element.val().trim()) {
            this.showMsg(false, value);
            return false;
        }
        this.showMsg(true, "");
        return true;
    }

    checkPasswordLength = ($element, value) => {
        if ($element.val().trim().length < 8) {
            this.showMsg(false, value);
            return false;
        }
        this.showMsg(true, "");
        return true;
    }

    checkPasswordConfirmation = ($password, $confirmPassword, message) => {
        if ($password.val() !== $confirmPassword.val()) {
            this.showMsg(false, message);
            return false;
        }
        this.showMsg(true, "");
        return true;
    }

    validateInputs = () => {
        const $inputs = $('#install input').not('[type="submit"]');
        const isDisplayEmpty = $('#display > span').is(':empty');
        const isEmpty = $inputs.toArray().some(input => !$.trim($(input).val()).length);
        
        $inputs.each(function() {
            const $input = $(this);
            if (!$.trim($input.val()).length) {
                $input.addClass('bg-danger-subtle');
            } else {
                $input.removeClass('bg-danger-subtle');
            }
        });

        $('#submit').prop('disabled', isEmpty || !isDisplayEmpty);
    }

    //Send form data
    sendFormData = (url, data, method = 'POST', success = null, errorCallback = null) => {
        return Utils.sendFormData({
            url: url,
            method: method,
            data: data,
            success: success,
            errorCallback: errorCallback
        });
    }

    fetchData = (data, resolveData, method = 'POST', parameter = null) => {
        let url = 'api.php';
        if (parameter) {
            url += '?' + parameter;
        }
        return new Promise((resolve, reject) => {
            Utils.doFetch({
                url: url,
                method: method,
                body: Utils.encodeFormData(data),
                success: function(res) {
                    resolve(resolveData(res));
                },
                error: function(error) {
                    reject(error);
                }
            });
        });
    }

    //Get language list
    langList = () => {
        return this.fetchData({ request: 'get_language' }, data => data['lang']);
    }

    //Get language option
    langOption = () => {
        return this.fetchData({ request: 'get_language_list' }, data => data);
    }

    async initValidation(config) {
        const self = this;

        // Lang
        const lang = await this.langList();
        console.log(lang);

        // Input actions
        this.inputActions = {
            'admin': {
                method: this.checkInput,
                messageKey: 'username_empty'
            },
            'admin_psw': {
                method: this.checkPasswordLength,
                messageKey: 'password_rule'
            },
            'admin_psw_confirm': {
                method: this.checkPasswordConfirmation,
                messageKey: 'repassword_error'
            },
            'db_host': {
                method: this.checkInput,
                messageIdx: 'database',
                messageKey: 'db_host_empty'
            },
            'db_port': {
                method: this.checkInput,
                messageIdx: 'database',
                messageKey: 'db_port_empty'
            },
            'db_name': {
                method: this.checkInput,
                messageIdx: 'database',
                messageKey: 'db_name_empty'
            },
            'db_user': {
                method: this.checkInput,
                messageIdx: 'database',
                messageKey: 'db_user_empty'
            },
            'db_password': {
                method: this.checkInput,
                messageIdx: 'database',
                messageKey: 'db_password_empty'
            },
        };

        // Input event
        $('#install').on('input blur propertychange', 'input', (e) => {
            const id = e.target.id;
            const action = this.inputActions[id];
            if (action && typeof action.method === 'function') {
                const index = action.messageIdx ? action.messageIdx : 'install';
                if (id === 'admin_psw_confirm') {
                    action.method($('#admin_psw'), $(e.target), lang[index][action.messageKey]);
                } else {
                    action.method($(e.target), lang[index][action.messageKey]);
                }
            }
        });

        // Check input before submit
        $('#install').on('input', 'input', (event) => {
            const $input = $(event.target);
            $input.removeClass('bg-danger-subtle');
            this.validateInputs();
        });

        $('#install').on('input blur', 'input', (event) => {
            this.validateInputs();
        });

        $('#install').on('submit', (event) => {
            event.preventDefault();
            this.validateInputs();
            const isUsernameValid = self.checkInput($('#admin'), lang['install']['username_empty']);
            const isPasswordValid = self.checkInput($('#admin_psw'), lang['install']['password_rule']);
            const isPasswordConfirmValid = self.checkPasswordConfirmation($('#admin_psw'), $('#admin_psw_confirm'), lang['install']['repassword_error']);
            if (!isUsernameValid || !isPasswordValid || !isPasswordConfirmValid) {
                return false;
            }
            if (!$(event.target).find('#submit').prop('disabled')) {
                console.log('submit');
            }
        });
    }
}

