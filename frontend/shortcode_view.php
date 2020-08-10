<?php
?>
<div class='nasa-gallery'>
<?php
	foreach ( $gallery_items as $photo ) {
		echo "<div class='slick-image'><img src='" . get_the_post_thumbnail_url($photo->ID, $atts['size']) . "'> </div>";
	}
?>
</div>