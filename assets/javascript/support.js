function update_guest_submit()
{
    jQuery('#AhoySupport_Config_guest_auto_signup').get(0).cb_update_enabled_state(jQuery('#AhoySupport_Config_ticket_allow_guests').is(':checked'));
}

function update_use_credits()
{
    jQuery('#AhoySupport_Config_credit_product_id').attr('disabled', !jQuery('#AhoySupport_Config_use_credits').is(':checked'));
    $('AhoySupport_Config_credit_product_id').select_update();
}

jQuery(document).ready(function($) {
    $('#AhoySupport_Config_ticket_allow_guests').bind('click', update_guest_submit);
    update_guest_submit();

    $('#AhoySupport_Config_use_credits').bind('click', update_use_credits);
    update_use_credits();
});