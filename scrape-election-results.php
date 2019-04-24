<?php
/**
  * scrape-election-results.php - A PHP script to scrape PEI election results into
  * machine-readable data.
  *
  * Elections PEI only publishes election results information as HTML tables
  * (http://results.electionspei.ca). This script scrapes those tables and converts
  * the results into machine-readable data.
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 3 of the License, or (at
  * your option) any later version.
  *
  * This program is distributed in the hope that it will be useful, but
  * WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
  * General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
  * USA
  *
  * @version 0.2, April 24, 2019
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2019, Reinvented Inc.
  * @license http://www.fsf.org/licensing/licenses/gpl.txt GNU Public License
  */


// Include the PHP Simple HTML DOM Parser (https://simplehtmldom.sourceforge.io/),
// a library that simplifies parsing HTML pages.
include('simple_html_dom.php');

// We're going to store the election results data in an array called $results.
$results = array();

// Loop through each of the 27 electoral districts
for ($district = 1 ; $district <= 27 ; $district++) {

	// Get the HTML for each district's district-specific web pate
	$html = file_get_html('http://results.electionspei.ca/provincial/results_2019/district' . $district . '.html');

	// Results are presented in a different order on each district page, based
	// on candidate's last names, so we need to figure out which column represents
	// which party. Fortunately there are some CSS classes in the HTML that
	// aid with this.
	//
	// The table row with the candidate information has a CSS class of "summaryheader,"
	// and each candidate's name appears in a cell in that row:
	//
	// <tr class="summaryheader">
	// 	<td class="districtheadertext">
	// 		0 of 10<br>polls reporting
	// 	</td>
	// 	<td style="text-align:center;background: #68AD2E">
	// 		<span style="font-size:80%;">
	// 			(Green)<br />
	// 		</span>
	// 		<span style="font-size:90%;">
	// 			KARLA<br />
	// 		</span>
	// 		<b>
	// 			BERNARD
	// 		</b>
	// 	</td>
	// 	<td style="text-align:center;background: #D51F24">
	// 		<span style="font-size:80%;">
	// 			(Liberal)<br />
	// 		</span>
	// 		<span style="font-size:90%;">
	// 			RICHARD<br />
	// 		</span>
	// 		<b>
	// 			BROWN
	// 		</b>
	// 	</td>
	// 	<td style="text-align:center;background: #F26722">
	// 		<span style="font-size:80%;">
	// 			(NDP)<br />
	// 		</span>
	// 		<span style="font-size:90%;">
	// 			JOE<br />
	// 		</span>
	// 		<b>
	// 			BYRNE
	// 		</b>
	// 	</td>
	// 	<td style="text-align:center;background: #0168C7">
	// 		<span style="font-size:80%;">
	// 			(PC)<br />
	// 		</span>
	// 		<span style="font-size:90%;">
	// 			TIM<br />
	// 		</span>
	// 		<b>
	// 			KEIZER
	// 		</b>
	// 	</td>
	// </tr>

	// $counter stores our place in the row as we work our way through it.
	$counter = 0;

	// $column is an array used to related the columns in the HTML table
	// to the political party name.
	$column = array();

	// A handy feature of the PHP Simple HTML DOM Parser is the "find" method,
	// which makes it easy to locate things like "all the cells inside the
	// table row with CSS class of "summaryheader," which is what we're doing
	// here...
	foreach($html->find('tr.summaryheader td') as $element) {
		// Within each cell there are two spans; the one with the
		// party name in it contains the string ')<br />', so we
		// look for that and when we find it we strip out everything
		// but the party name, and then store it in $column.
		foreach ($element->find('span') as $span) {
			if (strpos($span->innertext, ')<br />')) {
				$party = $span->innertext;
				$party = str_replace(')<br />','',$party);
				$party = str_replace('(','',$party);
				$column[$counter/2] = $party;
			}
			$counter++;
		}
	}

	// At this point we now know which party's results appear in which
	// column, as the $column array looks like this (for example):

	// Array
	// (
	//     [0] => Liberal
	//     [1] => PC
	//     [2] => Green
	// )

	// Now that we know which column maps to which party, we can parse
	// the results themselves. Each poll in the district gets a row
	// with CSS class of "districtresults":

	// <tr class="districtresults">
	// 	<td class="districtheadertext">
	// 		A
	// 	</td>
	// 	<td>
	// 		<span style="font-weight:bold;">
	// 			0
	// 		</span>
	// 	</td>
	// 	<td>
	// 		<span style="font-weight:bold;">
	// 			0
	// 		</span>
	// 	</td>
	// 	<td>
	// 		<span style="font-weight:bold;">
	// 			0
	// 		</span>
	// 	</td>
	// 	<td>
	// 		<span style="font-weight:bold;">
	// 			0
	// 		</span>
	// 	</td>
	// </tr>

	// We start by finding each of these results rows
	foreach($html->find('tr.districtresults') as $element) {
		// And within the role we find the name of the poll, which is in a cell
		// with CSS class if "districtheadertext". We grab the contents of that
		// cell to get the name of the poll.
		$poll = $element->find('td.districtheadertext');
		$pollnumber = $poll[0]->plaintext;
		// In the HTML table the polls are packed with zeroes; we want a numeric
		// value, unless the poll is "A" for the advance poll.
		if (is_numeric($pollnumber)) {
			$pollnumber = intval($pollnumber);
		}
		// We use $counter to store which column we're looking at the results for
		$counter = 0;
		// And now we get the results themselves: each result is inside a span
		// inside a table cell.
		foreach ($element->find('td') as $td) {
			foreach ($td->find('span') as $span) {
				// We store the results in an array, by district number, poll number
				// and party (remembering that we scraped which party maps to which
			  // column earlier)
				$results[$district][$pollnumber][$column[$counter]] = $span->plaintext;
				// On to the next column!
				$counter++;
			}
		}
	}

	// The end result of the above is the results for all parties for a single district
	// added to the $results array, like this:

	// 	Array
	// (
	//     [1] => Array
	//         (
	//             [A] => Array
	//                 (
	//                     [Liberal] => 467
	//                     [PC] => 684
	//                     [Green] => 365
	//                 )
	//             [1] => Array
	//                 (
	//                     [Liberal] => 53
	//                     [PC] => 114
	//                     [Green] => 84
	//                 )
	//             [2] => Array
	//                 (
	//                     [Liberal] => 32
	//                     [PC] => 66
	//                     [Green] => 45
	//                 )
	//             [3] => Array
	//                 (
	//                     [Liberal] => 35
	//                     [PC] => 58
	//                     [Green] => 39
	//                 )
	//             [4] => Array
	//                 (
	//                     [Liberal] => 32
	//                     [PC] => 60
	//                     [Green] => 44
	//                 )
	//             [5] => Array
	//                 (
	//                     [Liberal] => 46
	//                     [PC] => 75
	//                     [Green] => 54
	//                 )
	//             [6] => Array
	//                 (
	//                     [Liberal] => 45
	//                     [PC] => 95
	//                     [Green] => 44
	//                 )
	//             [7] => Array
	//                 (
	//                     [Liberal] => 18
	//                     [PC] => 37
	//                     [Green] => 39
	//                 )
	//             [8] => Array
	//                 (
	//                     [Liberal] => 44
	//                     [PC] => 88
	//                     [Green] => 48
	//                 )
	//             [9] => Array
	//                 (
	//                     [Liberal] => 89
	//                     [PC] => 70
	//                     [Green] => 42
	//                 )
	//         )
	// )
	//
	// We now move on to the next district, until we've parsed all 27.

}

