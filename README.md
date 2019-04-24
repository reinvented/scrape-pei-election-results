# Turn PEI Elections Results into Data

This is a simple PHP script to take the [HTML tables published by Elections PEI for the 2019 Provincial General Election Results](http://results.electionspei.ca/provincial/results_2019/index.html) and convert them into machine-readable data.

The result of the script is two files, under data/:

## pei-election-poll-winners.csv

This is a CSV file of the winning party (PC, Green, Liberal) of each of the 256 polls. Each row in the file represents one poll; the first column is the concatenation of the district and poll number (where 'A' is the advance poll) and the second column is the name of the winning party, like this:

	distpoll,winner
	1-A,PC
	1-1,PC
	1-2,PC
	1-3,PC
	1-4,PC
	1-5,PC
	1-6,PC
	1-7,Green
	
## pei-election-results.json

This is a JSON file of all results from all polls in all districts, like this:

	{
	  "1": {
	    "1": {
	      "Green": "84",
	      "Liberal": "53",
	      "PC": "114"
	    },
	    "2": {
	      "Green": "45",
	      "Liberal": "32",
	      "PC": "66"
	    },

## License 

Election results data is subject to [the terms outlined on the Elections PEI website](https://www.electionspei.ca/disclaimer):

> Elections PEI is the owner of the copyright, in accordance with the Copyright Act, in all materials and information found on this website unless otherwise stated. Materials and information on this website have been posted with the intent that it be readily available for personal and public non-commercial use.

The use outlined in this script clearly falls under the "public non-commercial use" condition.

The PHP code is free software; you can redistribute it and/or modify
it under the terms of the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.en.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

