# wp-theme-init

#### Version: 2.13.0

A common baseline of repeated functions, filters and actions used across our WordPress themes.

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

- **Miscellaneous Fixes and Cleanup**

  - Add a design credit to the WordPress dashboard.
  - "Howdy" is removed from the admin menu bar.
  - Lots of junk is removed from wp_head. Many optimizations come from the [Soil plugin](https://roots.io/plugins/soil/).
  - WordPress will attempt to trigger a webpack devServer reload from the `save_post` hook.
  - Set the [`DISALLOW_FILE_EDIT`][dfe] constant and displays an admin notice when the constant is explicitly set to false.

- **ShowIncludes**
  A div showing all included theme files will be appended to the page when `WP_DEBUG` is true. To disable it, initialize the class with an array containing: `['showIncludes' => false]`

- **Template Audit**  
   Adds a Template column to Pages admin and a summary table to the Appearance menu showing which templates have been assigned to pages.

- **Record Users' Last Login time**
  User's last successful login are recorded and added to the WordPress Admin User table.

- **Reset Metabox Order & Visibility**
  Adds buttons to the bottom of user profiles which will reset all metabox order and visibility from user_meta. 

- **Enable and limit WP_POST_REVISIONS**
  Revisions are set to 6, this overrides any constants set in wp-config.php.

- **Global Comments Disable**
  Comments and Trackbacks are completely disabled. To re-enable comments, initialize the ThemeInit class with an array containing: `['enableComments' => true]`

- **Remove jQuery Migrate**  (optional)
  Prevent jQuery Migrate from loading by removing it from the list of WordPress dependencies. To remove jquery-migrate,  initialize the ThemeInit class with an array containing: `['jQueryMigrate' => false]` 

- **Admin Separators**
  Admin Separators have been moved to their own library: [wp-admin-separators](https://github.com/ideasonpurpose/wp-admin-separators)

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

## WordPress Integration

Dependency manifest processing is designed to work with the WordPress [Dependency Extraction Webpack Plugin](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/). This omits a subset of script libraries included with WordPress, and outputs a PHP snippet for each entry point which includes a dependency list for enqueuing scripts.

The set of WordPress scripts which will be omitted is listed [here](https://github.com/WordPress/gutenberg/tree/trunk/packages/dependency-extraction-webpack-plugin#webpack) and includes `jquery`, `lodash`, `moment`, `react` and `react-dom` as well as all scripts from the `@wordpress` namespace.

## Development

Run the PHPUnit test suite with: `npm run test`

<!-- START IOP CREDIT BLURB -->

## &nbsp;

#### Brought to you by IOP

<a href="https://www.ideasonpurpose.com"><img src="https://raw.githubusercontent.com/ideasonpurpose/ideasonpurpose/master/IOP_monogram_circle_512x512_mint.png" height="44" align="top" alt="IOP Logo"></a><img src="https://raw.githubusercontent.com/ideasonpurpose/ideasonpurpose/master/spacer.png" align="middle" width="4" height="54"> This project is actively developed and used in production at <a href="https://www.ideasonpurpose.com">Ideas On Purpose</a>.

<!-- END IOP CREDIT BLURB -->

[dfe]: https://developer.wordpress.org/apis/wp-config-php/#disable-the-plugin-and-theme-file-editor
