<?php

namespace IdeasOnPurpose\ThemeInit\Admin;

/**
 * Fully disable the WordPress Customizer.
 */
class DisableCustomizer
{
    public function __construct()
    {
        add_filter('map_meta_cap', [$this, 'removeCapability'], 10, 2);
        add_action('load-customize.php', [$this, 'blockCustomizer']);
        add_action('admin_bar_menu', [$this, 'removeCustomizerFromAdminBar'], 9999);
    }

    /**
     * Remove the 'customize' capability from all users.
     *
     * @param array $caps The user's capabilities.
     * @param string $cap The capability being checked.
     * @return array The modified capabilities.
     */
    public function removeCapability($caps, $cap)
    {
        if ($cap === 'customize') {
            return ['do_not_allow'];
        }
        return $caps;
    }

    /**
     * Block access to the Customizer page.
     */
    public function blockCustomizer()
    {
        wp_die(
            __('The Customizer is disabled on this site.', 'text-domain'),
            __('Customizer Disabled', 'text-domain'),
            ['response' => 403],
        );
    }

    /**
     * Remove the Customizer link from the admin bar.
     *
     * @param \WP_Admin_Bar $wp_admin_bar The admin bar object.
     */
    public function removeCustomizerFromAdminBar($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('customize');
    }
}
