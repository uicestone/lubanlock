<?php $this->view('head'); ?>
<form method="post" class="form-inline">
	<input type="text" name="username" placeholder="<?=lang('username')?>">
	<input type="password" name="password" placeholder="<?=lang('password')?>">
	<button type="submit"><?=lang('login')?></button>
</form>
<?php $this->view('foot'); ?>