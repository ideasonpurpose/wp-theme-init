# wp-theme-init

#### Version: 2.17.4

A common baseline of repeated functions, filters and actions used across our WordPress themes. This may not be much use if you're not working at [Ideas On Purpose](https://www.ideasonpurpose.com), but the code is here so the solutions themselves can be shared.

[![Packagist](https://badgen.net/packagist/v/ideasonpurpose/wp-theme-init)](https://packagist.org/packages/ideasonpurpose/wp-theme-init)
[![codecov](https://codecov.io/gh/ideasonpurpose/wp-theme-init/branch/master/graph/badge.svg)](https://codecov.io/gh/ideasonpurpose/wp-theme-init)
[![Coverage Status](https://coveralls.io/repos/github/ideasonpurpose/wp-theme-init/badge.svg)](https://coveralls.io/github/ideasonpurpose/wp-theme-init)
[![Maintainability](https://api.codeclimate.com/v1/badges/38a14503add2806a99bd/maintainability)](https://codeclimate.com/github/ideasonpurpose/wp-theme-init/maintainability)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

### Some of what's included:

- **Webpack dependency manifest asset loading**<br>
  Uses the [Dependency Manifest Plugin](https://github.com/ideasonpurpose/docker-build/blob/master/lib/DependencyManifestPlugin.js) from [ideasonpurpose/docker-build](https://github.com/ideasonpurpose/docker-build).

  Scripts will be enqueued using the stylesheet "slug" combined with their entrypoint name.

  For example, if the theme directory was `ldco`, the following manifest entry would be enqueued as `ldco-main`

  ```json
  {
    "main": {
      "files": {
        "main.js": "/wp-content/themes/ldco/dist/main-b4216f6b.js"
      }
    }
  }
  ```

  Editor assets are enqueued using the [appropriate hooks](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/#editor-content-scripts-and-styles); [`enqueue_block_editor_assets`](https://developer.wordpress.org/reference/hooks/enqueue_block_editor_assets/) for scripts and [`enqueue_block_assets`](https://developer.wordpress.org/reference/hooks/enqueue_block_assets/) for styles. All scripts are enqueued as ESM by adding the `type="module"` to included script tags.

- **Miscellaneous Fixes and Cleanup**
  - Add an i18n-ready design credit to the WordPress dashboard.
  - "Howdy" is removed from the admin menu bar.
  - IOP's i18n library
  - Lots of junk is removed from wp_head. Many optimizations originally came from Roots.io's retired [Soil plugin](https://roots.io/plugins/soil/).
  - WordPress will attempt to trigger a webpack devServer reload from the `save_post` hook.
  - Override the [`DISALLOW_FILE_EDIT`](https://developer.wordpress.org/apis/wp-config-php/#disable-the-plugin-and-theme-file-editor) constant and block access to the [Theme File Editor](https://wordpress.org/documentation/article/appearance-theme-file-editor-screen/).

- **Block Editor**<br>
  Disable [third-party block suggestions](https://developer.wordpress.org/block-editor/reference-guides/filters/editor-filters/#block-directory) and [ remote block patterns](https://developer.wordpress.org/block-editor/reference-guides/filters/editor-filters/#block-patterns).

- **ShowIncludes**<br>
  A div showing all included theme files will be appended to the page when `WP_DEBUG` is true. To disable it, initialize the class with an array containing: `['showIncludes' => false]`

- **Template Audit**<br>
  Adds a Template column to Pages admin and a summary table to the Appearance menu showing which templates have been assigned to pages.

- **Record Users' Last Login time**
  Users' last successful logins are recorded and added to the WordPress Admin User table.

- **Reset Metabox Order & Visibility**
  Adds buttons to the bottom of user profiles which will reset all metabox order and visibility from user_meta.

- **Enable and limit WP_POST_REVISIONS**
  Revisions are set to 6, this overrides any constants set in wp-config.php.

- **Global Comments Disable**
  Comments and Trackbacks are completely disabled. To re-enable comments, initialize the ThemeInit class with an array containing: `['enableComments' => true]`

- **Remove jQuery Migrate** (optional)
  Prevent jQuery Migrate from loading by removing it from the list of WordPress dependencies. To remove jquery-migrate, initialize the ThemeInit class with an array containing: `['jQueryMigrate' => false]`

- **Admin Separators**
  Admin Separators have been moved to their own library: [wp-admin-separators](https://github.com/ideasonpurpose/wp-admin-separators)

- **Remove Stale Login Cookies**
  Repeatedly spinning up local dev environments often bloats **localhost** cookies with stale `wordpress_logged_in` entries. Once these accumulate, the local server will eventually fail with a bad request and the error message _"Your browser sent a request that this server could not understand. Size of a request header field exceeds server limit."_ The cookie can be removed using the browser's dev tools, if the error is recognized -- but it often isn't. Instead, wp-theme-init removes stale login cookies from the `wp_login` hook, preventing the issue. This only runs on development environments when WP_DEBUG is set.

- **Media**
  Several media related features will be enabled:
  - The JPEG Compression value can be set by defining a `JPEG_QUALITY` constant before invoking `ThemeInit()`. Numeric values will be clamped between 0-100 then passed to the [WordPress `jpeg_quality` filter](https://developer.wordpress.org/reference/hooks/jpeg_quality/). `JPEG_QUALITY` defaults to `82`.
  - A high-quality Lanczos scaling filter will be used for scaling images.
  - All image uploads will be re-compressed if their filesize can be reduced by at least 75%.

- **Search**
  A few improvements to native WordPress search
  - Short-circuit search queries <2 characters long
  - Redirect query searches to `/search/`
  - Workaround leading-dot search failures

- **SEO Framework Tweaks**
  We apply several tweaks to the excellent [The SEO Framework plugin](https://theseoframework.com/):
  - Hide the author's name
  - Move the metabox to the bottom of admin pages
  - Show the default image from the first post in archives

- **Customize the WordPress Login Screen** (wp-login.php)<br>
  Customizes the WordPress login page by adding a client logo, byline, and footer. The logo can be a string or a callable function (e.g., using wp-svg-lib). All arguments are optional, but the first (`$siteLogo`) is required to display a logo.

  Instantiate the class like this:

  ```php
  $siteLogo = fn() => $SVG->siteLogo(300, 'auto');
  $byline = 'A WordPress site by <a href="https://www.ideasonpurpose.com">Ideas On Purpose</a>';
  $footer = fn() => sprintf(
      '<a href="https://www.ideasonpurpose.com">%s</a>',
      $SVG->siteLogo(32, 'auto')
  );

  new ThemeInit\Admin\CustomLoginScreen($siteLogo, $byline, $footer);

  // or with just the logo
  new ThemeInit\Admin\CustomLoginScreen($siteLogo);

  // or with no arguments (no logo will be shown)
  new ThemeInit\Admin\CustomLoginScreen();
  ```

Logo colors can be customized by setting any of these three CSS properties in the **admin.css** scope:

```css

:root {
  --iop-login-logo-color: seagreen;
  --iop-login-footer-logo-color: slategray;
  --iop-login-footer-logo-hover: steelblue;
}
```

## WordPress Integration

Dependency manifest processing is designed to work with the WordPress [Dependency Extraction Webpack Plugin](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/). This omits a subset of script libraries included with WordPress, and outputs a PHP snippet for each entry point which includes a dependency list for enqueuing scripts.

The set of WordPress scripts which will be omitted is listed [here](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin#webpack) and includes `jquery`, `lodash`, `moment`, `react` and `react-dom` as well as all scripts from the `@wordpress` namespace.

## Development

Run the PHPUnit test suite with: `npm run test`.

To work on this project while installed in another project, add `repositories` to your **composer.json** file, then work on the checked-out repository in vendors.

```json
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/ideasonpurpose/wp-theme-init"
    }
  ]
```

<!-- START IOP CREDIT BLURB -->

## &nbsp;

#### Brought to you by IOP

<a href="https://www.ideasonpurpose.com"><img src="https://raw.githubusercontent.com/ideasonpurpose/ideasonpurpose/master/iop-logo-white-on-black-88px.png" height="44" align="top" alt="IOP Logo"></a><img src="https://raw.githubusercontent.com/ideasonpurpose/ideasonpurpose/master/spacer.png" align="middle" width="4" height="54"> This project is actively developed and used in production at <a href="https://www.ideasonpurpose.com">Ideas On Purpose</a>.

<!-- END IOP CREDIT BLURB -->
