<div class="users login-form"><?php 
    //echo $this->Flash->render('auth');
    echo $this->Form->create() ?>
    <fieldset>
        <div class="error <?php if(isset($error)) echo 'error-display';?>">
            <?php if(isset($error)) echo $error; ?>
        </div><?php 
        echo $this->Form->input('username',['class'=>'username','id'=>'username','label'=>'Email','placeholder'=>'Enter your email']);
        echo $this->Form->input('password',['class'=>'password','id'=>'password','label'=>'Password','placeholder'=>'Enter your password']) ?>
    </fieldset><?php 
    echo $this->Form->button(__('Login'),['class'=>'login-submit','onClick'=>'return loginform();']);
    echo $this->Form->end(); ?>
</div>

<script type="text/javascript">    
    function loginform(){
        var username = $.trim($('#username').val()),
            password = $.trim($('#password').val()),
            filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if(username.length <=0){
            $('.error').html('Please enter your email address');
            $('.error').show();
            return false;
        }else{
           if (filter.test(username)){
               $('.error').html('');
               $('.error').hide();
           } 
           else {
                $('.error').html('Please enter valid email address');
                $('.error').show();
                return false; 
           }
        }
        if(password.length <= 0){
           $('.error').html('Please enter your password');
           $('.error').show();
           return false; 
        }
    }
</script>


