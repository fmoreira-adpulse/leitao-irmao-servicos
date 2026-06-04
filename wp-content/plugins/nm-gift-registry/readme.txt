=== NM Gift Registry and Wishlist ===
Contributors: nmerii
Tags: wishlist, gift registry, gift list, wedding gift registry, wedding, birthday, anniversary, crowdfunding, crowd funding, woocommerce, ecommerce
Requires at least: 5.6
Tested up to: 6.4.3
Requires PHP: 7.4
Stable tag: 4.13
License: Nmeri Media
License URI: https://nmerimedia.com/license

An advanced and highly customizable WOOCOMMERCE gift registry and wishlist plugin that allows you to create lists for any occasion.

== Description ==

NM Gift Registry and Wishlist for WOOCOMMERCE allows customers to create and add products to all kinds of gift registries and wishlists, from birthdays to weddings, anniversaries and other occasions. It has been built as a solid gift registry plugin but one which enhances the power of wishlists when used as such. Designed with customers in mind, It provides tools needed to help them create the perfect list, get their items bought and generate sales for the store.

= Free version features =

* Create a gift registry or wishlist.
* Allow guests to create and manage wishlists.
* Add event date, description, partner's details and other profile information to the gift registry or wishlist.
* Add shipping information to the gift registry or wishlist using WooCommerce's shipping fields to blend well with the shipping setup on your site.
* Add simple, variable or grouped products directly to the gift registry or wishlist without fuss.
* Set the quantity desired of products added to the gift registry or wishlist.
* Add products from multiple gift registries or wishlists to the same cart and even add the same products to the cart as normal items.
* Track gift registry or wishlist items in the cart individually all the way up to checkout and order.
* Wishlist cart widget.
* Wishlist search widget.
* Customize the appearance and position of the add to gift registry or wishlist button.
* Customize the wishlist items table, add or remove columns as necessary, sort columns in every way.
* Social sharing for the gift registry or wishlist.
* Set the permalink where customers can view their wishlists on the frontend as you like.
* Set the permalink where customers can manage their wishlists in the WooCommerce my-account area as you like.
* Advanced search form for searching gift registries or wishlists by title, name, email and other fields.
* Multiple shortcodes for displaying and customizing every single template used by the plugin including the add to wishlist button itself.
* WooCommerce-like template system allowing plugin templates to be overridden by copying them to your theme.
* WooCommerce-style notifications and add to cart functionality for the add to wishlist action.
* WooCommerce-like API for performing CRUD actions related to the wishlist or wishlist item on the fly.
* Ability to completely modify the frontend user interface for viewing and managing wishlists to match your custom theme.
* Ajaxified actions such as add to wishlist, add wishlist item to cart, form submissions and others.
* Multiple action and filter hooks for tweaking the plugin's functionality at important steps of its functionality.
* Translation ready.

= Pro version features =

* Ability for each customer to have multiple gift registries or wishlists.
* Add multiple wishlist items to the cart at once.
* WooCommerce-like emails configurable to be sent to custom recipients and the wishlist owner at various stages such as when a wishlist is created, fulfilled and deleted, and when a wishlist item is ordered, purchased and refunded.
* Featured and background images for each gift registry or wishlist with various display styles.
* Ability to send custom messages to the gift registry or wishlist owner from the checkout page.
* Messages inbox for customers to view messages sent to them on the checkout page from their account area. Configure sending messages to customers' email.
* Settings for customers to manage the visibility and other properties of their gift registry or wishlist on the frontend.
* Ability to exclude individual wishlists from search results.
* Ability to mark an item as favourite in the wishlist and sort items by their favourite status.
* Extra settings for customizing the add to wishlist button and action completely to your liking.
* Ability to customize wishlist templates simply with the click of buttons from the admin settings page.
* Extra setting for customizing plugin functionality.
* Ability to set separate shipping methods and rates for wishlist items and ability to ship wishlist items to the wishlist owner's address.
* Ability to hide or customize the wishlist owner's shipping address on the frontend when shipping to it.
* Ability to include/exclude products from being added to the wishlist.
* Ability to include/exclude product categories from being added to the wishlist.
* Allow wishlist owners to see details of who bought items for them.

== Installation ==

Install and activate NM Gift Registry and Wishlist like any other plugin, it works right out of the box. However it is recommended you go to the settings page to familiarize yourself with the default settings and update them if you wish. Also browse the documentation to see how the plugin works in detail.

== Frequently Asked Questions ==

= Can I use NM Gift Registry and Wishlist as a gift registry plugin only =

Yes. NM Gift Registry and Wishlist is a fully-fledged gift registry plugin. It does that out of the box.

