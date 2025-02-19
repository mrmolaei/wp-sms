<?php

namespace WP_SMS;

use WP_SMS\Admin\Helper;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * WP SMS version class
 *
 * @category   class
 * @package    WP_SMS
 */
class Version
{
    public function __construct()
    {
        // Check pro pack is enabled
        if (self::pro_is_active()) {
            // Check what version of WP-Pro using? if not new version, َShow the notice in admin area
            if (defined('WP_SMS_PRO_VERSION') and version_compare(WP_SMS_PRO_VERSION, "2.4.2", "<=")) {
                add_action('admin_notices', array($this, 'version_notice'));
            }

            // Check license key.
            if (Option::getOption('license_wp-sms-pro_status') == false) {
                add_action('admin_notices', array($this, 'license_notice'));
            }

            /**
             * Move license and license status from old setting to new setting.
             */
            $option    = Option::getOptions();
            $optionPro = Option::getOptions(true);

            if (isset($optionPro['license_key']) && $optionPro['license_key'] && isset($optionPro['license_key_status']) && $optionPro['license_key_status'] == 'yes') {
                $option['license_wp-sms-pro_key']    = $optionPro['license_key'];
                $option['license_wp-sms-pro_status'] = true;
                update_option('wpsms_settings', $option);

                unset($optionPro['license_key']);
                unset($optionPro['license_key_status']);
                update_option('wps_pp_settings', $optionPro);
            }

        } else {

            if (is_admin() && isset($_GET['page']) and $_GET['page'] == 'wp-sms-pro') {
                add_action('admin_notices', array($this, 'license_notice'));
            }

            add_filter('plugin_row_meta', array($this, 'pro_meta_links'), 10, 2);
            add_filter('wpsms_gateway_list', array(self::class, 'addProGateways'));
        }
    }

    /**
     * Check pro pack is enabled
     * @return bool
     */
    public static function pro_is_active($pluginSlug = 'wp-sms-pro/wp-sms-pro.php')
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (is_plugin_active($pluginSlug)) {
            return true;
        }
    }

    /**
     * Check pro pack is exists
     * @return bool
     */
    private function pro_is_exists()
    {
        if (file_exists(WP_PLUGIN_DIR . '/wp-sms-pro/wp-sms-pro.php')) {
            return true;
        }
    }

    /**
     * @param $links
     * @param $file
     *
     * @return array
     */
    public function pro_meta_links($links, $file)
    {
        if ($file == 'wp-sms/wp-sms.php') {
            $links[] = sprintf(__('<b><a href="%s" target="_blank" class="wpsms-plugin-meta-link wp-sms-pro" title="Get professional package!">Get professional package!</a></b>', 'wp-sms'), WP_SMS_SITE . '/purchase');
        }

        return $links;
    }

    /**
     * @return string
     * @internal param $string
     */
    public function pro_setting_title()
    {
        echo sprintf(__('<p>WP-SMS-Pro v%s</p>', 'wp-sms'), WP_SMS_PRO_VERSION);
    }

    /**
     * @param $gateways
     *
     * @return mixed
     */
    public static function addProGateways($gateways)
    {
        // Set pro gateways to load in the list as Global.
        $gateways = array_merge_recursive(Gateway::$proGateways, $gateways);

        // Fix the first array key value
        unset($gateways['']);
        $gateways = array_merge(array('' => array('default' => __('Please select your gateway', 'wp-sms'))), $gateways);

        // Sort gateways by countries and merge them with global at first
        $gateways_countries = array_splice($gateways, 2);
        ksort($gateways_countries);

        $gateways = array_replace_recursive($gateways, $gateways_countries);

        return $gateways;
    }

    /**
     * Version notice
     */
    public function version_notice()
    {
        Helper::notice(sprintf(__('The <a href="%s" target="_blank">WP-SMS-Pro</a> is out of date and not compatible with new version of WP-SMS, Please update the plugin to the <a href="%s" target="_blank">latest version</a>.', 'wp-sms'), WP_SMS_SITE, 'https://wp-sms-pro.com/my-account/downloads/'), 'error');
    }

    /**
     * License notice
     */
    public function license_notice()
    {
        $url = admin_url('admin.php?page=wp-sms-settings&tab=licenses');
        Helper::notice(sprintf(__('Please <a href="%s">enter and activate</a> your license key for WP-SMS Pro to enable automatic updates.', 'wp-sms'), $url), 'error');
    }
}

new Version();