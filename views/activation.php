<section id="activation">
	<h2>Activate Plugin</h2>
	<br>
	<form method="post" actions="">
		<label>Key: </label>
		<input type="text" name="key" required>
		<?php wp_nonce_field('non','non'); ?>
		<input type="submit" name="submit" id="submit" class="button button-primary" value="Activate">
	</form>

	<br>
	<input type="button" style="margin-left: 36px;width: 320px;" class="button button-primary" onclick="javascript:window.open('https://app.wask.co/key-generator')" value="Generate Activation Key">
</section>