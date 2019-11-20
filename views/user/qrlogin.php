<?=$client_id ?>
<div>
	<div>
		<button class="confirm">确定</button>
	</div>
	<div>
		<button class="cancel">取消</button>
	</div>
</div>
<p class="msg"></p>
<script>
<?php $this->beginBlock('js_end') ?>  
　$(document).ready(function(){
	var token = "GRBSzx637-uIMYc8mJiJtNKkg-PMHCg5"
	$(".confirm").click(function (e) {
		$.ajax({
            url: "/user/qrlogin?client_id=<?=$client_id ?>",
            type: 'put',
            dataType: 'json',
            //data: JSON.stringify({data:{status: "start"}}),
            data: {name: "xu", foo: 'bar'},
            cache: false,
            headers: { 
                "X-Token":token,
                // "Content-Type": "application/x-www-form-urlencoded"  //multipart/form-data;boundary=--xxxxxxx   application/json
            },                
            success: function(res){
                if (res.status === 200) {
                    $(".msg").text("ok")
                }else{
                    $(".msg").text("fail")
                }
            },
            error: function(e) {

            }
        });
	});
});
<?php $this->endBlock(); ?>  
</script>  
<?php $this->registerJs($this->blocks['js_end'],\yii\web\View::POS_LOAD);?>  