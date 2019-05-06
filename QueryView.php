<?php
session_start();
// This file include table to show data from specific query also mange table sorting	
//-------------------------------------------------------------
function show_report ($conn,$sqlOginal)
{
	if (isset($_GET["GetPost"	])) 
	{
	print_r($_POST) ;
	$allPosts = $_POST;
	$countPosts = count($allPosts);
	echo ("Number of posts ".$countPosts);
	}
	
	$FilterCondition="";
		
	if (isset($_GET["FilterCondition"	])) $FilterCondition	=$_GET["FilterCondition"	]	;
		else
	foreach ($_POST as $key => $value)// check if the filter value in Session
		if (stripos($key,"xFilter")&&($value<>-1000)){
					if ($FilterCondition<>"") $FilterCondition=$FilterCondition." and " ;
					
						$FilterCondition=$FilterCondition."  ".$key." = ";
						if (is_numeric($value))	$FilterCondition=$FilterCondition.$value;
						else $FilterCondition=$FilterCondition."'".$value."'";
					
						$FilterCondition=str_replace("xFilter",'.',$FilterCondition);
				}
					
	if (isset($_GET["myfilter"	]))
		echo("<br>"."Filter condition ya PHP ".$FilterCondition."<br>");
		
	if (isset($_GET["mySession"	])) {
	echo("<br>"."<br>"); var_dump($_SESSION);echo("<br>");
	}
	
	$SearchText=$_POST["SearchText"	];
	
	if (isset($_GET["debug"	])) 
		echo ("Sort Order  Before  ".$sort_order."Search Text ".$SearchText); // print if in debug mode
	
	if (isset($_GET["SearchText"	])) $SearchText	=$_GET["SearchText"	]	; 
	
	if (isset($_GET["items_per_page"	])) $itemsPerPage	=$_GET["items_per_page"	]	; 
	else {
			$ComboPageSelection=$_POST["items_per_page"	];
			if ($ComboPageSelection=="")
				$itemsPerPage=20;
			else
				$itemsPerPage=$ComboPageSelection;
			if (isset($_GET["debug"	])) 	echo ("Combo selection".$ComboPageSelection);
		}
	
	if (isset($_GET["sort_field"	])) $sort_field 	=$_GET["sort_field"		]	; else $sort_field=1;
	if (isset($_GET["sort_order"	])) $sort_order 	=$_GET["sort_order"		]	; else $sort_order='ASC';
	if (isset($_GET["pn"			])) $pn				=$_GET["pn"		   		]	; else $pn=1; 
	
	if (isset($_GET["changepage"	])) 							; 
		else {
			$pn=1;
			$sort_order=='DESC' ? $sort_order='ASC' : $sort_order='DESC'; 
		}
	
	if ($pn <1) $pn=1;
		
	if (isset($_GET["debug"	])) echo (" Search text is : ".$SearchText);
	
	if ($SearchText<>""){ // there is search item
		if (isset($_GET["debug"	])){	
		echo ("Query where string  ".$_SESSION['QuerywhereStr']);
		echo("<br>");
		}
		
		if (stripos($sqlOginal, "WHERE") !== false) {
    	$sqlOginal	= $sqlOginal."  and  ";
		} else
			$sqlOginal	= $sqlOginal."  Where  ";
			
		$sqlOginal	= $sqlOginal.$_SESSION['QuerywhereStr']."'%".$SearchText."%' "  ;
		}
		
	if ($FilterCondition<>"")// there is search item
	{
		if (stripos($sqlOginal, "WHERE") !== false) {
    	$sqlOginal	= $sqlOginal."  and  ";
		} else
			$sqlOginal	= $sqlOginal."  Where  ";
			$sqlOginal	= $sqlOginal.$FilterCondition ;
	}	
		
	if (isset($_GET["finalSQL"	]))	
		echo ("<br>"."Final SQL with filter condition"."<br>".$sqlOginal);

	$ComboIncrement=20;
	$sql = $sqlOginal;
	

    $sql= construct_sql ($sql,$sort_field,$sort_order,$pn,$itemsPerPage);
	
	$pagesCount=11;
	display_query ($conn,$sqlOginal,$sql,$sort_order,$itemsPerPage,$SearchText,$sort_field,$ComboIncrement,$pn,$FilterCondition,$pagesCount);
	
	echo "<br>";
	
}
//---------------------------------------------------------------
function construct_sql ($sql,$sort_field,$sort_order,$pn,$itemsPerPage){// construct the sql txt
		
	$Limit 	="   Limit  " .($pn-1)*$itemsPerPage." , ".$itemsPerPage;
	
	$sql 	= $sql." order by ".$sort_field;
	$sql 	= $sql." ".$sort_order;
	$sql 	= $sql." ".$Limit;
	
	if (isset($_GET["debug"	])) 
	{
		echo ("<br>");
		echo ("    Final SQL  ".$sql); // print if in debug mode
		echo ("<br>");
	
	}
	return ($sql)	;
}
//--------------------------------------------------------------
function display_query ($conn,$sqlOginal,$sql,$sort_order,$itemsPerPage,$SearchText,$sort_field,$ComboIncrement,$pn,$FilterCondition,$pagesCount){
	
	$result_num_rows = $conn->query	($sqlOginal	);
	$NumOfRows = $result_num_rows->num_rows; 
	
	if (isset($_GET["debug"	])) 
	{	
	echo ("<br>");
	echo ("inside display query    Final SQL  ".$sql); // print if in debug mode
	}
	$result = $conn->query	($sql		);
	
// list page numbers 

echo "<div align='center'  >";
	if ($NumOfRows> 10000 ) 
	{
		echo "<br>";
		echo ("Number of rows ".$NumOfRows ."showing first 10,000 row");
		$NumOfRows=10000;
		echo "<br>";
	} 

	display_page_nums ($NumOfRows,$itemsPerPage,$sort_order,$SearchText,$FilterCondition,$pagesCount,$pn);
	
echo ("</div>");

	if (isset($_GET["debug"	])) echo ("Number of items per page -->".$itemsPerPage);

	$QuerywhereStr=
		display_query_header 	($conn,$result,$sort_order,$itemsPerPage,$SearchText,$ComboIncrement,$sort_field,$FilterCondition);
	
	$_SESSION['QuerywhereStr']=$QuerywhereStr;
		
	$StartCount = ($pn-1)*$itemsPerPage +1;
	display_query_rows 		($result,$StartCount);
			
}
//------------------------------------------------------------------------------
function display_rows_per_page_combo ($sort_order,$sort_field,$SearchText,$itemsPerPage,$ComboIncrement)
{

	if ($SearchText=="")
				echo ("<input type='text' Name='SearchText' placeholder='Search...'> ");
		else 
				echo ("<input type='text' Name='SearchText' Value=$SearchText> ");
	
	echo "<select name='items_per_page' >" ;
	
		for ($i=1; $i<5;$i++)	{	
			$comboValue=$i*$ComboIncrement; 	
			if ($itemsPerPage==$comboValue) 
						echo "<option selected='selected' value=$comboValue>$comboValue</option>" ; 
					else 
						echo "<option value=$comboValue>$comboValue</option>" ;
		}
	echo "</select>";
}
//--------------------------------------------------------------------------------
function GetStartPage ($pn,$NumOfPages,$pagesCount)// define the most left page number in the list of pages
{
	$pnStart = $pn-round(($pagesCount/2)) ;
	if ($pnStart<0) $pnStart=0;
	if (($pnStart+$pagesCount)>$NumOfPages)
		$pnStart=$NumOfPages-$pagesCount;
return($pnStart);
}
//------------------------------------------------------------------------------
function display_page_nums ($NumOfRows,$itemsPerPage,$sort_order,$SearchText,$FilterCondition,$pagesCount,$pn)
{
	
	if (isset($_GET["debug"	])) echo ("    Number of rows  ".$NumOfRows); // print if in debug mode
	
	$NumOfPages= ceil($NumOfRows/$itemsPerPage);
	$changepage='Y';
	
	//if (isset($_GET["pn"])) 			$pn				=$_GET["pn"				]	; else $pn=1; 
	if (isset($_GET["sort_field"	])) $sort_field 	=$_GET["sort_field"		]	; else $sort_field=1;

	if ($pn>1)   {
		
		$PrevPage=$pn-1;
		echo "<a href='?pn=1
						&changepage=$changepage&sort_order=$sort_order
						&items_per_page=$itemsPerPage
						&FilterCondition=$FilterCondition
						&SearchText=$SearchText
						&sort_field=$sort_field'>"."<<      "."</a>" ;

		echo "<a href='?pn=$PrevPage
						&changepage=$changepage
						&sort_order=$sort_order
						&items_per_page=$itemsPerPage
						&FilterCondition=$FilterCondition
						&SearchText=$SearchText
						&sort_field=$sort_field'>"."  <     "."</a>" ;
		
	}
	else{
		echo ("<<      <   "); 
	}	
	if ($NumOfPages>10)
		$pnStart = GetStartPage ($pn,$NumOfPages,$pagesCount) ;
	else
		$pnStart=0 ;
	
	if ($pagesCount>$NumOfPages) $pagesCount=$NumOfPages;
	
	for ($i=$pnStart; $i<$pnStart+$pagesCount;$i++){
			$j=$i+1;
    	if ($j==$pn) echo ($j."  ");
		else
		echo "<a href='?pn=$j
						&changepage=$changepage
						&sort_order=$sort_order
						&items_per_page=$itemsPerPage
						&FilterCondition=$FilterCondition
						&SearchText=$SearchText
						&sort_field=$sort_field'>".$j."  "."</a>" ;
		
	}
	
	if ($pn<$NumOfPages)   {

		$NextPage=$pn+1;
		echo "<a href='?pn=$NextPage
						&changepage=$changepage
						&sort_order=$sort_order
						&items_per_page=$itemsPerPage
						&FilterCondition=$FilterCondition
						&SearchText=$SearchText
						&sort_field=$sort_field'>"."    >   "."</a>" ;
		
		echo "<a href='?pn=$NumOfPages
						&changepage=$changepage
						&sort_order=$sort_order
						&items_per_page=$itemsPerPage
						&FilterCondition=$FilterCondition
						&SearchText=$SearchText
						&sort_field=$sort_field'>"."  >>    "."</a>" ;

	}
	else{
		echo ("      >"."     >>");
	}
	
	echo "<br>";
	echo ("page ".$pn. "  of  ".$NumOfPages);
	echo "<br>";
	
}// function end display page nums

