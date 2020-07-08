<?php

?>
<script type="text/javascript">
    var base_url = window.location.origin,
        host = window.location.host,
        pathArray = window.location.pathname,
        baseURL;
    if(host === 'localhost') baseURL = base_url+'/'+pathArray.split('/')[1];
    else baseURL = base_url;
    
    $(document).ready(function () {
        $("#user_add").submit(function () {
            var success_flag = 0,
                username = $('#username').val();
            if(username === ""){
                $('#msg').html('Please enter username');
                return false;
            }else{
                $.ajax({
                    method: "POST",
                    url: "<?php echo $this->Url->build(["controller" => "users", "action" => "subscriber_mail"]); ?>",
                    data: {username: username},
                    async: false
                }).done(function (msg) {
                    if(msg === 'session-expired'){
                        alert('Your session expired. Please login again!');
                        window.location.href = baseURL;
                    }else if(msg === 'no-access'){
                        alert("FAIL, You don\'t have access. Please contact admin");
                        return false;
                    }else{                       
                        if (msg === '1'){
                            $('#msg').html('');
                            success_flag = 0;
                        }else{
                            alert('tesadf');
                            $('#msg').html('Username already available');
                            success_flag = 1;
                        }
                    }
                });
                if(success_flag === 1) return false;
            }    
        });

    });
</script>

<div class="users form signup-form">
    <?php echo $this->Form->create(null, ['url' => ['controller' => 'users', 'action' => 'add'], 'id' => "user_add"]); ?>
        <legend><?php __('Signup') ?></legend>
        <?php echo $this->Form->input('firstname');
         echo $this->Form->input('lastname');
         echo $this->Form->input('username');
         echo $this->Form->input('password');
    
         echo $this->Form->input('role',array('options'=> array('admin'=>'admin','representative'=>'representative'),'label'=>false,'type'=>'select'));

        ?>
<?php echo $this->Form->button(__('Signup')); ?>
<?php echo $this->Form->end() ?>
</div>
<div class="msg" id="msg">

</div>