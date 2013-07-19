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