// With the results all neatly store in $results, we write out results as JSON.
$fp = fopen("data/pei-election-results.json", "w");
fwrite($fp, json_encode($results));
fclose($fp);

// We also want a CSV file that records the winner of each poll
$fp = fopen("data/pei-election-poll-winners.csv", "w");
// Headers for the CSV: two columns, the district and poll
// concatenated, plus the winning party.
fwrite($fp, "distpoll,winner\n");

// Loop through each of the 27 electoral districts
for ($district = 1 ; $district <= 27 ; $district++) {
	// The election in district 9 was delayed, so there are
	// no results for that district, and we skip it.
	if ($district != 9) {
		// For each poll in each district...
		foreach($results[$district] as $pollnumber => $poll) {
			// If the poll is reporting results, the sum of the poll will be more than zero.
			// Otherwise that poll has no results to report yet. Useful while results weren't
			// yet fully reported; no longer needed now that all polls have reported.
			if (array_sum($poll) > 0) {
				// The max($poll) finds the winner of the poll by looking for the maximum
				// value in the array storing results for this poll. But it returns the
				// *value*, not the key, so we need to use array_search() to find the key
				// (which is the name of the winning party).
				$winner = array_search(max($poll), $poll);
				// We need to account for ties, which we're going to skip over, and so if the
				// number of "winners" is more than one, we skip this poll.
				$howmanywinners = sizeof(array_keys($poll, max($poll)));
				if ($howmanywinners > 1) {
					// Do nothing: this was a tie.
				}
				else {
					// Write out a CSV value for this poll.
					fwrite($fp, $district . "-" . $pollnumber . ",$winner\n");
				}
			}
		}
	}
}
fclose($fp);

