<?php header("Content-Type: application/json;charset=utf-8"); ?>
<?php $i=0; ?>[
<?php foreach ($url as $storedresult): ?>
<?php $title[$i] = htmlentities($title[$i], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
<?php $bodymatch[$i] = htmlentities($bodymatch[$i], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
<?php $description[$i] = htmlentities($description[$i], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
<?php $title[$i] = str_replace("<","&lt;",$title[$i]); $title[$i] = str_replace(">","&gt;",$title[$i]); ?>
<?php $bodymatch[$i] = str_replace("<","&lt;",$bodymatch[$i]); $bodymatch[$i] = str_replace(">","&gt;",$bodymatch[$i]); ?>
<?php $description[$i] = str_replace("<","&lt;",$description[$i]); $description[$i] = str_replace(">","&gt;",$description[$i]); ?>
  {
    "URL": "<?php echo htmlspecialchars($storedresult, ENT_QUOTES, 'UTF-8'); ?>",
    "Title": "<?php echo $title[$i]; ?>",
    "Snippet": "<?php echo $bodymatch[$i]; ?>",
    "Description": "<?php echo $description[$i]; $i++; ?>"
  }<?php if ($i<sizeof($url)): ?>,
<?php endif ?><?php endforeach; ?><?php if($i >= $lim && $starappend == 0): ?>,
  { 
    "NextOffset": "<?php echo $totalcount;?>"
  }
<?php endif; ?>

]
