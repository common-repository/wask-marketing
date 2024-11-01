<?php 
	$subscription = 0;
	
	if($activation){
		$response = wp_remote_get(constant("wask_api")."user", [
			"headers" => [
				"Secret-Key" => $secret_key
			]
		]);

		$user = json_decode(wp_remote_retrieve_body($response));

		if(isset($user->error)){
			echo "<div class='error'><p><b>".$user->error->message."</b></p></div>";
		}
		else if(isset($user->user->subscription)){
			$subscription = $user->user->subscription == "1" ? 1 : 0;
		}
	}
?>