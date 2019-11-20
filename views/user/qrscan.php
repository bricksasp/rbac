<img class="qrcode" src="">

<p class="msg"></p>

<script>
<?php $this->beginBlock('js_end') ?>  
　$(document).ready(function(){
	var ws = new WebSocket("ws://127.0.0.1:8282?common=qrscan");
	ws.onopen = function(){
		setInterval(show,30000); //心跳
	}

	function show(){
		ws.send('ping');
	}	

	ws.onConnect = function(e){

	}
	ws.onmessage = function(e){
		let res = JSON.parse(e.data)
		console.log(res)
		if (!$.isEmptyObject(res.data.client_id)) {
			// type 1前台 2后台 调用
			$(".qrcode").attr("src", "http://localhost:8080/user/qrscan?client_id=" + res.data.client_id + "&type=2")
		}else{
			if (res.controller == "user" && res.action == "qrlogin" ) {
				if (res.data == "qrlogin_scan") {
					// $(".msg").text("扫码成功")
					$(".msg").text(res.message)
				}
				if (!$.isEmptyObject(res.data.token)) {
					// $(".msg").text("登录成功跳转")
					$(".msg").text(res.data.token)
				}
			}
		}
	}
	ws.onclose = function(e){
	 console.log(e);
	}
});
<?php $this->endBlock(); ?>  
</script>  
<?php $this->registerJs($this->blocks['js_end'],\yii\web\View::POS_LOAD);?>  