// We also want a CSV file that records the *second place* finisher of each poll
$fp = fopen("data/pei-election-poll-secondplace.csv", "w");
// Headers for the CSV: two columns, the district and poll
// concatenated, plus the winning party.
fwrite($fp, "distpoll,winner\n");

// Loop through each of the 27 electoral districts
for ($district = 1 ; $district <= 27 ; $district++) {
	// The election in district 9 was delayed, so there are
	// no results for that district, and we skip it.
	if ($district != 9) {
		// For each poll in each district...
		foreach($results[$district] as $pollnumber => $poll) {
			// If the poll is reporting results, the sum of the poll will be more than zero.
			// Otherwise that poll has no results to report yet. Useful while results weren't
			// yet fully reported; no longer needed now that all polls have reported.
			if (array_sum($poll) > 0) {
				// To find the second-place finisher, we sort the poll results, and then
				// use the second element in the array.
				arsort($poll);
				$values_only = array_values($poll);
				$secondplace = $values_only[1];
				$second_place_party = array_search($secondplace, $poll);
				fwrite($fp, $district . "-" . $pollnumber . ",$second_place_party\n");
			}
		}
	}
}
fclose($fp);

// We also want a CSV file that record the second place finisher of each poll
$fp = fopen("data/pei-election-poll-green-first-or-second.csv", "w");
// Headers for the CSV: two columns, the district and poll
// concatenated, plus the winning party.
fwrite($fp, "distpoll,winner\n");

// Loop through each of the 27 electoral districts
for ($district = 1 ; $district <= 27 ; $district++) {
	// The election in district 9 was delayed, so there are
	// no results for that district, and we skip it.
	if ($district != 9) {
		// For each poll in each district...
		foreach($results[$district] as $pollnumber => $poll) {
			// If the poll is reporting results, the sum of the poll will be more than zero.
			// Otherwise that poll has no results to report yet. Useful while results weren't
			// yet fully reported; no longer needed now that all polls have reported.
			if (array_sum($poll) > 0) {
				// To find the first- and second-place finishers, we sort the poll results, and then
				// use the first and second elements in the array.
				arsort($poll);
				$values_only = array_values($poll);
				$firstplace = $values_only[0];
				$secondplace = $values_only[1];
				$first_place_party = array_search($firstplace, $poll);
				$second_place_party = array_search($secondplace, $poll);
				if ($second_place_party == "Green" or $first_place_party == "Green") {
					fwrite($fp, $district . "-" . $pollnumber . ",Green\n");
				}
			}
		}
	}
}
fclose($fp);
