# wp-theme-init

#### Version: 2.5.1

A common baseline of repeated functions, filters and actions used across our WordPress themes.

[![Packagist](https://badgen.net/packagist/v/ideasonpurpose/wp-theme-init)](https://packagist.org/packages/ideasonpurpose/wp-theme-init)
[![codecov](https://codecov.io/gh/ideasonpurpose/wp-theme-init/branch/master/graph/badge.svg)](https://codecov.io/gh/ideasonpurpose/wp-theme-init)
[![Coverage Status](https://coveralls.io/repos/github/ideasonpurpose/wp-theme-init/badge.svg)](https://coveralls.io/github/ideasonpurpose/wp-theme-init)
[![Maintainability](https://api.codeclimate.com/v1/badges/38a14503add2806a99bd/maintainability)](https://codeclimate.com/github/ideasonpurpose/wp-theme-init/maintainability)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

### Some of what's included:

- **Webpack dependency manifest asset loading**<br>
  Uses the [Dependency Manifest Plugin](https://github.com/ideasonpurpose/docker-build/blob/master/lib/DependencyManifestPlugin.js) from [ideasonpurpose/docker-build](https://github.com/ideasonpurpose/docker-build).

- **Miscellaneous Fixes and Cleanup**

  - Add a design credit to the WordPress dashboard.
  - "Howdy" is removed from the admin menu bar.
  - Lots of junk is removed from wp_head. Many optimizations come from the [Soil plugin](https://roots.io/plugins/soil/).
  - WordPress will attempt to trigger a [Browsersync]() reload from the `save_post` hook.

- **ShowIncludes**
  A div showing all included theme files will be appended to the page when `WP_DEBUG` is true. To disable it, initialize the class with an array containing: `['showIncludes' => false]`

- **Global Comments Disable**
  Comments and Trackbacks are completely disabled. To re-enable comments, initialize the ThemeInit class with an array containing: `['enableComments' => true]`

- **Admin Separators**
  Admin Separators have been moved to their own library: [wp-admin-separators](https://github.com/ideasonpurpose/wp-admin-separators)

- **Media**
  Several media related features will be enabled:

  - The JPEG Compression value can be set by defining a `JPEG_QUALITY` constant before invoking `ThemeInit()`. Numeric values will be clamped between 0-100 then passed to the [WordPress `jpeg_quality` filter](https://developer.wordpress.org/reference/hooks/jpeg_quality/).
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

## Development

Run the PHPUnit test suite with: `npm run test`
