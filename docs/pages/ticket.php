<div id="page_support_tickets">
    <? if ($ticket): ?>
        <?= flash_message() ?>
        <h1>Support Ticket <?=$ticket->ticket_number?></h1>
        <div class="ticket_info">
            <h3>Ticket <?=$ticket->ticket_number?></h3>
            <p>Subject: <?=$ticket->title?></p>
            <p>Ticket status: <?=$ticket->status->name?></p>
        </div>
        <hr />
        <?=open_form(array('id'=>'support_ticket_'.$ticket->id))?>
            <? $this->render_partial('support:message', array('ticket' => $ticket)) ?>
        </form>
        <hr />
        <? foreach ($ticket->notes as $note): ?>
            <?=open_form(array('id'=>'support_note_'.$note->id))?>
                <? $this->render_partial('support:message', array('note' => $note)) ?>
            </form>
            <hr />
        <? endforeach ?>
        <? if ($ticket->status->code == AhoySupport_Ticket_Status::status_closed): ?>
            <blockquote>This ticket is closed</blockquote>
        <? else: ?>
            <div id="note_form">
                <?=$this->render_partial('support:note_form')?>
            </div>
        <? endif ?>
    <? else: ?>
        <h1>Submit Ticket</h1>
        <?= flash_message() ?>
        <?=open_form()?>
            <label for="ticket_category">Ticket Type</label>
            <select id="ticket_category" name="category_id" onchange="update_ticket_category()" onkeyup="update_ticket_category()">
                <option value=""></option>
                <? foreach ($categories as $category): ?>
                    <option value="<?=$category->id?>"><?=$category->name?></option>
                <? endforeach ?>
            </select>
        </form>
        <div id="ticket_form">
            <?=$this->render_partial('support:ticket_form')?>
        </div>
    <? endif ?>
</div>
<script>
    function ticket_attach_file(el) {
        var new_input = $('<input type="file" name="files[]" />');
        $(el).before(new_input);
    }
    function update_ticket_category() {
        $('#ticket_category').getForm().sendRequest('ahoysupport:on_category_update', {update: {'ticket_form': 'support:ticket_form'} });
    }
    function ticket_validate_form() {
        if (!$('#title').val().length) {
            alert('Please enter the ticket subject');
            $('#title').focus();
            return false;
        }
        if (!$('#description').val().length) {
            $('#description').focus();
            alert('Please enter the ticket description');
            return false;
        }
        return true;
    }    
</script>