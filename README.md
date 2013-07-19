# ls-module-ahoysupport

Issue Tracker Support Module is a tracker for issues, bugs, complaints, questions,
comments or just about anything for your store. Tickets have various statuses,
priorities and can be assigned to admin users.

## Instructions

1. Install module
1. Create the two pages below, setting their Page Action correctly
1. Create the two partials below
1. Select the ticket CMS page under Support > Settings

## Front end pages...

For your convenience, the code snippets below are a bare bones implementation and will require styling to match your sites look and feel.

## Page - Tickets (action = ahoysupport:tickets)

```php
<div id="page_support_tickets">
    <h1>Support Tickets</h1>
    <a href="<?=root_url('support/ticket')?>">Submit a new ticket</a>
    <hr />
    <? if ($tickets): ?>
    <table class="nice" width="100%">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($tickets as $ticket): ?>
            <tr>
                <td>#<?=$ticket->id?></td>
                <td><a href="<?=root_url('support/ticket/'.$ticket->id)?>"><?=$ticket->title?></a></td>
                <td><?=$ticket->status->name?></td>
                <td><?=$ticket->ticket_age?> ago</td>
            </tr>
        <? endforeach ?>
        </tbody>
    </table>
    <? else: ?>
    <p>No tickets found</p>
    <? endif ?>
</div>
```

## Page - Ticket (action = ahoysupport:ticket)

```php
<div id="page_support_tickets">
    <? if ($ticket): ?>
        <h1>Support Ticket <?=$ticket->ticket_number?></h1>
        <div class="ticket_info">
            <h3>Ticket <?=$ticket->ticket_number?></h3>
            <p>Subject: <?=$ticket->title?></p>
            <p>Ticket status: <?=$ticket->status->name?></p>
        </div>
        <hr />
        <div class="ticket_description">
            <blockquote>Original message by <?=$ticket->customer->name?>. Posted @ <?=$ticket->created_at?></blockquote>
            <?=$ticket->description?>
        </div>
        <hr />
        <? foreach ($ticket->notes as $note): ?>
            <div class="ticket_note">
                <blockquote>Response by <?=$note->author_name?>. Posted @ <?=$note->created_at?></blockquote>
                <?=$note->description?>
            </div>
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
        <?=open_form()?>
            <label for="ticket_category">Ticket Type</label>
            <select id="ticket_category" name="category_id" onchange="update_ticket_category()" onkeyup="update_ticket_category()">
                <option value=""></option>
                <? foreach ($categories as $category): ?>
                    <option value="<?=$category->id?>"><?=$category->name?></option>
                <? endforeach ?>
            </select>
        </form>
        <script>
            function update_ticket_category() {
                $('ticket_category').getForm().sendRequest('ahoysupport:on_category_update', {update: {'ticket_form': 'support:ticket_form'} });
            }
        </script>
        <div id="ticket_form">
            <?=$this->render_partial('support:ticket_form')?>
        </div>
    <? endif ?>
</div>
```

## Partial - support:ticket_form

```php
<? if (isset($category) && $category): ?>
    <?=open_form()?>
        <input type="hidden" name="category_id" value="<?=$category->id?>" />
        <input type="hidden" name="ticket_submit" value="1" />
        <input type="hidden" name="redirect" value="<?=Phpr::$request->getRequestUri()?>/%s" />
        <p><?=$category->description?></p>
        <div>
            <label for="title">Priority</label>
            <select id="ticket_priority" name="priority_id">
                <option value=""></option>
                <? foreach ($priorities as $priority): ?>
                    <option value="<?=$priority->id?>"><?=$priority->name?></option>
                <? endforeach ?>
            </select>
        </div>
        <div>
            <label for="title">Ticket Subject</label>
            <input id="title" value="" maxlength="255" name="title" type="text">
        </div>
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
            <input type="submit" name="submit" value="Submit ticket" />
        </div>
    </form>
    <script>
        function ticket_attach_file(el) {
            var new_input = new Element('input', {name: 'files[]', type: 'file'});
            new_input.inject(el,'before');
        }
    </script>
<? else: ?>
    <blockquote>Please select a ticket type</blockquote>
<? endif ?>
```

## Partial - support:note_form

```php
<? if (isset($ticket) && $ticket): ?>
    <?=open_form()?>
        <input type="hidden" name="ticket_id" value="<?=$ticket->id?>" />
        <input type="hidden" name="note_submit" value="1" />
        <input type="hidden" name="redirect" value="<?=Phpr::$request->getRequestUri()?>" />
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
    <script>
        function ticket_attach_file(el) {
            var new_input = new Element('input', {name: 'files[]', type: 'file'});
            new_input.inject(el,'before');
        }
    </script>
<? else: ?>
    <blockquote>Ticket not found</blockquote>
<? endif ?>
```


