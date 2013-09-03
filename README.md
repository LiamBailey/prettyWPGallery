Wordpress Plugin prettyWPGallery v1.0.0
=======================================

Taking over the Wordpress Gallery shortcode with a prettyPhoto gallery showing only the first image as an anchor 
to open the modal gallery.

Version 1.0.0

prettyGallery.php is the file where everything happens. It removes the Wordpress gallery shortcode hook and hooks its own 
function to it. When activated any gallery shortcode invoked with the attribute link='file' will show only the first image
in the size specified in the shortcode, with hidden links to the remaining images. The modal images are all set to 'full'
so the modal expands to full size. Apart from that the gallery shortcode works as normal.

At present the plugin is very simple and probably a little untidy. It was written for a client and I had misunderstood
the full requirement so it was changed in progress. Over the coming months I will tidy it up and start adding new features
probably starting with other prettyPhoto themes and such like. I have decided not to have a settings page, instead all options
will be specified using attributes in the shortcode.

To use the plugin simply install, activate and use the gallery shortcode like so: [gallery link='file], with any other
parameters you need. Hope you like it.
