# WooCommerce-Product-Migrator
Uses WooCommerce API to import products from another WooCommerce install

# Usage

Install this as a normal plugin and navigate to the panel named "WooCommerce Product Migrator"

Enter the api key and secret for the remote site - this key only needs read access.
Enter the url for the remote site
Set up an API key for the site you're importing to - this needs to have read/write capabilities

Save, reload, and click start.

This will grab 10 products at a time, compare them to existing products (by title), and either update the matching product or create a new one.

There are occasionally issues where we get an error from the JSON api for no apparent reason, but if you rerun the migration, the product will get imported the second time.

The last updated dates are compared before performing an update, so if a product has been updated locally later than the last updated date on the remote site, the product will be skipped.
