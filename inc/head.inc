<?php
$basename = trim(basename($_SERVER['PHP_SELF']) . PHP_EOL);
?>

 <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <div class="nav-collapse collapse">
            <ul class="nav">
             <li
	<?php if (strcmp($basename, 'index.php') == 0)
	echo " class='active'";
	?>><a
		href="index.php">home</a></li>
            <li
	<?php if (strcmp($basename, 'concepts.php') == 0)
	echo " class='active'";
	?>><a
		href="concepts.php">concepts</a></li>
            <li
	<?php if (strcmp($basename, 'informationmodel.php') == 0)
	echo " class='active'";
	?>><a
		href="informationmodel.php">model</a></li>
	<li
	<?php if (strcmp($basename, 'temporal.php') == 0)
	echo " class='active'";
	?>><a
		href="temporal.php">temporal</a></li>
	<li
	<?php if (strcmp($basename, 'numeric.php') == 0)
	echo " class='active'";
	?>><a
		href="numeric.php">numeric</a></li>
		<li
	<?php if (strcmp($basename, 'textual.php') == 0)
	echo " class='active'";
	?>><a
		href="textual.php">textual</a></li>
	<li
	<?php if (strcmp($basename, 'boolean.php') == 0)
	echo " class='active'";
	?>><a
		href="boolean.php">boolean</a></li>
	<li
	<?php if (strcmp($basename, 'exclusion.php') == 0)
	echo " class='active'";
	?>><a
		href="exclusion.php">exclusion</a></li>
	<li
	<?php if (strcmp($basename, 'numerator.php') == 0)
	echo " class='active'";
	?>><a
		href="numerator.php">numerator</a></li>
	<li
	<?php if (strcmp($basename, 'query.php') == 0)
	echo " class='active'";
	?>><a
		href="query.php">query</a></li>
	<!--  <li><a target="_blank" href="schema/tables/Patient.html">schema</a>
	</li>-->
	<li
	<?php if (strcmp($basename, 'help.php') == 0)
	echo " class='active'";
	?>><a
		href="help.php">help</a></li>
	<li
	<?php if (strcmp($basename, 'logout.php') == 0)
	echo " class='active'";
	?>><a
		href="logout.php">logout</a>
	</li>
	<li><a style="text-decoration: none; background-color: transparent"
		href="">user: <?php echo $username; ?> </a>
	</li>
            
            </ul>
  
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>
