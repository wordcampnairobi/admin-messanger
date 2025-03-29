
jQuery(document).ready(function($) {
    // Submit new message
    $('#internal-messages-form').on('submit', function(e) {
        e.preventDefault();
        
        var subject = $('#message-subject').val();
        var message = $('#message-content').val();
        
        if (!subject || !message) {
            alert('Please enter both subject and message');
            return;
        }
        
        $.ajax({
            url: internalMessages.ajaxurl,
            type: 'POST',
            data: {
                action: 'internal_messages_save',
                nonce: internalMessages.nonce,
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('Message sent successfully!');
                    $('#message-subject').val('');
                    $('#message-content').val('');
                    // Redirect to messages list
                    window.location.href = 'admin.php?page=internal-messages';
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while saving the message');
            }
        });
    });
    
    // Mark message as read
    $('.mark-read').on('click', function(e) {
        e.preventDefault();
        
        var messageId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: internalMessages.ajaxurl,
            type: 'POST',
            data: {
                action: 'internal_messages_mark_read',
                nonce: internalMessages.nonce,
                message_id: messageId
            },
            success: function(response) {
                if (response.success) {
                    row.removeClass('unread-message');
                }
            }
        });
    });
    
    // Delete message
    $('.delete-message').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this message?')) {
            return;
        }
        
        var messageId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: internalMessages.ajaxurl,
            type: 'POST',
            data: {
                action: 'internal_messages_delete',
                nonce: internalMessages.nonce,
                message_id: messageId
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while deleting the message');
            }
        });
    });
});