= Can I use NM Gift Registry and Wishlist as a wishlist plugin only =

Of course, it is also meant for this. NM Gift Registry and Wishlist can be used as a gift registry or wishlist plugin, and everything in between.


== Screenshots ==

1. Customizable items table - add and remove columns, change column contents.
2. Identify items from multiple lists in cart, order and checkout.
3. Add simple, variable and grouped products to the list.
4. Display WooCommerce-like add-to-list notices.
5. Wishlist page appearance.
6. View overview information in the wishlist management page.
7. Add detailed profile information and customize visibility and required status of profile fields.
8. Add shipping address the WooCommerce way.
9. Administrators have Full management control over all lists.


== Upgrade Notice ==


== Changelog ==

(Full changelog available in changelog.txt file in plugin root directory)

= 4.13 =
* Fix - Improved compatibility with caching applications

= 4.12 =
* Fix - Improved compatibility with caching applications
* Tweak - Improved performance in saving wishlist and wishlist items.
* Tweak - Adjusted plugin redirection feature to work after user registration just like user login.
* Dev - Deprecated use of 'date_modified' column in wishlist items table.

= 4.11.2 =
* Fix - Improved compatibility with caching applications

= 4.11.1 =
* Fix - Modal and Toast components are made responsive on mobile devices.
- Tweak - Updated twitter icon to X.

= 4.11 =
* Fix - Page jump effect when modal or toast is removed from page.
* Fix - Incorrect display of select2 dropdown on profile shipping form.
* Fix - Bug preventing add to cart button for wishlist item from showing on product page.
* Tweak - Removed pagination and ordering for overridden templates.
* Tweak - Removed ability to change display mode for overridden wishlist items table template.
* Dev - Deprecated NMGR_Items_View class.
* Dev - Prevent woocommerce style overriding of account/wishlists.php template.
* Dev - Prevent woocommerce style overriding of account/items/item-actions-add_to_cart.php template.
* Dev - Prevent woocommerce style overriding of account/items/item-cost.php template.
* Dev - Prevent woocommerce style overriding of account/items/item-quantity.php template.
* Tweak - Gift Registry and Wishlist pages can be sub pages of parent pages.

= 4.10 =
* Tweak - Removed 'visibility' column from all wishlists table.
* Tweak - wishlist type title can only be 'wishlist' or 'gift-registry' by default. Removed other titles.
* Tweak - Fixed bug preventing wishlist items from being added to cart via http.

= 4.9.0 =
* Replaced bootstrap with JQuery UI.

= 4.8.0 =
* Removed email Templates
* Removed overrides tab in plugin settings
* Fix - Bug preventing checkout messages from being sent in email to wishlist owner.

= 4.7.0 =
* Fix - Email field on profile settings is made required by default on plugin installation.
* Fix - Messages module is enabled by default on plugin installation.
* Fix - Wishlist thumbnail on wishlist page image shows on center by default on plugin installation.
* Dev - Deprecated function nmgr_get_default_account_section_content().
* Dev - Deprecated shortcode [nmgr_profile].
* Dev - Deprecated shortcode [nmgr_items].
* Dev - Deprecated shortcode [nmgr_shipping].
* Dev - Deprecated shortcode [nmgr_images].
* Dev - Deprecated shortcode [nmgr_orders].
* Dev - Deprecated shortcode [nmgr_messages].
* Dev - Deprecated shortcode [nmgr_settings].
* Dev - Deprecated shortcode [nmgr_share].
* Dev - Deprecated shortcode [nmgr_account].
* Dev - Deprecated shortcode [nmgr_account_wishlist].
* Removed Template - add-to-wishlist/profile.php
* Removed Template - add-to-wishlist/shipping.php
* Removed Template - add-to-wishlist/select-wishlist.php
* Removed Template - form-search-wishlist.php
* Removed Template - cart.php
* Deprecated Template - single-nm_gift_registry.php
* Deprecated Template - archive-nm_gift_registry.php
* Deprecated Template - content-archive-nm_gift_registry.php
* Deprecated Template - content-single-nm_gift_registry.php
* Fix - Enqueued frontend scripts on all frontend to prevent missing scripts on some pages.

