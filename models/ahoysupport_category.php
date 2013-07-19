<?php

class AhoySupport_Category extends Db_ActiveRecord
{
    public $table_name = 'ahoysupport_categories';

    public $implement = 'Db_AutoFootprints';
    public $auto_footprints_visible = true;

    public $belongs_to = array(
        'auto_assign_user'=>array('class_name'=>'Users_User', 'foreign_key'=>'auto_assign_id')
    );

    public $has_and_belongs_to_many = array(
        'tickets'=>array('class_name'=>'AhoySupport_Ticket', 'join_table'=>'ahoysupport_ticket_categories', 'order'=>'ahoysupport_tickets.created_at desc'),
    );

    public $calculated_columns = array(
        'ticket_num'=>array('sql'=>"(select count(*) from ahoysupport_tickets, ahoysupport_ticket_categories where ahoysupport_ticket_categories.ahoysupport_category_id=ahoysupport_categories.id and ahoysupport_ticket_categories.ahoysupport_ticket_id=ahoysupport_tickets.id)", 'type'=>db_number),
    );

    public static function create()
    {
        return new self();
    }

    public function define_columns($context = null)
    {
        $config_obj = AhoySupport_Config::create();

        $this->define_column('name', 'Name')->order('asc')->validation()->fn('trim')->required("Please specify the category name.");
        $this->define_column('description', 'Description')->validation()->fn('trim');
        $this->define_column('ticket_num', 'Ticket Number');

        $credits_field = $this->define_column('credits_required', 'Credits Required')->validation()->fn('trim');
        if ($config_obj->use_credits)
            $credits_field->required();

        $this->define_column('code', 'API Code')->defaultInvisible()->validation()->fn('trim')->fn('mb_strtolower')->unique('Category with the specified  API code already exists.');

        $this->define_relation_column('auto_assign_user', 'auto_assign_user', 'Auto assign To', db_varchar, "concat(@firstName, ' ', @lastName)");
        $this->define_column('is_hidden', 'Hidden');
    }

    public function define_form_fields($context = null)
    {
        $config_obj = AhoySupport_Config::create();

        $this->add_form_field('name', 'left');

        if ($config_obj->use_credits)
            $this->add_form_field('credits_required', 'right');

        $this->add_form_field('description')->renderAs(frm_textarea)->size('small');
        $this->add_form_field('code')->comment('You can use the API Code for accessing the category in the API calls.', 'above');


        $this->add_form_field('is_hidden', 'left')->comment('Do not show this category on the front-end');
        $this->add_form_field('auto_assign_user', 'right')->comment('The user to be automatically assigned to tickets in this category', 'above')->emptyOption('<nobody>')->previewNoRelation();
    }

    public function get_auto_assign_user_options($key_value = -1)
    {
        if ($key_value == -1)
        {
            $users = AhoySupport_Helper::list_support_users();
            $result = array();
            foreach ($users as $user)
                $result[$user->id] = $user->name;

            return $result;
        }
        
        $user = Users_User::create()->find($key_value);
        if ($user)
            return $user->name;
    }

    // Events
    //

    public function before_delete($id=null)
    {
        if ($this->ticket_num)
            throw new Phpr_ApplicationException('This category cannot be deleted because it contains '.$this->ticket_num.' tickets(s).');
    }
}

