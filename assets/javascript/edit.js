var formTabManager = null;
var url_modified = false;

function save_code()
{
	$('form_element').sendPhpr('onSave', {
		prepareFunction: function(){phprTriggerSave();},
		extraFields: {redirect: 0},
		loadIndicator: {show: false},
		onBeforePost: LightLoadingIndicator.show.pass('Saving...'),
		onComplete: LightLoadingIndicator.hide,
		onFailure: popupAjaxError,
		onAfterUpdate: setupInfoSize,
		onAfterError: setupInfoSize,
		onBeforeScriptEval: function(){UnloadManager.reset_changes()},
		update: 'multi'});

	return false;
}

function setupInfoSize()
{
	backend_trigger_layout_updated();
}

window.addEvent('domready', function(){
	$(document.getElement('html')).bindKeys({
		'meta+s, ctrl+s': save_code
	});

	initSidebarTabManager();
	(function(){setupInfoSize();}).delay(600);

	jQuery('#splitter-table').backendSplitter({
		minWidth: 300,
		saveWidth: true
	});
	jQuery('#content').fullHeightLayout();

	window.addEvent('phpr_editor_resized', setupInfoSize);
	window.addEvent('phpr_form_collapsable_updated', setupInfoSize);
	window.addEvent('phpreditoradded', function(){
		(function(){setupInfoSize();}).delay(600);
	});
});

function initSidebarTabManager()
{
	if ($('sidebar_tabs'))
		new TabManager('sidebar_tabs', 'sidebar_pages', {trackTab: false});
}

function assignEditorEvents(editor)
{
	if (!editor || !tinyMCE.get(editor))
	{
		assignEditorEvents.delay(300, null, editor);
		return;
	}

	var container = $(tinyMCE.get(editor).getContainer());
	if (container)
	{
		var glyph = container.getElement('a.mceResize');
		if (glyph)
		{
			glyph.addEvent('mousemove', setupInfoSize);
			glyph.addEvent('mouseout', setupInfoSize);
		}

		var statusbar = container.getElement('td.mceStatusbar');
		if (statusbar)
			statusbar.addEvent('mouseout', setupInfoSize);
	}

	setupInfoSize();
}

window.addEvent('phpreditoradded', assignEditorEvents)