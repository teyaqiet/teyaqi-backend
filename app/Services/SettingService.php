<?php


namespace App\Services;


use App\Models\Setting;
use Illuminate\Support\Facades\Cache;


class SettingService
{

    protected string $cacheKey = 'teyaqi.settings';



    /**
     * Get all settings
     */
    public function all()
    {

        return Cache::rememberForever(
            $this->cacheKey,
            function () {

                return Setting::all()
                    ->keyBy('key');

            }
        );

    }





    /**
     * Get single setting
     */
    public function get(
        string $key,
        mixed $default = null
    ) {


        $setting = $this->all()->get($key);



        if (!$setting) {
            return $default;
        }



        return $this->cast(
            $setting->value,
            $setting->type
        );

    }





    /**
     * Get setting definition from config
     */
    public function definition(string $key)
    {

        $groups = config('settings', []);



        foreach($groups as $group => $fields)
        {


            foreach($fields as $field)
            {


                if(
                    isset($field['key']) &&
                    $field['key'] === $key
                ){

                    // Add group automatically
                    $field['group'] = $group;


                    return $field;

                }


            }


        }



        return null;

    }







    /**
     * Save setting value
     */
    public function set(
        string $key,
        mixed $value
    )
    {


        $definition = $this->definition($key);



        $setting = Setting::firstOrNew([
            'key'=>$key
        ]);



        $setting->key = $key;



        $setting->value = $value;



        if($definition)
        {


            $setting->group =
                $definition['group'] ?? null;



            $setting->type =
                $this->databaseType(
                    $definition['type'] ?? 'text'
                );



            $setting->description =
                $definition['label'] ?? null;



            $setting->is_secret =
                ($definition['type'] ?? null) === 'secret';


        }



        $setting->save();



        $this->clearCache();



        return $setting;

    }








    /**
     * Convert config type to database type
     */
    protected function databaseType(string $type)
    {

        return match($type)
        {

            'number'
                => 'integer',


            'toggle'
                => 'boolean',


            default
                => 'string',

        };

    }







    /**
     * Get boolean setting
     */
    public function boolean(
        string $key,
        bool $default=false
    ){

        return (bool)$this->get(
            $key,
            $default
        );

    }








    /**
     * Get integer setting
     */
    public function integer(
        string $key,
        int $default=0
    ){

        return (int)$this->get(
            $key,
            $default
        );

    }







    /**
     * Cast database value
     */
    protected function cast(
        $value,
        $type
    ){


        return match($type)
        {


            'boolean'
                => filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN
                ),



            'integer'
                => (int)$value,



            'float'
                => (float)$value,



            'array',
            'json'
                => json_decode(
                    $value,
                    true
                ),



            default
                => $value,


        };


    }







    /**
     * Clear settings cache
     */
    public function clearCache()
    {

        Cache::forget(
            $this->cacheKey
        );

    }







    /**
     * Check setting exists
     */
    public function has(string $key): bool
    {

        return $this->all()
            ->has($key);

    }







    /**
     * Get settings by group
     */
    public function group(string $group)
    {

        return $this->all()
            ->filter(function($setting) use ($group){

                return $setting->group === $group;

            });

    }







    /**
     * Check secret field
     */
    public function isSecret(string $key): bool
    {

        $setting = $this->all()
            ->get($key);



        return $setting?->is_secret ?? false;

    }







    /**
     * Mask secret values
     */
    public function masked(string $key)
    {

        $setting = $this->all()
            ->get($key);



        if(!$setting){
            return null;
        }



        if(!$setting->is_secret){

            return $setting->value;

        }



        if(empty($setting->value)){

            return '';

        }



        return str_repeat(
            '•',
            8
        );

    }







    /**
     * Delete setting
     */
    public function forget(string $key)
    {

        Setting::where(
            'key',
            $key
        )->delete();



        $this->clearCache();

    }







    /**
     * Refresh cache
     */
    public function refresh()
    {

        $this->clearCache();


        return $this->all();

    }



}