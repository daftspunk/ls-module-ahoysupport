window.addEvent('domready', function(){
	jQuery('#listAhoySupport_Tickets_index_list').tableRowMenu();
	$('listAhoySupport_Tickets_index_list').addEvent('listUpdated', function(){
		jQuery('#listAhoySupport_Tickets_index_list').tableRowMenu();
	})
})

function tickets_selected() {
    return $('listAhoySupport_Tickets_index_list_body').getElements('tr td.checkbox input').some(function(element){return element.checked});
}

function update_ticket_list() {
    $('ticket_control_panel').sendPhpr('index_onPull',{
        loadIndicator: {show: false},
        onBeforePost: LightLoadingIndicator.show.pass('Updating the list...'), 
        onComplete: LightLoadingIndicator.hide,
        evalScriptsAfterUpdate: true
    });
    
    return false;
}

function set_assignee(user_id) {
    if (!tickets_selected())
    {
        alert('Please select tickets first.');
        return false;
    }
    
    $('listAhoySupport_Tickets_index_list_body').getForm().sendPhpr('index_onSetAssignee',{
        loadIndicator: {show: false},
        onBeforePost: LightLoadingIndicator.show.pass('Loading...'), 
        onComplete: LightLoadingIndicator.hide,
        evalScriptsAfterUpdate: true,
        extraFields: {'user_id': user_id}
    });
    
    return false;
}

function close_tickets() {
    
    if (!tickets_selected()) {
        alert('Please select tickets to close.');
        return false;
    }
    
    if (!confirm('Do you really want to close selected ticket(s)?'))
        return false;
    
    $('listAhoySupport_Tickets_index_list_body').getForm().sendPhpr('index_onClose',{
        loadIndicator: {show: false},
        onBeforePost: LightLoadingIndicator.show.pass('Loading...'), 
        onComplete: LightLoadingIndicator.hide,
        evalScriptsAfterUpdate: true
    });
    
    return false;
}

function delete_tickets() {
    if (!tickets_selected())
    {
        alert('Please select tickets to delete.');
        return false;
    }
    
    if (!confirm('Do you really want to DELETE selected ticket(s)? This operation is permanent and cannot be undone.'))
        return false;
    
    $('listAhoySupport_Tickets_index_list_body').getForm().sendPhpr('index_onDelete',{
        loadIndicator: {show: false},
        onBeforePost: LightLoadingIndicator.show.pass('Loading...'), 
        onComplete: LightLoadingIndicator.hide,
        evalScriptsAfterUpdate: true
    });
    
    return false;
}