
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form id="internal-messages-form" class="message-form">
        <div class="form-field">
            <label for="message-subject">Subject:</label>
            <input type="text" id="message-subject" name="subject" required>
        </div>
        
        <div class="form-field">
            <label for="message-content">Message:</label>
            <?php
                wp_editor(
                    '',
                    'message-content',
                    array(
                        'textarea_name' => 'message',
                        'media_buttons' => false,
                        'textarea_rows' => 10,
                        'teeny' => true,
                    )
                );
            ?>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Send Message">
        </p>
    </form>
</div>
