<?php

namespace IdeasOnPurpose\ThemeInit\Plugins;

class TwoFactor
{
    public function __construct($enforce_mfa = true)
    {
        // Set the emailed codes to industry standard 6 characters.
        add_filter('two_factor_email_token_length', fn(): int => 6);
        add_filter('two_factor_enabled_providers_for_user', [$this, 'disableForNonProduction'], 500);

        // Customize MFA Token emails sending name and subject line
        add_filter('two_factor_token_email_message', [$this, 'tokenEmail'], 700, 3);

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
     * Disable MFA for non-production environments
     * Return the dummy provider for non-production environments, otherwise return $enabled_providers
     */
    public function disableForNonProduction($enabled_providers)
    {
        if (wp_get_environment_type() !== 'production') {
            error_log('Two Factor MFA disabled for non-production environments.');
            error_log('Environment: ' . wp_get_environment_type());
            return ['Two_Factor_Dummy'];
        }
        return $enabled_providers;
    }

    /**
     * Set a custom email name for MFA Token emails
     * Registers native wp_mail filters to modify the from name, subject and to style
     * the message body.
     *
     * TODO: wp_mail filter will be unnecessary if/when these PRs are merged:
     * - https://github.com/WordPress/two-factor/pull/897
     * - [headers for email provider]
     */
    public function tokenEmail($message, $token, $user_id)
    {
        $name = apply_filters(
            'iop_two_factor_email_from_name',
            'WordPress - ' . wp_parse_url(home_url(), PHP_URL_HOST),
        );

        add_filter('wp_mail_from_name', fn() => $name, 500);
        add_filter('wp_mail_content_type', fn() => 'text/html');

        /**
         * Modify the token email subject.
         * This is registered from the Two Factor two_factor_token_email_message hook,
         * so it only affects token emails sent by Two Factor.
         *
         * @param array $args {
         *     @type string|string[] $to          Array or comma-separated list of email addresses to send message.
         *     @type string          $subject     Email subject.
         *     @type string          $message     Message contents.
         *     @type string|string[] $headers     Additional headers.
         *     @type string|string[] $attachments Files to attach.
         *     @type string|string[] $embeds      Files to embed.
         * }
         */
        add_filter('wp_mail', function ($args) use ($token) {
            $args['subject'] = "$token is your login code";
            return $args;
        });

        $styled_token = sprintf(
            '<strong style="font-size:42px;font-weight:bold;font-family:monospace,sans-serif;"><span style="padding-right:12px;">%s</span><span>%s</span></strong>',
            substr($token, 0, 3),
            substr($token, 3),
        );
        $new_message = str_replace($token, $styled_token, $message);
        return nl2br($new_message);
    }
}
