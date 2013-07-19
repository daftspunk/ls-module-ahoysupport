<?
    $ticket_category = AhoySupport_Category::create()->find_by_code('general');
?>

<?= flash_message() ?>

<? if ($this->request_param(0)=="sent"): ?>
    <h3>Message sent!</h3>
    <p>Thanks for contacting us, you will have a reply within 24 hours.</p>
<? else: ?>
    <h3>Contact us</h3>
    <?=$this->render_partial('support:ticket_form', array(
        'category'=>$ticket_category,
        'priority'=>1,
        'show_attachments'=>false,
        'redirect'=>root_url($this->page->url.'/sent')
    ))?>
<? endif?>