<?php
namespace IdeasOnPurpose\ThemeInit\Plugins;

class TwoFactor
{
    public function __construct($enforce_mfa = true)
    {
        // Set the emailed codes to industry standard 6 characters.
        add_filter('two_factor_email_token_length', fn(): int => 6);
        add_filter('two_factor_enabled_providers_for_user', [$this, 'disableForDev'], 999);

        if ($enforce_mfa) {
            add_filter('two_factor_enabled_providers_for_user', [$this, 'enforceEmail']);
        }
    }

    /**
     * Enforce email MFA on all users with access to the admin dashboard
     * @link https://wordpress.org/plugins/two-factor/
     *
     * Enforcement via filter discussed here:
     * @link https://github.com/WordPress/two-factor/issues/307
     */
    public function enforceEmail($providers)
    {
        return !empty($providers) ? $providers : ['Two_Factor_Email'];
    }

    /**
     * Disable MFA for local/development environments
     * Return an empty array for development environments, otherwise return $enabled_providers
     */
    public function disableForDev($enabled_providers)
    {
        if (wp_get_environment_type() === 'development') {
            error_log('Two Factor MFA disabled for development environment.');
            // Return only the Dummy Provider class name
            return ['Two_Factor_Dummy'];
        }
        return $enabled_providers;
    }
}