// -------------------------------------------------------------------------------
function GetConditionValue ($ConditionStartLocation,$OrgSelectName,$FilterCondition)
	//this function takes filtercondition and get the value out of it.. example country.country_id = 15,
	// this function returns 15
{
			$ConditionEndLocation = $ConditionStartLocation+strlen($OrgSelectName);
			
			$ConditionValue=$FilterCondition;

			$ConditionValue=substr($ConditionValue,$ConditionEndLocation,strlen($ConditionValue)-$ConditionEndLocation);
			
			$ConditionValue=str_replace("=",' ',$ConditionValue); ////remove leading = 
			
			$ConditionValue=ltrim($ConditionValue); // remove leading left spaces
			
			$arr = explode(' ',trim($ConditionValue));
			$ConditionValue = $arr[0];
			
			$ConditionValue=rtrim($ConditionValue); // remove trail right spaces

			
				
				

	return ($ConditionValue);

}// Get Condition Value

//------------------------------------------------------
function FillFilterWithData ($conn,$field,$FilterCondition) // fill filter combo box with data
{	
	$OrgFieldName=$field->orgname;
		
	$FilterEndPos = stripos($field->name,"_1filter")+strlen("_1filter")+1	;//+1 to remove leading "_" from field ID name

	
	$IDFieldLen =strlen($field->name)-$FilterEndPos;
	
	
	$IDFieldName=	substr($field->name,$FilterEndPos, strlen($field->name)-$FilterEndPos) ;
    
	if (stripos($IDFieldName,"_GROUPBY")) 
			$IDFieldName= substr($IDFieldName,0,  strlen($IDFieldName)- strlen("_GROUPBYX"));// remove group by from the filed name

	$filterSQL ="Select Distinct ".$IDFieldName.",".$OrgFieldName. " From  ".$field->table. " Order by 2" ;
	
	if (isset($_GET["debug"	])) 
		echo ("<br>"."Final filter SQL ".$filterSQL."<br>"); // print if in debug mode
	
	
	$FilterResult = $conn->query	($filterSQL	);	

	$NetFieldName = substr($field->name,0,stripos($field->name,"_1filter"));
	$SelectName=$field->table."xFilter".$IDFieldName;
		
		//Get the value of selected item
							
			$OrgSelectName = str_replace("xFilter",'.',$SelectName);
		
			$ConditionValue=0 ;
			$ConditionStartLocation = stripos($FilterCondition,$OrgSelectName);// This filter include column name
			
			if ($ConditionStartLocation)	// now you have condition in the header for this filed, get its value		
			$ConditionValue = GetConditionValue ($ConditionStartLocation,$OrgSelectName,$FilterCondition);
	
			//echo ("<br>"."This is condition value ".$ConditionValue."<br>");
	
	echo "<select name=$SelectName >" ;
					
		$filter_field_cnt=$FilterResult->field_count	;
		if ($FilterResult->num_rows > 0) {
					
			// fill the slection combo box
			echo "<option  value=-1000> $NetFieldName</option>" ;// first item means no items selected

			while($row = $FilterResult->fetch_array()) {
			//loop to get all result set rows
								
				if (($_POST[$SelectName]==$row[0]) || ($ConditionValue==$row[0])) // check if this is the selected item
					echo "<option  selected='selected' value=$row[0]>$row[1]</option>" ; // Add selected item
				else 
					echo "<option  value=$row[0]>$row[1]</option>" ; // Add non selected item
				}
		}
		echo "</select>";	

	return ($NetFieldName);
	
}// End of FillFilterWithData 
//-------------------------------------------------------------
function display_query_header ($conn,$result,$sort_order,$itemsPerPage,$SearchText,$ComboIncrement,$sort_field,$FilterCondition)
{	// make the table header by reading result set metadata.
	// $result include requied data such as number of fields, field_count
	// also include field names.
	// This function also show the required filed filters which are marked as _1filter in the query alias.
	
	$QuerywhereStr		="";
	
	$field_cnt 	= $result->field_count	;
	$x=300;
	
	echo "<th> #  </th>" ;

	echo "<form action='?pn=1&sort_order=$sort_order&sort_field=$sort_field&changepage=Y' method='POST'>";
	echo "<div align='center'  style='float:right'>";
	
	
	for ($i=0; $i<$field_cnt;$i++){
		$field 		= $result->fetch_field();
		$j=$i+1;
		
		//check if this is a drop down filter field, for drop down filed always use _1filter to define that this is a drop done.
		// then use _code column name to define ID location 
		//As example country_1filter_country_id  means header will be country and the table ID in filed Country_id
		
		if (stripos($field->name,"_1filter")) // this column is filter combo box
		{
				
		$NetFieldName=FillFilterWithData ($conn,$field,$FilterCondition);
			
		if (isset($_GET["debug_filter"	]))	{	
			echo ("   Results ". $_POST[$field->table.'_'.$field->orgname]);
			echo ("   Array Index   ".$field->table.'_'.$field->orgname );
			//echo("<br>"); echo ("Filter SQL is ".$filterSQL);echo("<br>");
		}
		
		}// this is a filter field
		else
			$NetFieldName = $field->name;
		
		echo 
			"<th width=$x ><a href='?sort_field=$j&pn=1&items_per_page=$itemsPerPage&SearchText=$SearchText
			&FilterCondition=$FilterCondition&sort_order=$sort_order'>".$NetFieldName."</a></th>" ; // display column headers
	
		$QuerywhereStr=$QuerywhereStr.$field->table.".".$field->orgname.","; // construct where statment for the query
	}
	
	display_rows_per_page_combo ($sort_order,$sort_field,$SearchText,$itemsPerPage,$ComboIncrement);
		echo ("<input type='submit' value='GO'>");	
	echo ("</div>");

	echo "</form>" ;
		
	$QuerywhereStr = rtrim($QuerywhereStr,','); // remove last ',' from the query
	
	$QuerywhereStr =" concat (".$QuerywhereStr.") like " ;
	
	return($QuerywhereStr);
		
}// Function end
//------------------------------------------------------------------
function display_query_rows ($result,$StartCount)
	{
	// Table is columns and rows, columns are fileds 
	// $ result include all required information 
	// Filed count is the number of columns in the result set.
	// num rows is the number of rows returned in the query
	// strart count is the start counter of the page, i.e. page 1 start at 1, while page 3 starts at 2*number of items per page.

	$field_cnt 	= $result->field_count	;

	if ($result->num_rows > 0) {// output data of each row
			while($row = $result->fetch_array()) {//loop to get all result set rows
			echo "<tr>" ;
				echo "<td>" .$StartCount."</td>"  ;// show record number
				$StartCount++;	
				for ($j=0; $j<$field_cnt;$j++) //loop to print one row
					echo "<td>" . $row[$j].	"</td>" ;
			echo "</tr>";
			}

		} else {
		echo "0 results";
	}
	}//Function end
//----------------------------------------------------------------------
?>
