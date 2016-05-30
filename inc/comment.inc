<div style="height: 30px"></div>

<div style="width: 100%; height: 30%; font-size: 0.8em; float: right">
	<a name="comment"></a>
	<form action="<?php echo $_SERVER['PHP_SELF'].'#comment'; ?>"
		method="post">
		<?php echo"<textarea class='xxlarge' id='textarea' name='comment' style='margin-left: 10px; width: 70%; border: 2px solid grey' rows='5' cols='100'>";if(!empty($comment)){echo$comment;}else{echo $commentcomment;}echo"</textarea>";?>
		<br /> <br />
		<button type="submit" class="btn small primary" name="submitComment"
			value="submit">save changes</button>
	</form>
</div>
