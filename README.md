# wp-theme-init

#### Version: 1.4.0

A common baseline of repeated functions, filters and actions used across our WordPress themes.

### Some of what's included:

- **Miscellaneous Fixes and Cleanup**

  - Add a design credit to the WordPress dashboard.
  - "Howdy" is removed from the admin menu bar.
  - Lots of junk is removed from wp_head. Many optimizations come from the [Soil plugin](https://roots.io/plugins/soil/).
  - WordPress will attempt to trigger a [Browsersync]() reload from the `save_post` hook.

- **ShowIncludes**
  A div showing all included theme files will be appended to the page when `WP_DEBUG` is true. To disable it, initialize the class with an array containing: `['showIncludes' => false]`

- **Global Comments Disable**
  Comments and Trackbacks are completely disabled. To re-enable comments, initialize the ThemeInit class with an array containing: `['enableComments' => true]`

- **Admin\Separators**
  Quickly add separators to the WordPress admin dashboard's left sidebar. Initialize the class with a list of numbers representing the index locations where separators should appear in the menu. Arguments can be an array or multiple arguments.

  ```
  // Add separators
  new Admin\Separators(5, 7);

  // or use an array
  new Admin\Separators([5, 7]);
  ```

* **SEO Framework Tweaks**
  We apply several tweaks to the excellent [The SEO Framework plugin](https://theseoframework.com/):
  - Hide the author's name
  - Move the metabox to the bottom of admin pages
  - Show the default image from the first post in archives