= 4.6.0 =
* Tweak - Wishlist item maximum desired quantity set to unlimited.
* Fix - Bug preventing some columns from being hidden in the wishlist items table settings
* Tweak - Wishlist messages always show even when order is not paid.
* Tweak - Added pagination to wishlist messages templates.
* Feature - Wordpress minimum version set to 4.9.0
* Removed Template - account/messages.php
* Removed Template - account/images.php
* Removed Template - account/shipping.php
* Removed Template - account/orders.php
* Removed Template - account/settings.php
* Removed Template - account/profile.php
* Removed Template - account/items.php
* Removed Template - account/sharing.php
* Removed Template - account/wishlists.php
* Removed Template - account/call-to-action-no-wishlist.php
* Removed Template - account/items/item-thumbnail.php
* Removed Template - account/items/item-title.php
* Removed Template - account/items/item-cost.php
* Removed Template - account/items/item-quantity.php
* Removed Template - account/items/item-purchased-quantity.php
* Removed Template - account/items/item-favourite.php
* Removed Template - account/items/item-total_cost.php
* Removed Template - account/items/item-actions-add_to_cart.php
* Removed Template - account/items/item-actions-edit-delete.php
* Removed Template - account/items/items-actions.php
* Removed Template - account/items/items-total_cost.php
* Changed Template - add-to-wishlist/select-wishlist.php

= 4.5.1 =
* Tweak - Enqueued frontend scripts and styles on front page.
* Tweak - Refresh wishlist item row and wishlist table totals when item desired quantity is updated.
* Fix - Bug preventing wishlist item desired quantity from being updated.

= 4.5.0 =
* Feature - Added compatibility with woocommerce custom order tables.
* Tweak - Default order status for manually created orders is processing instead of completed.
* Feature - Added support for webp files for wishlist images.

= 4.4.0 =
* Dev - Changed database structure for wishlist tables.
* Dev - Replaced '_date_fulfilled' wishlist meta key with '_nmgr_fulfilled'
* Dev - Moved item meta properties from nmgr_itemmeta table to nmgr_wishlist_items table.
* Dev - Removed datatables.js jquery plugin.
* Dev - removed 'nmgr_guest_wishlist_expiry_days' filter.
* Dev - wishlist item quantity reference data no longer used.
* Tweak - Guest wishlists no longer expire.
* Tweak - Improved add to wishlist process
* Dev - Removed action 'nmgr_add_to_wishlist_option_row_start'.
* Dev - Removed action 'nmgr_add_to_wishlist_option_row_end'.
* Feature - Added ability to dequeue plugin bootstrap scripts to prevent conflicts.

= 4.3.7 =
* Fix -  Undefined index: add_to_cart_button in admin when showing wishlist items.
* Fix - Duplicate orders shown on orders account section when two wishlist items are bought in one order.

= 4.3.6 =
* Fix - Bug preventing background image from saving in admin.
* Fix - Fatal error caused by caching items view data before item is read.

= 4.3.5 =
* Fix - When updating wishlist item purchased quantity manually, the order item quantity should be the difference between the original wishlist item quantity and the updated wishlist item quantity rather than being the total wishlist item quantity.
* Fix - Show post edit link in admin for variable products in wishlists.

= 4.3.4 =
* Fix - Properly set up wishlist item object in wishlist class when getting wishlist purchased amount.

= 4.3.3 =
* Fix - Properly select gift registry or wishlist profile template when creating a new wishlist with post-new.php in admin.
* Fix - Fatal error when getting wishlist item part template without a set wishlist item.

= 4.3.2 =
* Fix - Show wishlist order item meta data in packing slips and pdf invoices.

= 4.3.1 =
* Fix - Double emails sent to customer and admin when new wishlist is created.
* Fix - Inability to properly read wishlist object after CRUD operations.

= 4.3.0 =
* Improvement - Wishlist page loading speed
* Feature - Added pagination to wishlist orders table.
* Tweak - Removed legacy gift registry woocommerce my-account page.

= 4.2.2 =
* Dev - Improved deactivation of incompatible plugins.

= 4.2.1 =
* Fix - Bug preventing wishlist items from being added to cart

= 4.2.0 =
* Fix - Bug preventing items table from toggling between list and grid modes.
* Feature - Added pagination to wishlist items table.
* Improvement - Efficiency of background tasks.

= 4.1.0 =
* Dev - Added compatibility with php 8.2
* Tweak - Removed setting to add products to wishlist via http.

= 4.0.1 =
* Fix - Bug preventing plugin from automatically updating on plugins screen.

= 4.0.0 =
* Feature - Create order when updating item purchased quantity.
* Feature - Archive wishlist item when order is set to completed.
* Tweak - Allow plugin to be used as a wishlist plugin alone or as a gift registry plugin alone, or both.
* Tweak - Only admin can archive and unarchive wishlist items.
* Tweak - Removed some plugin settings.
* Tweak - Changed placement of some plugin settings.
* Tweak - Removed page for managing wishlists.
* Tweak - Removed page for viewing wishlist archives.
* Tweak - Removed 'overview' account section.
* Dev - Replaced plugin option 'wishlist_single_page_id' with 'page_id'.
