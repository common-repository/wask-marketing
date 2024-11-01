<?php 
	if($_POST["submit"]=="Activate"){

		if(!isset($_POST["non"]) || !wp_verify_nonce($_POST["non"], "non")){
			exit;
		}
		else{
			$key = sanitize_text_field($_POST['key']);
			update_option("wask", $key, true);
			echo "<div class='updated'><p><b>Key Saved Successfully</b></p></div>";
		}
	}

	$activation = false;
	$secret_key = get_option("wask");

	if($secret_key != ""){
		$activation = true;

		$response = wp_remote_get(constant("wask_api")."facebook/account", [
			"headers" => [
				"Secret-Key" => $secret_key
			]
		]);

		$facebook_act = json_decode(wp_remote_retrieve_body($response));

		if(isset($facebook_act->error)){
			echo "<div class='error'><p><b>".$facebook_act->error->message."</b></p></div>";

			if($facebook_act->error->code == 401 || $facebook_act->error->code == 403)
				$activation = false;
		}
	}
?>