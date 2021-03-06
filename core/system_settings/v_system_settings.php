<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2010
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/config.php";
require_once "includes/paging.php";
require_once "includes/checkauth.php";
if (permission_exists('system_settings_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//domain list
	unset ($_SESSION["domains"]);
	$sql = "select * from v_system_settings ";
	$prepstatement = $db->prepare($sql);
	$prepstatement->execute();
	$result = $prepstatement->fetchAll();
	foreach($result as $row) {
		$_SESSION['domains'][$row['v_id']]['v_id'] = $row['v_id'];
		$_SESSION['domains'][$row['v_id']]['domain'] = $row['v_domain'];
		$_SESSION['domains'][$row['v_id']]['template_name'] = $row['v_template_name'];
	}
	$num_rows = count($result);
	unset($result, $prepstatement);

//get http values and set them as variables
	$orderby = $_GET["orderby"];
	$order = $_GET["order"];

//change the tenant
	if (strlen($_GET["v_id"]) > 0 && $_GET["domain_change"] == "true") {
		//update the v_id and session variables
			$v_id = $_GET["v_id"];
			$_SESSION['v_id'] = $_SESSION['domains'][$v_id]['v_id'];
			$_SESSION["v_domain"] = $_SESSION['domains'][$v_id]['domain'];
			$_SESSION["v_template_name"] = $_SESSION['domains'][$v_id]['template_name'];
		//clear the menu session so that it is regenerated for the current tenant
			$_SESSION["menu"] = '';
		//set the context
			if (count($_SESSION["domains"]) > 1) {
				$_SESSION["context"] = $_SESSION["v_domain"];
			}
			else {
				$_SESSION["context"] = 'default';
			}
	}

//include the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "      <br>";

	echo "<table width='100%' border='0'>\n";
	echo "</tr></table>\n";

	$rowsperpage = 150;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
	list($paging_controls, $rows_per_page, $tmp) = paging($num_rows, $param, $rowsperpage); 
	$offset = $rows_per_page * $page; 

	$sql = "";
	$sql .= " select * from v_system_settings ";
	if (strlen($orderby)> 0) { $sql .= "order by $orderby $order "; }
	$sql .= " limit $rowsperpage offset $offset ";
	$prepstatement = $db->prepare(check_sql($sql));
	$prepstatement->execute();
	$result = $prepstatement->fetchAll();
	$resultcount = count($result);
	unset ($prepstatement, $sql);

	$c = 0;
	$rowstyle["0"] = "rowstyle0";
	$rowstyle["1"] = "rowstyle1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' nowrap><strong>System Settings</strong></td>\n";
	echo "<td>&nbsp;</td>\n";
	echo "<td align='right' align='right'>&nbsp;</td>\n";
	echo "<td align='right' width='42'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo thorderby('v_domain', 'Domain', $orderby, $order);
	//echo thorderby('v_package_version', 'Package Version', $orderby, $order);
	echo thorderby('v_label', 'Label', $orderby, $order);
	//echo thorderby('v_name', 'Name', $orderby, $order);
	//echo thorderby('v_dir', 'Directory', $orderby, $order);
	echo "<th width='40%'>Description</th>\n";
	echo "<td align='right' width='42'>\n";
	if (permission_exists('system_settings_add')) {
		echo "	<a href='v_system_settings_add.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($resultcount == 0) {
		//no results found
	}
	else {
		//get the list of installed apps from the core and mod directories
			if (!is_array($apps)) {
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/v_config.php");
				$x=0;
				foreach ($config_list as &$config_path) {
					include($config_path);
					$x++;
				}
			}
		foreach($result as $row) {
			//if there are no permissions listed in v_group_permissions then set the default permissions
				$sql = "";
				$sql .= "select count(*) as count from v_group_permissions ";
				$sql .= "where v_id = ".$row['v_id']." ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetch();
				unset ($prep_statement);
				if ($result['count'] > 0) {
					if ($display_type == "text") {
						echo "Goup Permissions: 	no change\n";
					}
				}
				else {
					if ($display_type == "text") {
						echo "Goup Permissions: 	added\n";
					}
					//no permissions found add the defaults
						foreach($apps as $app) {
							foreach ($app['permissions'] as $sub_row) {
								foreach ($sub_row['groups'] as $group) {
									//add the record
									$sql = "insert into v_group_permissions ";
									$sql .= "(";
									$sql .= "v_id, ";
									$sql .= "permission_id, ";
									$sql .= "group_id ";
									$sql .= ")";
									$sql .= "values ";
									$sql .= "(";
									$sql .= "'".$row['v_id']."', ";
									$sql .= "'".$sub_row['name']."', ";
									$sql .= "'".$group."' ";
									$sql .= ")";
									$db->exec($sql);
									unset($sql);
								}
							}
						}
				}

			if (strlen($row['v_server_port']) == 0) { $row['v_server_port'] = '80'; }
			switch ($row['v_server_port']) {
				case "80":
					$url = strtolower($row['v_server_protocol']).'://'.$row['v_domain'];
					break;
				case "443":
					$url = strtolower($row['v_server_protocol']).'://'.$row['v_domain'];
					break;
				default:
					$url = strtolower($row['v_server_protocol']).'://'.$row['v_domain'].':'.$row['v_server_port'];
					break;
			}
			echo "<tr >\n";
			//echo "	<td valign='top' class='".$rowstyle[$c]."'><a href='".$url."'>".$row['v_domain']."</a></td>\n";
			echo "	<td valign='top' class='".$rowstyle[$c]."'><a href='v_system_settings.php?id=".$row['v_id']."&domain=".$row['v_domain']."'>".$row['v_domain']."</a></td>\n";
			//echo "	<td valign='top' class='".$rowstyle[$c]."'>".$row['v_package_version']."</td>\n";
			echo "	<td valign='top' class='".$rowstyle[$c]."'>".$row['v_label']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$rowstyle[$c]."'>".$row['v_name']."</td>\n";
			//echo "	<td valign='top' class='".$rowstyle[$c]."'>".$row['v_dir']."</td>\n";
			echo "	<td valign='top' class='rowstylebg'>".$row['v_description']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('system_settings_edit')) {
				echo "		<a href='v_system_settings_edit.php?id=".$row['v_id']."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('system_settings_delete')) {
				echo "		<a href='v_system_settings_delete.php?id=".$row['v_id']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $rowcount);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='7'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$pagingcontrols</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('system_settings_add')) {
		echo "			<a href='v_system_settings_add.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

//include the footer
	require_once "includes/footer.php";
?>
