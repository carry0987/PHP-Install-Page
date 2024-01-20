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

    checkPasswordConfirmation = ($password, $confirmPassword, message) => {
        if ($password.val() !== $confirmPassword.val()) {
            this.showMsg(false, message);
            return false;
        }
        this.showMsg(true, "");
        return true;
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
        $(document).on('change blur keydown keyup click', function() {
            let empty = $('input').filter(function() {
                return $.trim($(this).val()).length == 0
            }).length == 0;
            if ($('#checkbox').html() == '' && $('#display').html() == '' && empty === true) {
                $('button#submit').prop('disabled', false);
            } else {
                $('button#submit').prop('disabled', true);
            }
        });
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
                method: this.checkInput,
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
        $('#install').submit(function(e) {
            const isUsernameValid = self.checkInput($('#admin'), lang['install']['username_empty']);
            const isPasswordValid = self.checkInput($('#admin_psw'), lang['install']['password_rule']);
            const isPasswordConfirmValid = self.checkPasswordConfirmation($('#admin_psw'), $('#admin_psw_confirm'), lang['install']['repassword_error']);
            if (!isUsernameValid || !isPasswordValid || !isPasswordConfirmValid) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    }
}

