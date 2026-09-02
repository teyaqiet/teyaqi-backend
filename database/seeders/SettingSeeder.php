<?php


namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Setting;


class SettingSeeder extends Seeder
{

    public function run(): void
    {

        $groups = config('settings');


        foreach($groups as $group => $fields)
        {

            foreach($fields as $field)
            {

                Setting::updateOrCreate(

                    [
                        'key' => $field['key']
                    ],

                    [

                        'key' => $field['key'],


                        'value' => $this->defaultValue(
                            $field
                        ),


                        'type' => $this->databaseType(
                            $field['type']
                        ),


                        'group' => $group,


                        'description' =>
                            $field['label'] ?? null,


                        'is_secret' =>
                            ($field['type'] ?? null) === 'secret',

                    ]

                );

            }

        }

    }



    private function defaultValue($field)
    {

        return match($field['type'] ?? 'text')
        {

            'toggle' => '0',

            'number' => '0',

            'file' => '',

            'select' =>
                $field['options'][0]['value'] ?? '',

            'secret' => '',

            default => '',

        };

    }





    private function databaseType($type)
    {

        return match($type)
        {

            'number' => 'integer',

            'toggle' => 'boolean',

            default => 'string',

        };

    }


}