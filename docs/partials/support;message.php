<?
    $obj = (isset($note)) ? $note : $ticket;
    $type = (get_class($obj) == "AhoySupport_Ticket_Note") ? 'note' : 'ticket';
    $mode = (isset($mode)) ? $mode : post('mode', 'view');
    $can_edit = ($this->customer) ? ($this->customer->id == $obj->customer_id) : false;
    $form_id = 'support_'.$type.'_'.$obj->id;
    $action = 'ahoysupport:on_'.$type.'_edit';

    if ($type == 'ticket' && $obj->status->code == AhoySupport_Ticket_Status::status_closed)
        $can_edit = false;
    else if ($type == 'note' && $obj->ticket->status->code == AhoySupport_Ticket_Status::status_closed)
        $can_edit = false;
?>
<input type="hidden" name="<?=$type?>_id" value="<?=$obj->id?>" />
<div class="ticket <?=$type?>">
    <blockquote>
        <? if ($type=="ticket"): ?>
            Original message by <?=$obj->author_name?>
        <? else: ?>
            Response by <?=$obj->author_name?>
        <? endif ?>
        <? if ($can_edit): ?>
        <?=Phpr_DateTime::now()->substractDateTime($obj->created_at)->intervalAsString()?> ago
        &nbsp;
        <a href="javascript:;" onclick="$(this).getForm().sendRequest('<?=$action?>', { extraFields: { mode: 'edit' }, update: { '<?=$form_id?>': 'support:message' } })">Edit</a>
        <? endif ?>
    </blockquote>
    <? if ($mode == "edit"): ?>
        <div class="description edit">
            <textarea name="description"><?=$obj->description?></textarea>
            <p><a href="javascript:;" onclick="$(this).getForm().sendRequest('<?=$action?>', { extraFields: { mode: 'save' }, update: { '<?=$form_id?>': 'support:message' } })">Save</a>
                | <a href="javascript:;" onclick="$(this).getForm().sendRequest('<?=$action?>', { update: { '<?=$form_id?>': 'support:message' } })">Cancel</a></p>
        </div>
    <? else: ?>
        <div class="description view">
            <?=$obj->description?>
            <? if (count($obj->files)) { ?>
                <div class="files">
                    <p><strong>Uploaded Files</strong></p>
                    <ul>
                    <? foreach ($obj->files as $file) { ?>
                    <?
                        $file_link = ($type=="note")
                            ? $this->page->url.'/'.$obj->ticket->id.'/get_file/'.$file->id.'/'.$obj->id
                            : $this->page->url.'/'.$obj->id.'/get_file/'.$file->id;
                    ?>
                        <li><a href="<?=root_url($file_link)?>"><?=$file->name?></a></li>
                    <? } ?>
                    </ul>
                </div>
            <? } ?>
        </div>
    <? endif ?>
</div>