<!DOCTYPE html>  

<html>  

  <head>  

    <title>Awaiting Approval</title>  

    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
    <link rel="stylesheet" type="text/css" href="/styles.css">  
    <style> 
		blockquote { max-width: 100%; }
		input[type='number'] { width: 80px; }
    </style>
  </head>  

  <body>

    <form action="review.php" method="post"> 
        <p>Some pages awaiting review:</p>  

	<blockquote><p>
		[Worksafe<input type="checkbox" id="selectAllWorksafe">]
		[Surprise<input type="checkbox" id="selectAllSurprise">]
		[Skip<input type="checkbox" id="selectAllSkip">]
		[Bury<input type="checkbox" id="selectAllBury">]
		[Deny<input type="checkbox" id="selectAllDeny">]
		 - Open All<input type="checkbox" id="openLinks">
		<hr>
	</p></blockquote>

        <?php $i=0; ?> 
        <?php foreach ($url as $pageurl): ?>  

          <blockquote><p>  

	    <a class="tlink" href="<?php echo htmlspecialchars($pageurl, ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 17px;" target="_blank"><?php echo htmlspecialchars($pageurl, ENT_QUOTES, 'UTF-8'); ?></a><br>
	    <?php if($worksafe[$i] == '1'): ?>
		[Worksafe<input type="checkbox" id="worksafe<?php echo $id[$i] ?>" name="worksafe<?php echo $id[$i] ?>" class="cbWorksafe" checked="checked">]
	    <?php else: ?>
		[Worksafe<input type="checkbox" id="worksafe<?php echo $id[$i] ?>" name="worksafe<?php echo $id[$i] ?>" class="cbWorksafe">]
	    <?php endif; ?>
	    [Surprise<input type="checkbox" id="surprise<?php echo $id[$i] ?>" name="surprise<?php echo $id[$i] ?>" class="cbSurprise">]
            [Skip<input type="checkbox" id="skip<?php echo $id[$i] ?>" name="skip<?php echo $id[$i] ?>" class="cbSkip">]
            [Bury<input type="checkbox" id="bury<?php echo $id[$i] ?>" name="bury<?php echo $id[$i] ?>" class="cbBury">]
	    [Deny<input type="checkbox" id="deny<?php echo $id[$i] ?>" name="deny<?php echo $id[$i] ?>" class="cbDeny">]
	    [Updatable<select id="updatable<?php echo $id[$i] ?>" name="updatable<?php echo $id[$i] ?>"> 
			<option value=1>1 WEEK</option>
			<option value=2>1 DAY</option> 
			<option value=3>12 HOUR</option>
			<option value=4>6 HOUR</option>
			<option value=5>3 HOUR</option>
			<option value=6>1 HOUR</option>
		      </select>]
		
		[Crawl: Depth <input type="number" id="crawldepth<?php echo $id[$i] ?>" name="crawldepth<?php echo $id[$i] ?>" >
		Pages <input type="number" id="crawlpages<?php echo $id[$i] ?>" name="crawlpages<?php echo $id[$i] ?>" >
		Type <select id="crawltype<?php echo $id[$i] ?>" name="crawltype<?php echo $id[$i] ?>">
			<option value=0>Local</option>
			<option value=1>All</option>
			<option value=2>External</option>
			</select>
		Enforce Rules<input type="checkbox" id="forcerules<?php echo $id[$i] ?>" name="forcerules<?php echo $id[$i] ?>" >
		Repeat<input type="checkbox" id="crawlrepeat<?php echo $id[$i] ?>" name="crawlrepeat<?php echo $id[$i] ?>" >]
          </p></blockquote>  
          <?php $i++; ?>
        <?php endforeach; ?> 
	<br> 
	<?php $r=5; ?>
        <div><input type="submit" id="submit" value="Submit"/></div>    
	<input type="hidden" name="startid" id="startid" value="<?php echo $id[0]; ?>">
	<input type="hidden" name="endid" id="endid" value="<?php echo $id[$i-1]; ?>">
    </form> 

	<!-- Script for Select All Worksafe Checkbox -->
	<script>
	  // 1. Target the elements
	  const selectAllWorksafe = document.getElementById('selectAllWorksafe');
	  const checkboxesWorksafe = document.querySelectorAll('.cbWorksafe');

	  // 2. Add event listener to the "Select All" checkbox
	  selectAllWorksafe.addEventListener('change', function() {
	    checkboxesWorksafe.forEach(checkbox => {
	      // Set each checkbox state to match the "Select All" state
	      checkbox.checked = this.checked;
	    });
	  });

	  // 3. Optional: Uncheck "Select All" if an individual checkbox is manually unchecked
	  checkboxesWorksafe.forEach(checkbox => {
	    checkbox.addEventListener('change', function() {
	      // If any checkbox is unchecked, "Select All" cannot be true
	      if (!this.checked) {
		selectAllWorksafe.checked = false;
	      }
	      
	      // If all checkboxesWorksafe get checked manually, check the "Select All" box too
	      const allChecked = Array.from(checkboxesWorksafe).every(cb => cb.checked);
	      if (allChecked) {
		selectAllWorksafe.checked = true;
	      }
	    });
	  });
	</script>
	
	<!-- Script for Select All Surprise Checkbox -->
	<script>
	  // 1. Target the elements
	  const selectAll = document.getElementById('selectAllSurprise');
	  const checkboxesSurprise = document.querySelectorAll('.cbSurprise');

	  // 2. Add event listener to the "Select All" checkbox
	  selectAllSurprise.addEventListener('change', function() {
		checkboxesSurprise.forEach(checkbox => {
		  // Set each checkbox state to match the "Select All" state
		  checkbox.checked = this.checked;
		});
	  });

	  // 3. Optional: Uncheck "Select All" if an individual checkbox is manually unchecked
	  checkboxesSurprise.forEach(checkbox => {
		checkbox.addEventListener('change', function() {
		  // If any checkbox is unchecked, "Select All" cannot be true
		  if (!this.checked) {
			selectAllSurprise.checked = false;
		  }
		  
		  // If all checkboxesSurprise get checked manually, check the "Select All" box too
		  const allChecked = Array.from(checkboxesSurprise).every(cb => cb.checked);
		  if (allChecked) {
			selectAllSurprise.checked = true;
		  }
		});
	  });
	</script>

	<!-- Script for Select All Skip Checkbox -->
	<script>
	  // 1. Target the elements
	  const selectAllSkip = document.getElementById('selectAllSkip');
	  const checkboxesSkip = document.querySelectorAll('.cbSkip');

	  // 2. Add event listener to the "Select All" checkbox
	  selectAllSkip.addEventListener('change', function() {
		checkboxesSkip.forEach(checkbox => {
		  // Set each checkbox state to match the "Select All" state
		  checkbox.checked = this.checked;
		});
	  });

	  // 3. Optional: Uncheck "Select All" if an individual checkbox is manually unchecked
	  checkboxesSkip.forEach(checkbox => {
		checkbox.addEventListener('change', function() {
		  // If any checkbox is unchecked, "Select All" cannot be true
		  if (!this.checked) {
			selectAllSkip.checked = false;
		  }
		  
		  // If all checkboxesSkip get checked manually, check the "Select All" box too
		  const allChecked = Array.from(checkboxesSkip).every(cb => cb.checked);
		  if (allChecked) {
			selectAllSkip.checked = true;
		  }
		});
	  });
	</script>
	
	<!-- Script for Select All Bury Checkbox -->
	<script>
	  // 1. Target the elements
	  const selectAllBury = document.getElementById('selectAllBury');
	  const checkboxesBury = document.querySelectorAll('.cbBury');

	  // 2. Add event listener to the "Select All" checkbox
	  selectAllBury.addEventListener('change', function() {
		checkboxesBury.forEach(checkbox => {
		  // Set each checkbox state to match the "Select All" state
		  checkbox.checked = this.checked;
		});
	  });

	  // 3. Optional: Uncheck "Select All" if an individual checkbox is manually unchecked
	  checkboxesBury.forEach(checkbox => {
		checkbox.addEventListener('change', function() {
		  // If any checkbox is unchecked, "Select All" cannot be true
		  if (!this.checked) {
			selectAllBury.checked = false;
		  }
		  
		  // If all checkboxesBury get checked manually, check the "Select All" box too
		  const allChecked = Array.from(checkboxesBury).every(cb => cb.checked);
		  if (allChecked) {
			selectAllBury.checked = true;
		  }
		});
	  });
	</script>
	
	<!-- Script for Select All Deny Checkbox -->
	<script>
	  // 1. Target the elements
	  const selectAllDeny = document.getElementById('selectAllDeny');
	  const checkboxesDeny = document.querySelectorAll('.cbDeny');

	  // 2. Add event listener to the "Select All" checkbox
	  selectAllDeny.addEventListener('change', function() {
	    checkboxesDeny.forEach(checkbox => {
	      // Set each checkbox state to match the "Select All" state
	      checkbox.checked = this.checked;
	    });
	  });

	  // 3. Optional: Uncheck "Select All" if an individual checkbox is manually unchecked
	  checkboxesDeny.forEach(checkbox => {
	    checkbox.addEventListener('change', function() {
	      // If any checkbox is unchecked, "Select All" cannot be true
	      if (!this.checked) {
		selectAllDeny.checked = false;
	      }
	      
	      // If all checkboxesDeny get checked manually, check the "Select All" box too
	      const allChecked = Array.from(checkboxesDeny).every(cb => cb.checked);
	      if (allChecked) {
		selectAllDeny.checked = true;
	      }
	    });
	  });
	</script>

	<!-- Script for Open All Checkbox -->
	<script>
	  // 1. Target the elements
	  const openLinksCheckbox = document.getElementById('openLinks');
	  const links = document.querySelectorAll('.tlink');

	  // 2. Add event listener to the "Open All" checkbox
	  openLinksCheckbox.addEventListener('change', function() {
	    if (this.checked) {
	      for(let index = links.length - 1; index >= 0; index--){
	        const element = links[index].attributes['href'].value;
	        window.open(element, '_blank');
	      }
	    }
	  });
	</script>
  </body>

</html>
