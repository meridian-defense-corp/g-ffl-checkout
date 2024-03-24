# Meridian Defense g-ffl-checkout Plugin Customizations

This is a fork of the g-ffl-checkout plugin public repo https://github.com/garidium/g-ffl-checkout heavily modified for use with the MDC checkout flow.

## Major updates

The original plugin comingled FFL shipping addresses with the default Woocommerce shipping addresses, which prevented the ability to define dual shipping addresses, one for non-firearms e.g. apparel, and the other for the shipping address to a licensed FFL dealer.

The plugin was modified to abstract away all FFL-related data, including desination shipping address, email and other contact info as order meta. To achieve that, all FFL order data has been updated to be stored as order meta. This means that any custom fields added to Woocommerce checkout have been moved from the 'shipping' custom fields area to the 'order' section. This allows us to disable shipping when the order only contains one or more firearms, since the only shipping information required is the FFL dealer's address.

## Plugin updates

Periodically check the vendor repo, or better, watch the repo for updates. When an update becomes available, view the changelog or a diff, and then merge in the updates to our forked repo as necessary.