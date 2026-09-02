<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;


class SettingsController extends Controller
{

    public function __construct(
        protected SettingService $settings
    ) {}



    /**
     * Settings page
     */
    public function index(Request $request)
    {

        $groups = array_keys(config('settings'));


        $activeGroup = $request->get(
            'group',
            'general'
        );


        if(!in_array($activeGroup, $groups)) {

            $activeGroup = 'general';

        }


        $settings = config(
            "settings.$activeGroup",
            []
        );


        return view(
            'admin.system.settings.index',
            compact(
                'groups',
                'activeGroup',
                'settings'
            )
        );

    }




    /**
     * Update settings
     */
    public function update(Request $request)
{

    $fields = config(
        "settings.".$request->group,
        []
    );


    foreach($fields as $field)
{

    $name = $field['name'];


    if($field['type'] === 'file')
    {

        if($request->hasFile($name))
        {

            $path = $request
                ->file($name)
                ->store(
                    'settings',
                    'public'
                );


            $this->settings->set(
                $field['key'],
                $path
            );

        }


        continue;
    }



    $value = $request->input(
        $name,
        $field['type'] === 'toggle'
        ? false
        : null
    );


    $this->settings->set(
        $field['key'],
        $value
    );



    }


    return back()->with(
        'success',
        'Settings updated successfully'
    );

}

}