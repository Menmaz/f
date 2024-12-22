<?php

namespace Backpack\Settings\database\seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * The settings to add.
     */

    protected $settings = [
        [
            'key'         => 'notifications',
            'name'        => 'Thông Báo',
            'description' => 'Thông Báo',
            'field'       => '{"name":"value","label":"Nội dung thông báo","type":"code"}',
            'value' => '',
            'active'      => 0,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->settings as $index => $setting) {
            $result = DB::table(config('backpack.settings.table_name'))->insert($setting);

            if (!$result) {
                $this->command->info("Insert failed at record $index.");

                return;
            }
        }

        $this->command->info('Inserted '.count($this->settings).' records.');
    }
}