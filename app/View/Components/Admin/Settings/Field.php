<?php

namespace App\View\Components\Admin\Settings;

use Illuminate\View\Component;

class Field extends Component
{

    public array $field;


    public function __construct(array $field)
    {
        $this->field = $field;
    }


    public function render()
    {
        return view('admin.components.settings.field');
    }

}