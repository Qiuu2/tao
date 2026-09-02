//Ƚ֮ǰת
	function convert(value, dataType) 
	{
		switch(dataType) 
		{
			case "int":
				return parseInt(value);
				break
			case "float":
				return parseFloat(value);
				break
			case "date":
				return Date.parse(value);
				break
			default:
				return value.toString();
		}
	}
	//sortȽַ
	function compareCols(col, dataType) 
	{
		return function compareTrs(tr1, tr2) 
		{
			value1 = convert(tr1.cells[col].innerHTML, dataType);

			value2 = convert(tr2.cells[col].innerHTML, dataType);
			
			if (value1 < value2) 
			{
				return -1;
			} 
			else if (value1 > value2) 
			{
				return 1;
			} 
			else 
			{
				return 0;
			}
		};
	}
	//Ա
	


	function sortTable(tableId, col, dataType) 
	{
		var table = document.getElementById(tableId);

		var tbody = table.tBodies[0];
		
		var tr = tbody.rows; 
		
		var trValue = new Array();
		
		for (var i=0; i<tr.length; i++ ) 
		{
			trValue[i] = tr[i];  //иеϢ洢½
		}
		
		if (tbody.sortCol == col &&(dataType=='1')) 
		{
			
			trValue.reverse(); //ѾˣֱӶ䷴
		} 
		else 
		{
			
			trValue.sort(compareCols(col, dataType));  //
		}

		var fragment = document.createDocumentFragment();  //½һƬΣڱĽ

		for (var i=0; i<trValue.length; i++ ) 
		{
			fragment.appendChild(trValue[i]);
		}
		tbody.appendChild(fragment); //Ľ滻֮ǰֵ

		tbody.sortCol = col;
	}
