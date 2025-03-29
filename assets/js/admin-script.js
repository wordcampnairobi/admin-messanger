jQuery(document).ready(function($) {
    // Handle message submission
    $('#admin-messenger-send-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $response = $('#admin-messenger-response');
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.text();
        
        $submitBtn.text(adminMessenger.i18n.sending).prop('disabled', true);
        $response.removeClass('success error').hide();
        
        const data = {
            action: 'admin_messenger_send_message',
            security: adminMessenger.nonce,
            recipient_id: $('#admin-messenger-recipient').val(),
            subject: $('#admin-messenger-subject').val(),
            message: $('#admin-messenger-message').val()
        };
        
        $.post(adminMessenger.ajaxurl, data, function(response) {
            if (response.success) {
                $response.addClass('success').text(adminMessenger.i18n.messageSent).show();
                $form[0].reset();
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                $response.addClass('error').text(response.data).show();
            }
        }).fail(function() {
            $response.addClass('error').text(adminMessenger.i18n.errorSending).show();
        }).always(function() {
            $submitBtn.text(originalBtnText).prop('disabled', false);
        });
    });
    
    // Handle mark as read
    $(document).on('click', '.mark-as-read', function() {
        const $button = $(this);
        const messageId = $button.data('message-id');
        const originalBtnText = $button.text();
        
        $button.text(adminMessenger.i18n.marking).prop('disabled', true);
        
        const data = {
            action: 'admin_messenger_mark_read',
            security: adminMessenger.nonce,
            message_id: messageId
        };
        
        $.post(adminMessenger.ajaxurl, data, function(response) {
            if (response.success) {
                $button.closest('tr').removeClass('unread-message');
                $button.remove();
                // Update admin bar and menu count
                if (typeof wp !== 'undefined' && wp.a11y && wp.a11y.speak) {
                    wp.a11y.speak(adminMessenger.i18n.messageSent);
                }
            }
        }).always(function() {
            $button.text(originalBtnText).prop('disabled', false);
        });
    });
});
