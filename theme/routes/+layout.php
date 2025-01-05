<!DOCTYPE html>
<html lang="<?php echo $page_lang; ?>">
	<?php the_head(); ?>
	<body<?php echo $page_attrs; ?>>
	<div class="site">
		<?php _item('site-head'); ?>
		<main class="site-main">
			<?php echo $page_html; ?>	
		</main>
		<?php _item('site-foot'); ?>
	</div>
	</body>
	<?php the_foot(); ?>
</html>