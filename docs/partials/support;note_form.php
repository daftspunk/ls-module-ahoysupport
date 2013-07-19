<? if (isset($ticket) && $ticket): ?>
    <?=open_form()?>
        <input type="hidden" name="ticket_id" value="<?=$ticket->id?>" />
        <input type="hidden" name="email_hash" value="<?=$ticket->email_hash?>" />
        <input type="hidden" name="note_submit" value="1" />
        <input type="hidden" name="redirect" value="<?=root_url($this->page->url)?>/%s" />
        <h4>Reply</h4>
        <div>
            <label for="description">Message</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <div>
            <label>Attach files</label>
            <input name="files[]" type="file">
            <a href="javascript:;" onclick="ticket_attach_file(this)">Attach another file</a>
        </div>
        <div>
            <input type="submit" name="submit" value="Submit reply" />
        </div>
    </form>
<? else: ?>
    <blockquote>Ticket not found</blockquote>
<? endif ?>