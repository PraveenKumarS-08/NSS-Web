/**
 * NSS Portal - Tier 1 Frontend AJAX API Bridge (jQuery)
 */
const NSS = {
    // API Request Wrapper
    request: function(endpoint, action, data = {}, callback = null) {
        data.action = action;
        return $.ajax({
            url: '../api/' + endpoint + '.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (typeof callback === 'function') callback(res);
            },
            error: function(xhr, status, err) {
                console.error('NSS API Error:', endpoint, action, err);
                if (typeof callback === 'function') callback({ success: false, message: 'Server communication error.' });
            }
        });
    },

    // Session status check
    checkSession: function(callback) {
        return $.getJSON('../api/auth.php?action=check_session', callback);
    },

    // Show Toast Notification
    toast: function(msg, isSuccess = true) {
        let bg = isSuccess ? '#166534' : '#b71c1c';
        let $t = $('<div></div>').css({
            'position': 'fixed', 'bottom': '24px', 'right': '24px',
            'background': bg, 'color': '#ffffff', 'padding': '12px 24px',
            'border-radius': '12px', 'box-shadow': '0 10px 25px rgba(0,0,0,0.2)',
            'font-weight': '700', 'z-index': '99999', 'font-size': '0.92rem'
        }).html((isSuccess ? '✓ ' : '⚠ ') + msg);
        
        $('body').append($t);
        setTimeout(() => { $t.fadeOut(400, () => $t.remove()); }, 3500);
    }
};
