<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">
	<script type="text/javascript" charset="utf-8" src="/js/jquery.js"></script>
	<link href="/css/bootstrap.css" rel="stylesheet">
	<link href="/css/bootstrap-responsive.css" rel="stylesheet">
	<script src="/js/bootstrap.min.js" type="text/javascript"></script>
	<link rel="shortcut icon" href="/favicon.ico">
	<title>Baron's Grid Maker</title>
	<style type ="text/css">
		body {
			padding-top: 10px;
			padding-bottom: 40px;
			background-color: #F5F5F5;
			background-repeat:no-repeat;
			background-size:100% 100%;
		}
		<!--p {text-align:center;}-->
		h1 {text-align:center;}
		h3 {text-align:left;}
		h4 {text-align:center;}
		h5 {text-align:center;}
		.submenu{display:none;}
		#my-div
		{
			width	 : 900px;
			height	 : 200px;
			overflow : hidden;
			position : relative;
			margin: 0 auto;
			display:block;
		}
		 
		#my-iframe
		{
			position : absolute;
			top		 : -850px;
			left	 : -150px;
			width	 : 1280px;
			height	 : 1200px;
			margin: 0 auto;
			display:block;
		}
	</style>
</head>
<body>
	<?php	 
	set_time_limit(3600000); //60 minutes
	
	$numberOfRaces = 4;
	//$tolerance = 2;
	$visualInputList = array_fill(0, 30, "");
	$inputList = []; //seperate actual list, as the visual might have blanks from data entry
	$numberOfDrivers = 0;
	
	if(isset($_GET['numberOfRaces']))
	{
		$numberOfRaces = ($_GET['numberOfRaces']);
	}
	/*if(isset($_GET['tolerance']))
	{
		$tolerance = ($_GET['tolerance']);
		Tolerance: <input type="text" name="tolerance" value="<?php echo $tolerance;?>"></br>
	}*/
	?>
	
	<h3>FriendlyBaron's Championship Grid Creator</h3>
	Created for NODO - See discussion: [link later]</br></br>
	
	<form>
	Number of Races: <input type="number" name="numberOfRaces" min="1" max="256" value="<?php echo $numberOfRaces;?>"></br></br>
	
	<?php
	if(isset($_GET['player1']))
	{
		for ($x = 1; $x < 31; $x++)
		{
			$testStr = ('player'.$x);
			if(isset($_GET[$testStr]) && $_GET[$testStr] != "")
			{
				$visualInputList[$x-1] = $_GET[$testStr];
				$inputList[$x-1] = $_GET[$testStr];
				$numberOfDrivers++;
			}
		}
	}
	
	for ($x = 1; $x < 31; $x++)
	{
		?>
			Player <?php echo $x; ?>:<?php if($x < 10){echo "&nbsp;&nbsp;";} ?> <input type="text" name="player<?php echo $x;?>" value="<?php echo $visualInputList[$x-1];?>"></br>
		<?php
	} 
	?>
	</br>
	<input type="submit" value="Create Grids"></br>
	</form>

	<?php
	
	if($numberOfDrivers > 1 && $numberOfRaces < 101 && $numberOfRaces > 0)
	{
		$races = [];
		
		if ($numberOfRaces == 1)
		{
			shuffle($inputList);
			
			echo '<hr><b>One Randomized Grid:</b></br><hr>';
			
			echo '<div style="width: 100%; background-color: white;">';
			listArray($inputList);
			echo '</div>';
		}
		else if ($numberOfRaces == 2)
		{
			for ($x = 0; $x < $numberOfRaces; $x++)
			{
				$races[$x] = $inputList;
			}
			shuffle($races[0]);
			$races[1] = array_reverse($races[0]);
			
			echo '<hr><b>Two Randomized Races:</b></br>Race 1 is randomized, then Race 2 is reversed.</br><hr>';
			
			echo '<div style="width: 100%; background-color: white;">';
			echo 'Race #1:</br></br>';
			listArray($races[0]);
			echo '</div></br>';
			
			echo 'Race #2:</br></br>';
			echo '<div style="width: 100%; background-color: #F5F5F5;">';
			listArray($races[1]);
			echo '</div>';
		}
		else
		{
			for ($x = 0; $x < $numberOfRaces; $x++)
			{
				$races[$x] = $inputList;
			}
			
			for ($x = 0; $x < $numberOfRaces; $x++)
			{
				shuffle($races[$x]);
			} 
			
			$attempts = 0;
			$retry = false;
			$midpoint = (($numberOfDrivers+1)/2);
			
			$orderedAvgs = [];
			$lastLowSwap = "";
			$lastHighSwap = "";
			$lastSuccess = $races;
			$lastIteration = 0;
			
			$tolerance = 10;
			
			while ($attempts > -1 && $attempts < 250000) //250000 takes about 5 minutes at most
			{
				$retry = false;
				
				if($attempts % 2500 == 0) //If it hits 2500 iters, it means its more or less stuck, so shake things up by randomly shuffling one of the races. Also runs on first pass to generate averages.
				{
					shuffle($races[rand(0, $numberOfRaces-1)]);
					foreach($inputList as $result) { //then recalc all averages.
						$avg = getAverage($result, $races);
						$orderedAvgs[$result] = $avg;
					}
				}
				else
				{
					$orderedAvgs[$lastLowSwap] = getAverage($lastLowSwap, $races); //save computation time by only recalculating the averages of swapped players
					$orderedAvgs[$lastHighSwap] = getAverage($lastHighSwap, $races);
				}
				
				asort($orderedAvgs);
				
				$firstName = "";
				$firstAvg = 0;
				$lastName = "";
				$lastAvg = 0;
				$counter = 0;
				
				foreach(array_keys($orderedAvgs) as $result) {
				
					if ($counter == 0)
					{
						$firstName = $result;
						$firstAvg = $orderedAvgs[$result];
					}
					else if($counter == (sizeof($orderedAvgs)-1))
					{
						$lastName = $result;
						$lastAvg = $orderedAvgs[$result];
					}
					$counter++;
				}
				
				//find if lowest is out of bounds
				if ($firstAvg < ($midpoint-$tolerance))
				{
					//swap if yes
					$races = doSwap($races, $firstName, $lastName);
					$retry = true;
				}
				else if ($lastAvg > ($midpoint+$tolerance))
				{
					//swap if yes
					$races = doSwap($races, $firstName, $lastName);
					$retry = true;
				}
				
				$lastLowSwap = $firstName;
				$lastHighSwap = $lastName;
				
				if($retry == false) //if success at current tolerance
				{
					if ($tolerance > 0.01) //neglible amount, if it gets to 0.01 they are all the same number
					{
						$lastIteration = $attempts;
						$attempts = 0;
						$tolerance = $tolerance * (0.9); //slowly make the tolerance smaller
						$lastSuccess = $races; //store last successful list of races
					}
					else
					{
						break;
					}
				}
				$attempts++;
			}
			
			$races = $lastSuccess; 
			$tolerance = $tolerance / (0.9); //bump back up to successful tolerance
			
			echo '<hr><b>',$numberOfRaces,' Randomized Races:</b></br><hr>';
			
			echo 'First listed is the average starting position for each player. This is calculated to be as close as possible, a tolerance of ',$tolerance,' extending from a midpoint of ',$midpoint,' was found to be the closest amount for this data-set.</br></br>';
			
			$ordered = [];
			
			foreach($inputList as $result) {
				$avg = getAverage($result, $races);
				$ordered[$result] = $avg;
			}
			
			asort($ordered);
			
			$orderedCount = 1;
			
			foreach(array_keys($ordered) as $result) {
				echo $orderedCount,'. ',$result,' : ',$ordered[$result],'<br>';
				$orderedCount++;
			}
			
			echo '<b></br><hr>Listed below are the starting grids for your ',$numberOfRaces,' races:</b><hr>';
			
			for ($x = 0; $x < $numberOfRaces; $x++)
			{
				if($x % 2 == 0)
				{
					echo '<div style="width: 100%; background-color: white;">';
				}
				else
				{
					echo '<div style="width: 100%; background-color: #F5F5F5;">';
				}
				echo '</br>Race #',($x+1),':</br></br>';
				listArray($races[$x]);
				echo '</div>';
			}
		}
	}
	
	function doSwap($racesList, $low, $high)
	{	 
		$possibles = [];
		for ($y = 0; $y < count($racesList); $y++)
		{
			$possibles[$y] = $y;
		}
	
		shuffle($possibles);

		foreach($possibles as $x) //tl;dr - this makes an array with numbers 0 to count($racesList)-1 in a random order and goes through it like a for loop. i dont remember the path taken in my head to get here, it was doing something else before
		{
			$positionInRace = $racesList[$x];
			$namesAsKey = [];
			$counter = 0;
			ksort($positionInRace);
		
			foreach($positionInRace as $result) { //this reverses the array from being 1==>name to name==>1 so we can easily find starting position per race
				$namesAsKey[$result] = $counter;
				$counter++;
			}
			if ($namesAsKey[$low] < $namesAsKey[$high]) //if the lowest avg player's starting spot in race X is less than the starting spot of the highest - swap their spots!
			{
				$racesListCounter = 0;
				$posLow = -1;
				$posHigh = -1;
				foreach($racesList[$x] as $result) { //now we go back into the actual 1==>name list
					if(strcmp($result, $low) == 0)
					{
						$posLow = $racesListCounter;
					}
					if(strcmp($result, $high) == 0)
					{
						$posHigh = $racesListCounter;
					}
					$racesListCounter++;
				}
				$racesList[$x][$posLow] = $high;
				$racesList[$x][$posHigh] = $low;
				
				return $racesList;
			}
		}
		return $racesList;
	}
	 
	function listArray($theArray)
	{
		$count = 1;
		foreach($theArray as $result) {
			echo $count,". ",$result,'</br>';
			$count++;
		}
	}
	
	function getAverage($name, $races)
	{
		$amount = count($races);
		$running = 0;
		
		for ($x = 0; $x < $amount; $x++)
		{
			$running += (1+array_search($name, $races[$x])); //gives the position (place) in array
		}
		return ($running/$amount);
	}
	
	?>
  

</body>

