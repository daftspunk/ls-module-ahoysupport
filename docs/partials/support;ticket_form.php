<? if (isset($category) && $category): ?>
<?
    $can_submit = ($category->credits_required <= 0) || (($this->customer) && ($this->customer->x_ahoysupport_credits >= $category->credits_required));
    $priority = (isset($priority)) ? $priority : null;
    $show_attachments = (isset($show_attachments)) ? $show_attachments : true;
    $redirect = (isset($redirect)) ? $redirect : root_url($this->page->url)."/%s";
?>
    <?=open_form(array('onsubmit'=>'return ticket_validate_form()'))?>
        <input type="hidden" name="category_id" value="<?=$category->id?>" />
        <input type="hidden" name="ticket_submit" value="1" />
        <input type="hidden" name="redirect" value="<?=$redirect?>" />

        <p><?=$category->description?></p>

        <? if ($config->use_credits && $category->credits_required): ?>
            <blockquote>
                <strong>This ticket type costs <?=Phpr_Strings::word_form($category->credits_required, 'credit', true)?>.</strong>
                <? if ($this->customer): ?>You currently have <?=Phpr_Strings::word_form($this->customer->x_ahoysupport_credits, 'credit', true)?> at the moment.<? endif ?>
                You can <a href="<?=$credits_product->page_url('product')?>">buy more credits here</a>.
            </blockquote>
        <? endif ?>

        <? if (!$priority): ?>
            <div>
                <label for="title">Priority</label>
                <select id="ticket_priority" name="priority_id">
                    <option value=""></option>
                    <? foreach ($priorities as $priority): ?>
                        <option value="<?=$priority->id?>"><?=$priority->name?></option>
                    <? endforeach ?>
                </select>
            </div>
        <? endif ?>
        <? if (!$this->customer): ?>
        <div>
            <label for="name">Your name</label>
            <input id="name" value="" maxlength="255" name="name" type="text">
        </div>
        <div>
            <label for="email">Your email</label>
            <input id="email" value="" maxlength="255" name="email" type="text">
        </div>
        <? endif ?>
        <div>
            <label for="title">Ticket Subject</label>
            <input id="title" value="" maxlength="255" name="title" type="text">
        </div>
        <div>
            <label for="description">Message</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <? if ($show_attachments): ?>
            <div>
                <label>Attach files</label>
                <input name="files[]" type="file">
                <a href="javascript:;" onclick="ticket_attach_file(this)">Attach another file</a>
            </div>
        <? endif ?>
        <div>
            <? if (!$config->use_credits || $can_submit): ?>
                <input type="submit" name="submit" value="Submit ticket" />
            <? else: ?>
                <input type="submit" name="submit" value="Submit ticket" disabled="disabled" onclick="return false" />
                <p>Please <a href="<?=$credits_product->page_url('product')?>">purchase support credits</a> to continue</p>
            <? endif ?>
        </div>
    </form>
<? else: ?>
    <blockquote>Please select a ticket type</blockquote>
<? endif ?>