<?php
require_once __DIR__ . "/../models/settings_admin.php";

class SettingsController
{
    public function detail($dbh, $db_functions, $humo_option)
    {
        $settingsModel = new SettingsModel($dbh);
        $settings['menu_tab'] = $settingsModel->get_menu_tab();
        $settings['time_lang'] = $settingsModel->get_timeline_language($humo_option);
        $settingsModel->save_settings($dbh, $db_functions, $humo_option, $settings);

        // *** Use a seperate controller for each tab ***
        if ($settings['menu_tab'] == 'settings_homepage') {
            require_once __DIR__ . "/../models/settings_homepage.php";
            $settings_homepageModel = new SettingsHomepageModel($dbh);
            $settings_homepageModel -> reset_modules($dbh);
            $settings_homepageModel -> save_settings_modules($dbh);
            $settings_homepageModel -> order_modules($dbh);

            $settings_homepageModel -> save_settings_favorites($dbh);
        }

        return $settings;
    }
}
